# Plan — Example Hub Synthesis Rework

## Summary

Implement the strategic findings and close the documentation gaps identified in the [Example Hub synthesis report](../2026-03-26-example-hub/synthesis.md). This covers seven manifest documentation updates (`api-surface.md`, `constraints.md`, `data-flows.md`), a code-level investigation of `find('*')` and `appendChild()`, and verification tests for the sibling/children node-type behaviours.

## Architectural Context

The project manifest under `docs/agents/project-manifest/` is the canonical documentation source for AI agents. Three files require updates:

- **`api-surface.md`** — Public API documentation for all classes, enums, and procedural functions. Currently lacks behavioural annotations for `outertext`, `appendChild()`, `plaintext`/`text()`, `children()`/`childNodes()`, and `next_sibling()`/`prev_sibling()`.
- **`constraints.md`** — Coding rules and known limitations. Already has `find('*')` documented (added during the Example Hub session). Missing the `tbody` selector workaround pattern.
- **`data-flows.md`** — Eight numbered data-flow diagrams. Missing the canonical error-handling flow.

Source files involved:

- `src/SimpleHtmlDom/Node.php` — Contains `appendChild()` (L705), `parent()` (L174), `next_sibling()` (L235), `prev_sibling()` (L255), `children()` (L199), `text()` (L367).
- `src/SimpleHtmlDom/SelectorParser.php` — Contains the `find('*')` direct-children-only logic (L138–142 in `seek()`).
- `src/SimpleHtmlDom/Parser.php` — Contains `link_nodes()` (L630) which controls `children[]` vs `nodes[]` population.

### Key Implementation Detail Discovered During Research

The synthesis claims "`appendChild()` sets only the parent pointer and does not insert the node into the children array." This is **partially inaccurate**. The actual call chain is:

```
appendChild($node) → $node->parent($this) → sets $node->parent, adds to $this->nodes[], adds to $this->children[]
```

So `appendChild()` **does** add the node to `children[]` and `nodes[]`. However, the implementation is still broken because:

1. It does **not** remove the node from its previous parent's `children[]` / `nodes[]` arrays.
2. It does **not** set `$node->dom` to match the new parent's `$dom` reference.
3. It does **not** update `_[HDOM_INFO_BEGIN]` / `_[HDOM_INFO_END]` positions on the node or its subtree.
4. It does **not** rebuild the `Parser::$nodes` global index.

The source code itself contains the comment (Node.php L176): *"I am SURE that this doesn't work properly. It fails to unset the current node from its current parents nodes or children list first."*

### Key Implementation Detail: `children[]` vs `nodes[]`

During parsing, `Parser::link_nodes()` (L630) controls array membership:

- `link_nodes($node, true)` → element nodes → added to **both** `parent->nodes[]` and `parent->children[]`
- `link_nodes($node, false)` → text/comment nodes → added to `parent->nodes[]` **only**

Therefore, in a normally parsed tree:
- `children[]` contains **element nodes only** (not text/whitespace nodes).
- `nodes[]` contains **all nodes** (elements + text + comments).
- `next_sibling()` / `prev_sibling()` iterate `children[]`, so they return **element siblings only** in standard parsed documents.

The synthesis claim that "next_sibling()/prev_sibling() return whitespace text nodes" appears to be inaccurate for normally parsed trees. This should be verified with a dedicated test before documenting the behaviour.

## Approach / Architecture

The work breaks into three categories:

1. **Manifest documentation updates** (7 items across 3 files) — pure text edits to `api-surface.md`, `constraints.md`, and `data-flows.md`.
2. **Code-level decision: `appendChild()`** — Mark as incomplete/unsupported in the API surface with a documentation annotation. A full fix is a separate future task due to the complexity of subtree rebuilding.
3. **Verification tests** — Write targeted PHPUnit tests to confirm `children[]` vs `nodes[]` membership, `next_sibling()`/`prev_sibling()` return types, and `outertext = ''` node-retention behaviour. These tests serve as regression anchors and as evidence for the documentation annotations.

## Rationale

- **Documentation-first approach:** The synthesis identified that the examples already demonstrate correct patterns. The manifest just needs to match reality. This is high-value, low-risk work.
- **Marking `appendChild()` as unsupported rather than fixing it:** The fix requires subtree index rebuilding, old-parent cleanup, and `$dom` reference propagation — a non-trivial change that risks breaking existing consumers. Documenting the limitation is safer and immediately useful.
- **Verification tests before documenting sibling behaviour:** The synthesis claim about whitespace text nodes in siblings contradicts the actual `link_nodes()` logic. We must test before we document to avoid encoding false information in the manifest.
- **Not investigating `find('*')` in this rework:** The `find('*')` behaviour is already documented in `constraints.md`. A fix would require changing `SelectorParser::seek()` which has broad downstream impact. That decision is deferred to a dedicated plan.

## Detailed Steps

### Step 1: Add verification tests

Create a new test file `tests/Unit/NodeBehaviorTest.php` with the following test cases:

1. **`test_children_array_contains_only_elements`** — Parse HTML with interleaved text and elements. Assert that `$node->children` contains only `HDOM_TYPE_ELEMENT` nodes.
2. **`test_nodes_array_contains_all_node_types`** — Same fixture. Assert that `$node->nodes` contains text and element nodes.
3. **`test_next_sibling_returns_element_not_text`** — Parse `<div><p>A</p> whitespace <p>B</p></div>`. Assert `$p1->next_sibling()->tag === 'p'` (not a text node).
4. **`test_prev_sibling_returns_element_not_text`** — Same fixture, reverse direction.
5. **`test_outertext_empty_retains_node_in_nodes_array`** — Parse HTML, set `$node->outertext = ''`, assert node is still found via `find()`, assert `save()` output excludes it.
6. **`test_plaintext_preserves_inter_node_whitespace`** — Parse `<p><span>A</span> <span>B</span></p>`, assert `$p->plaintext` contains the space between spans.

These tests confirm the behaviours before we document them. Run `composer test-file -- tests/Unit/NodeBehaviorTest.php` to validate.

### Step 2: Update `constraints.md` — Add `tbody` workaround

In the **CSS Selector Limitations** section, after the existing `tbody` bullet and the `find('*')` paragraph, add a named workaround pattern:

```markdown
### `tbody` Workaround Pattern

Because the parser treats `<tbody>` as transparent (it is silently skipped), descendant selectors like `find('tbody tr')` will not match. To select table data rows while skipping header rows, use:

    $rows = $dom->find('tr');
    foreach ($rows as $row) {
        if ($row->find('th', 0)) {
            continue; // skip header rows
        }
        // process data row
    }
```

### Step 3: Update `api-surface.md` — `outertext` property hook annotation

Under the `Node` class → **Property Hooks** section, add a `> **Caveat**` note to the `outertext` entry:

> **Caveat — node retention:** Setting `$node->outertext = ''` suppresses the node's serialisation output but does **not** remove the node from `Parser::$nodes` or from `find()` results. To verify removal, inspect `$dom->save()` output. Treat `outertext = ''` as a render-time suppression, not a DOM removal.

### Step 4: Update `api-surface.md` — `appendChild()` annotation

Under the `Node` class → **camelCase DOM-API Delegates** section, add a `> **Warning**` note to the `appendChild` entry:

> **Warning — incomplete implementation:** `appendChild()` delegates to `parent()`, which sets the parent pointer and appends the node to the new parent's `children[]` and `nodes[]` arrays. However, it does **not** remove the node from its previous parent's arrays, does **not** propagate the `$dom` reference, and does **not** rebuild subtree index positions (`_[HDOM_INFO_BEGIN]` / `_[HDOM_INFO_END]`). Do not rely on `appendChild()` for DOM manipulation. It is retained for forward-compatibility but is functionally unsupported.

### Step 5: Update `api-surface.md` — `plaintext` / `text()` annotation

Under the `Node` class → **Magic Read-Only Properties** section, add a note to the `plaintext` entry:

> **Note:** `plaintext` (via `text()`) preserves whitespace between inline child nodes. For indented HTML, the result may contain unexpected padding. No trimming or normalisation is applied.

### Step 6: Update `api-surface.md` — `children()` / `childNodes()` return contract

Under the `Node` class → **Tree Navigation** → `children()` entry, add:

> **Return contract:** In a parsed document, the `children` array contains **element nodes only** (`HDOM_TYPE_ELEMENT`). Text nodes, comments, and other non-element nodes are in the `nodes` array but not in `children`. The `$idx` parameter indexes into the element-only `children` array.

Duplicate a similar note on `childNodes()` in the camelCase delegates section.

### Step 7: Update `api-surface.md` — `next_sibling()` / `prev_sibling()` annotation

Under the `Node` class → **Tree Navigation** → `next_sibling()` and `prev_sibling()` entries, add:

> **Navigation scope:** These methods iterate the parent's `children` array, which contains element nodes only in a parsed tree. Whitespace text nodes between elements are not returned. To traverse all node types (including text), iterate `$node->parent->nodes` manually.

Duplicate on `nextSibling()` / `previousSibling()` in the camelCase delegates section.

### Step 8: Update `api-surface.md` — `find('*')` cross-reference

Under the `Node` class → **Search** → `find()` entry, and under `Parser` → **Searching** → `find()` entry, add:

> **Limitation:** `find('*')` returns only direct children of the context node, not all descendants. See the CSS Selector Limitations section in `constraints.md` for details and workarounds.

### Step 9: Update `data-flows.md` — Add canonical error-handling flow

Add a new section **9. Error Handling** after the existing section 8:

```markdown
## 9. Error Handling

\`\`\`
Consumer calls str_get_html($html) or file_get_html($url)
  → On failure (empty, oversized, bad HTTP response):
      → Bridge stores Error in Settings::set('__error', new Error($message, $code))
      → Returns false

Consumer checks for error:
  → simple_html_dom_get_error()
      → Returns Settings::get('__error')  →  Error|null
  → If Error returned:
      → $error->getMessage()  → human-readable message
      → $error->getCode()    → 1001 (empty) | 1002 (oversized) | 1003 (bad HTTP)
      → (string) $error      → "[{code}] {message}"

Error codes:
  1001 — HTML content is empty
  1002 — HTML content exceeds Settings::getMaxFilesize() limit
  1003 — HTTP response returned non-200 status code
\`\`\`
```

### Step 10: Run full test suite and static analysis

```bash
composer test
composer analyze
```

Verify all existing tests pass and no new PHPStan errors are introduced.

## Dependencies

- Step 1 (verification tests) should be completed before Steps 3–7, so documentation annotations are grounded in verified behaviour.
- Steps 2–9 (documentation edits) are independent of each other and can be parallelised.
- Step 10 depends on all prior steps.

## Required Components

### New Files
- `tests/Unit/NodeBehaviorTest.php` — Verification tests for node array membership and property behaviours.

### Modified Files
- `docs/agents/project-manifest/constraints.md` — `tbody` workaround pattern (Step 2).
- `docs/agents/project-manifest/api-surface.md` — Six annotation additions (Steps 3–8).
- `docs/agents/project-manifest/data-flows.md` — Error-handling flow section (Step 9).
- `docs/agents/project-manifest/file-tree.md` — Add `NodeBehaviorTest.php` entry to the test file tree.

## Assumptions

- The `children[]` vs `nodes[]` distinction observed in `Parser::link_nodes()` holds true across all parse paths. The verification tests in Step 1 will confirm this.
- The synthesis finding about `next_sibling()` returning whitespace text nodes is inaccurate for normally parsed trees. If verification tests reveal otherwise, Steps 6–7 documentation must be adjusted.
- `appendChild()` will remain functionally unsupported in this rework. A full fix is deferred.
- `find('*')` behaviour will remain unchanged. Investigation into whether it is a bug is deferred.

## Constraints

- All changes must pass `composer test` (PHPUnit 12.x, 278+ existing tests) and `composer analyze` (PHPStan level 6).
- Manifest updates must follow the conventions in `AGENTS.md` § 2 (Manifest Maintenance Rules).
- No backward-compatibility breaking changes.
- `Settings::reset()` must be called in `tearDown()` for the new test class per `constraints.md`.

## Out of Scope

- **Fixing `appendChild()`** — Requires subtree rebuilding, old-parent cleanup, and `$dom` reference propagation. Deferred to a dedicated plan.
- **Fixing `find('*')`** — Requires changes to `SelectorParser::seek()` with broad selector-engine impact. Deferred to a dedicated plan.
- **Removing the legacy `example/` directory** — Deferred to a future breaking-release cycle.
- **Code changes to `Node`, `Parser`, or `SelectorParser`** — This rework is documentation + verification only.

## Acceptance Criteria

1. `tests/Unit/NodeBehaviorTest.php` exists with 6 test methods, all passing.
2. `constraints.md` contains a named `### tbody Workaround Pattern` subsection with the `find('tr')` + `th`-guard pattern.
3. `api-surface.md` `outertext` property hook has a caveat annotation about node retention.
4. `api-surface.md` `appendChild()` has a warning annotation about incomplete implementation.
5. `api-surface.md` `plaintext` has a note about whitespace preservation.
6. `api-surface.md` `children()` and `childNodes()` document the element-only return contract.
7. `api-surface.md` `next_sibling()`, `prev_sibling()`, `nextSibling()`, `previousSibling()` document element-only traversal scope.
8. `api-surface.md` `find()` (both `Node` and `Parser`) cross-references the `find('*')` limitation.
9. `data-flows.md` contains a section **9. Error Handling** with the canonical error-check flow.
10. `file-tree.md` includes `NodeBehaviorTest.php`.
11. `composer test` passes with 0 failures.
12. `composer analyze` passes with 0 errors.

## Testing Strategy

- **New verification tests** (Step 1) confirm the actual runtime behaviour of `children[]`, `nodes[]`, `next_sibling()`, `outertext = ''`, and `plaintext` before any documentation is written.
- **Full regression suite** (`composer test`) validates no existing behaviour is broken.
- **Static analysis** (`composer analyze`) validates type safety.
- If any verification test fails (i.e., the behaviour differs from what codebase research predicts), the corresponding documentation step must be adjusted before proceeding.

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **Verification tests reveal unexpected behaviour** (e.g., `children[]` does contain text nodes in certain parse paths) | Adjust documentation annotations to match verified reality. Flag discrepancy for investigation. |
| **Synthesis claim about sibling whitespace nodes turns out to be context-dependent** (e.g., only when `parent()` is called manually) | Document both the normal-parse behaviour and the `parent()` edge case explicitly. |
| **`appendChild()` warning discourages legitimate use** | Frame the annotation as "functionally unsupported" rather than "deprecated" — leaves room for a future fix without breaking the API contract. |
| **Large diff in `api-surface.md` causes merge conflicts** | Apply edits in distinct, well-separated sections of the file to minimise overlap. |
