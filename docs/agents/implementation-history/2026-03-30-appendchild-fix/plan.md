# Plan — Fix `appendChild()` Implementation

## Summary

Fix the four known defects in `Node::appendChild()` (and the underlying `Node::parent()` setter path) so that appending a node to a new parent produces a consistent, queryable DOM tree. The current implementation only sets `$this->parent` and appends to the new parent's `children[]`/`nodes[]` arrays — it does not clean up the old parent, propagate the `$dom` reference, recalculate index positions, or rebuild the Parser's global `$nodes` index. This makes `appendChild()` functionally broken for any use case that involves `find()`, serialisation, or tree traversal after the append.

## Architectural Context

### Current Implementation

**`Node::appendChild()`** (`src/SimpleHtmlDom/Node.php` line 705):
```php
public function appendChild(Node $node): Node { $node->parent($this); return $node; }
```

**`Node::parent()` setter path** (`src/SimpleHtmlDom/Node.php` lines 175–183):
```php
public function parent(?Node $parent = null): ?Node
{
    // I am SURE that this doesn't work properly.
    // It fails to unset the current node from it's current parents nodes or children list first.
    if ($parent !== null) {
        $this->parent = $parent;
        $this->parent->nodes[] = $this;
        $this->parent->children[] = $this;
    }
    return $this->parent;
}
```

### The Four Defects

1. **No parent-removal:** The node is not removed from its previous parent's `children[]` and `nodes[]` arrays. After `appendChild()`, the node appears in both the old and new parent's child lists.

2. **No `$dom` propagation:** The node (and all its descendants) retain their original `$dom` (Parser) reference. When a node is created via `Parser::createElement()` or `Parser::createTextNode()`, it belongs to a **temporary, throwaway Parser instance**. After `appendChild()`, the node's `$dom` still points to that temporary Parser, not the target document's Parser.

3. **No index position recalculation:** `_[HDOM_INFO_BEGIN]` and `_[HDOM_INFO_END]` are cursor positions into `Parser::$nodes`. These are not updated, so the node's position information is stale and indexes into the wrong `$nodes` array.

4. **No global index rebuild:** `Parser::$nodes` is a flat array where each node's `_[HDOM_INFO_BEGIN]` is its index. Appended nodes are not inserted into this array, so `find()` (which iterates `$this->dom->nodes[$i]` from `_[HDOM_INFO_BEGIN]+1` to `_[HDOM_INFO_END]`) can never discover them.

### Key Downstream Dependencies

- **`Node::find()`** (`src/SimpleHtmlDom/Node.php` line 461) — resolves nodes via `$this->dom->nodes[$k]` using `HDOM_INFO_BEGIN`/`HDOM_INFO_END` ranges. Broken for appended nodes.
- **`SelectorParser::seek()`** (`src/SimpleHtmlDom/SelectorParser.php` line 100) — iterates the `$this->node->dom->nodes` array within the begin/end range. Appended nodes are invisible.
- **`Node::outertext()` / `Node::innertext()`** — serialise the node tree. Text output of appended subtrees may be incorrect if `$dom` points to a dead Parser (noise restoration fails).
- **`Parser::createElement()` / `Parser::createTextNode()`** (`src/SimpleHtmlDom/Parser.php` lines 821–832) — create nodes via temporary Parser instances. The returned nodes have `$dom` pointing to these ephemeral parsers.
- **`Node::clear()`** — nulls `$dom`, `$nodes`, `$parent`, `$children`. Must not be called on nodes still in the tree.

### Existing Tests

There are **zero** tests for `appendChild()` anywhere in the test suite. The `api-surface.md` documents it with a `> **Warning**` annotation listing all four defects. No examples use `appendChild()`.

### Constraints from `constraints.md`

- Both `snake_case` (`append_child`) and `camelCase` (`appendChild`) method names must be provided.
- `Settings::reset()` must be called in test `tearDown()`.
- Memory management: appended nodes must not create circular references that bypass `clear()`.

## Approach / Architecture

### Strategy: Subtree Transplant with Index Rebuild

The fix should follow a transplant model:

1. **Detach** the node from its current parent (if any).
2. **Attach** the node to the new parent's `children[]` and `nodes[]`.
3. **Re-link** `$dom` on the entire subtree to point to the new parent's Parser.
4. **Insert** all subtree nodes into `Parser::$nodes` at new index positions.
5. **Recalculate** `_[HDOM_INFO_BEGIN]` and `_[HDOM_INFO_END]` for the transplanted subtree and adjust the parent's `_[HDOM_INFO_END]`.

### Method Placement

- The core logic should live in a new private method `Node::detach_from_parent()` and an updated `Node::parent()` setter path (or a new `Node::append_child()` method that replaces the current trivial one-liner).
- `appendChild()` continues to delegate to the snake_case method.
- A new private method `Node::reindex_subtree(Parser $dom)` handles recursive `$dom` re-linking and index insertion.

### Index Rebuild Approach

Rather than rebuilding the entire `Parser::$nodes` array (which would invalidate all existing `_[HDOM_INFO_BEGIN]`/`_[HDOM_INFO_END]` values across the whole document), the approach should:

1. Assign new index positions **at the end** of `Parser::$nodes` for the transplanted subtree.
2. Update `_[HDOM_INFO_BEGIN]` and `_[HDOM_INFO_END]` for each node in the subtree relative to the new positions.
3. Update the new parent's `_[HDOM_INFO_END]` to encompass the appended subtree.

This avoids the O(n) cost of shifting all existing nodes and is the safest approach for backward compatibility.

**Important caveat:** Appending at the end of `$nodes` means that `find()` traversal (which iterates by index range) will only discover the appended nodes if the parent's `_[HDOM_INFO_END]` is extended to cover them. Since `find()` uses `for ($i = begin+1; $i < end; ++$i)` to scan `$nodes`, the appended nodes must be in a contiguous range reachable from the parent. Appending at the end of the global array satisfies this only if the parent is the root (or if we update all ancestor `_[HDOM_INFO_END]` values up to the root).

**Refined approach:** Walk up the ancestor chain and extend `_[HDOM_INFO_END]` for each ancestor to be `max(current_end, new_subtree_end)`. This ensures `find()` from any ancestor (including root) can reach the appended nodes.

## Rationale

- Appending at the end of `$nodes` rather than splicing mid-array avoids invalidating all existing index positions — this is critical for a library where the global index is the backbone of the selector engine.
- Recursive `$dom` re-linking is necessary because `createElement()` creates nodes in throwaway Parsers — without re-linking, noise restoration and `find()` would reference a dead Parser.
- Detaching from the old parent first prevents the node from appearing in two places simultaneously.
- The dual-name convention (`append_child` + `appendChild`) is mandatory per `constraints.md`.

## Detailed Steps

### Step 1 — Add `append_child()` / Refactor `appendChild()`

1. In `src/SimpleHtmlDom/Node.php`, add a new public method `append_child(Node $node): Node` with the full implementation.
2. Change `appendChild()` to delegate to `append_child()`.
3. The `append_child()` method must:
   a. Call `$node->detach_from_parent()` (new private method — Step 2).
   b. Set `$node->parent = $this`.
   c. Append `$node` to `$this->children[]` and `$this->nodes[]`.
   d. If `$this->dom !== null`, call `$node->reindex_subtree($this->dom)` (new private method — Step 3).
   e. Return `$node`.

### Step 2 — Add `detach_from_parent()` Private Method

1. Add `private function detach_from_parent(): void` to `Node`.
2. Implementation:
   a. If `$this->parent === null`, return immediately.
   b. Remove `$this` from `$this->parent->children[]` using `array_values(array_filter(...))` to re-index.
   c. Remove `$this` from `$this->parent->nodes[]` using the same pattern.
   d. Set `$this->parent = null`.
3. **Do not** remove from the old `Parser::$nodes` — the old Parser may still need its index intact for other operations. The stale entry will be harmless (a node with no parent is unreachable from the tree).

### Step 3 — Add `reindex_subtree()` Private Method

1. Add `private function reindex_subtree(Parser $dom): void` to `Node`.
2. Implementation:
   a. Set `$this->dom = $dom`.
   b. Calculate the new `_[HDOM_INFO_BEGIN]` as `count($dom->nodes)`.
   c. Append `$this` to `$dom->nodes[]`.
   d. Recursively call `reindex_subtree($dom)` on each child in `$this->nodes` (if any — this covers the full subtree including text nodes).
   e. Set `$this->_[HDOM_INFO_END]` to `count($dom->nodes)`.
   f. After all subtree nodes are inserted, walk up the ancestor chain (`$this->parent`, `$this->parent->parent`, ...) and extend each ancestor's `_[HDOM_INFO_END]` to `max(current, count($dom->nodes))`.

### Step 4 — Clean Up `parent()` Method

1. Remove the setter path from `Node::parent()`. The `parent()` method should be a pure getter (or a getter that only sets `$this->parent` without the `children[]`/`nodes[]` side effects).
2. The setter functionality is now handled exclusively by `append_child()`.
3. Remove the comment "I am SURE that this doesn't work properly." — it no longer applies.
4. **Backward-compatibility check:** Verify no other code in the library calls `parent($node)` with an argument. Search for `->parent(` calls throughout `src/`.

### Step 5 — Write Tests

Create tests in `tests/Unit/AppendChildTest.php`:

1. **`test_append_child_basic`** — Append a `createElement()` node to an existing DOM. Verify the node appears in `$parent->children` and `$parent->nodes`.
2. **`test_append_child_find_discovers_appended_node`** — After appending, `$dom->find('appended-tag')` must find the node.
3. **`test_append_child_detaches_from_old_parent`** — Move a node from one parent to another. Verify it no longer appears in the old parent's `children[]`.
4. **`test_append_child_propagates_dom_reference`** — After appending a `createElement()` node, `$node->dom` must point to the target Parser, not the temporary one.
5. **`test_append_child_subtree_reindexed`** — Append a node with its own children. Verify `find()` from the root discovers the grandchild.
6. **`test_append_child_serialises_correctly`** — After appending, `$dom->save()` must include the appended node's HTML in the output.
7. **`test_append_child_outertext_includes_appended`** — The parent's `outertext` must include the appended child.
8. **`test_append_child_with_text_node`** — Append a `createTextNode()` node and verify it appears in output.

### Step 6 — Update `parent()` Method Documentation

1. In `api-surface.md`, update the `parent()` signature note to reflect that it is now a pure getter (or document the revised setter semantics if a setter path is retained for backward compatibility).
2. Update the `appendChild()` / `append_child()` entry: remove the `> **Warning**` annotation and replace it with accurate documentation of the new behaviour.

### Step 7 — Manifest Updates

1. Update `api-surface.md`:
   - Add `append_child(Node $node): Node` to the snake_case methods section.
   - Update `appendChild()` documentation.
   - Update `parent()` documentation.
2. Update `file-tree.md` to include `tests/Unit/AppendChildTest.php`.
3. Update `constraints.md` if any new conventions are established.

### Step 8 — Run Full Test Suite and Static Analysis

1. `composer test` — all tests must pass (existing 284+ plus new tests).
2. `composer analyze` — PHPStan level 6, 0 errors.

## Dependencies

- Steps 1–3 are tightly coupled and should be implemented together.
- Step 4 depends on Step 1 (to avoid breaking the `parent()` setter before the replacement is ready).
- Step 5 depends on Steps 1–4.
- Steps 6–7 depend on Step 5 (test results confirm the documented behaviour).
- Step 8 depends on all previous steps.

## Required Components

- `src/SimpleHtmlDom/Node.php` — existing, primary modification target (Steps 1–4)
- `tests/Unit/AppendChildTest.php` — **new file** (Step 5)
- `docs/agents/project-manifest/api-surface.md` — existing, to be updated (Steps 6–7)
- `docs/agents/project-manifest/file-tree.md` — existing, to be updated (Step 7)

## Assumptions

- `Parser::createElement()` and `Parser::createTextNode()` are the primary sources of nodes for `appendChild()`. Nodes can also come from an existing parsed tree (node relocation).
- No external code within this library calls `parent($node)` as a setter outside of the `appendChild()` path. (Must verify in Step 4.)
- The `find()` engine's index-range iteration model (`for $i = begin+1; $i < end`) is the only code path that depends on `_[HDOM_INFO_BEGIN]`/`_[HDOM_INFO_END]` values.
- Appended nodes at the end of `$nodes` array are reachable via ancestor `_[HDOM_INFO_END]` extension.

## Constraints

- Backward compatibility: `appendChild()` signature (`Node $node): Node`) must not change.
- Dual naming: `append_child()` (snake_case) must be added as the primary method; `appendChild()` delegates to it.
- Memory: must not introduce circular references that bypass `clear()`.
- All existing 284+ tests must continue to pass.
- PHPStan level 6 must pass with 0 errors.
- `Settings::reset()` must be called in test `tearDown()`.

## Out of Scope

- Implementing `removeChild()` / `remove_child()` — though the `detach_from_parent()` method provides the foundation.
- Implementing `insertBefore()` / `replaceChild()` — future work that can build on this.
- Fixing `createElement()` / `createTextNode()` to produce nodes already linked to the calling Parser — the `reindex_subtree()` approach handles this at append time instead.
- Performance optimisation of the index rebuild — the append-at-end strategy is O(subtree_size + ancestor_depth), which is acceptable.

## Acceptance Criteria

1. `$parent->append_child($node)` removes the node from its old parent's `children[]` and `nodes[]`.
2. `$parent->appendChild($node)` delegates to `append_child()` and produces identical results.
3. After `appendChild()`, `$node->dom` and all descendants' `$dom` reference the target Parser.
4. After `appendChild()`, `$dom->find('tag')` discovers the appended node.
5. After `appendChild()`, `$dom->save()` includes the appended node's HTML.
6. After `appendChild()`, `$parent->outertext` includes the appended node.
7. Moving a node between parents works correctly (old parent no longer contains it).
8. Appending a subtree (node with children) works correctly — `find()` discovers grandchildren.
9. All existing tests pass. New tests cover all criteria above.
10. PHPStan level 6 passes with 0 errors.

## Testing Strategy

- **New test file:** `tests/Unit/AppendChildTest.php` with 8 test methods covering all acceptance criteria.
- **Regression:** Full suite (`composer test`) must pass — critical since index manipulation could break selector traversal.
- **Static analysis:** `composer analyze` for type safety verification.
- **Manual verification:** Test with `createElement()`, `createTextNode()`, and node relocation scenarios.

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| **Extending ancestor `_[HDOM_INFO_END]` may cause `find()` to scan garbage indices** | The extended range covers only valid nodes appended to `$dom->nodes` — no invalid indices are introduced. Unit tests verify `find()` correctness. |
| **Removing parent setter path breaks internal callers** | Search `src/` for `->parent(` calls before removing. If internal callers exist, refactor them to use `append_child()` instead. |
| **`clear()` on the old temporary Parser invalidates nodes before append** | `createElement()` creates a Parser that remains alive until the returned node goes out of scope. The node is appended before the temporary Parser is garbage-collected, so `$dom` re-linking happens in time. |
| **Performance of `reindex_subtree()` for large subtrees** | Appending at the end of `$nodes` is O(n) where n = subtree size, which is acceptable. No existing nodes are shifted. |
| **Stale entries in old Parser's `$nodes` after detach** | Stale entries are harmless — they reference nodes no longer reachable from the old tree. They will be cleaned up when the old Parser's `clear()` is called. |
