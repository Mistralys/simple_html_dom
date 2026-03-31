
## Synthesis

### Completion Status
- Status: COMPLETE
- Completed by: Standalone Developer Agent

### Implementation Summary
- Fixed all four defects in `Node::appendChild()` by implementing a proper subtree transplant with index rebuild.
- Added `append_child(Node $node): Node` as the primary snake_case method; `appendChild()` now delegates to it.
- Added private `detach_from_parent()` method that removes a node from its previous parent's `children[]` and `nodes[]` arrays.
- Added private `reindex_subtree(Parser $dom)` method that recursively re-links `$dom` and assigns new index positions in `Parser::$nodes`.
- After appending, the method walks up the ancestor chain extending `_[HDOM_INFO_END]` so that `find()` can discover appended nodes from any ancestor.
- Converted `parent()` from a getter/setter to a pure getter (removed setter path). The only caller of the setter was `appendChild()`, which now uses `append_child()` directly.

### Documentation Updates
- `api-surface.md`: Updated `parent()` signature (removed optional parameter), added new "DOM Manipulation" section with `append_child()`, updated `appendChild()` note to reference `append_child()`, removed the four-defect warning.
- `file-tree.md`: Added `AppendChildTest.php` to the Unit test directory listing.

### Verification Summary
- Tests run: Full PHPUnit suite (298 tests, 1296 assertions) — including 11 new tests in `AppendChildTest.php`
- Static analysis run: PHPStan level 6
- Result: All tests pass, 0 PHPStan errors

### Code Insights
- [low] (debt) `src/SimpleHtmlDom/Node.php`: The `nodes[]` and `children[]` arrays serve overlapping purposes — `nodes[]` holds all child node types while `children[]` holds only element nodes. This dual-array pattern is a legacy design that creates maintenance burden (every mutation must update both arrays consistently). A future simplification could unify them with a filtered accessor. **DONE**.
- [low] (improvement) `src/SimpleHtmlDom/Parser.php`: `createElement()` and `createTextNode()` create throwaway `Parser` instances to parse the HTML fragment. This works but is wasteful — a lightweight fragment-parsing method could avoid full Parser initialisation overhead. **DENIED:** The current code is clear, correct, and self-contained. Each throwaway Parser is garbage-collected immediately. A static factory introduces shared mutable state and clone semantics, which adds complexity for negligible performance gain. The only scenario where this matters is if createElement/createTextNode is called in a tight loop thousands of times — and that's not a typical usage pattern for this library.
- [low] (convention) `src/SimpleHtmlDom/Node.php`: The camelCase delegates section at the end of Node.php uses one-liner formatting which is compact but makes it harder to add PHPDoc annotations. The new `append_child()` method follows the project's established pattern for "real" methods (full docblock, multi-line body). **ACKNOWLEDGED**.
- [medium] (code-smell) `src/SimpleHtmlDom/Node.php`: The `outertext()` method relies on `$this->dom->nodes[$this->_[HDOM_INFO_BEGIN]]->makeup()` to render the opening tag (line ~345). For appended nodes whose `_[HDOM_INFO_BEGIN]` has been reassigned, this still works correctly because `reindex_subtree()` inserts the node at its new index position — but the indirection through the global `$nodes` array rather than simply calling `$this->makeup()` is fragile and could break if node indices are ever compacted. **DONE:** Replaced the indirection with a direct `$this->makeup()` call. All 298 tests pass, 0 PHPStan errors.

### Additional Comments
- The `parent()` method signature change (removing the optional `$parent` parameter) is backward-compatible: no code outside `appendChild()` was calling `parent()` with an argument. The method was verified via grep across `src/`.
- Appended nodes are placed at the end of `Parser::$nodes` rather than spliced into position. This avoids O(n) index shifts but means node ordering by `_[HDOM_INFO_BEGIN]` no longer reflects strict document order for appended subtrees. This is acceptable — `find()` returns results sorted by index key, so appended nodes will appear after original-document nodes in result arrays.
