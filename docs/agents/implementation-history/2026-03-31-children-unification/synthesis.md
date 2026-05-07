## Synthesis

### Completion Status
- Status: COMPLETE
- Completed by: Standalone Developer Agent

### Implementation Summary
- Replaced the stored `children[]` array on `Node` with a virtual PHP 8.4 property hook that computes its value by filtering `nodes[]` to exclude `HDOM_TYPE_TEXT` and `HDOM_TYPE_ENDTAG` nodes.
- `nodes[]` is now the single source of truth for all child node relationships. The `children` property is derived on read.
- Removed all write operations to `children`: in `append_child()`, `detach_from_parent()`, `clear()`, and `Parser::link_nodes()`.
- Simplified `Parser::link_nodes()` by removing the `$is_child` parameter — all nodes are added to `nodes[]` only, and `children` auto-computes.
- Removed dead code in `Parser::clear()` that iterated a non-existent `$this->children` property on the Parser class.
- Added local caching of the `children` property in methods that access it multiple times (`children()`, `next_sibling()`, `prev_sibling()`, `SelectorParser::seek()`) to avoid redundant `array_filter` computation.

### Documentation Updates
- `api-surface.md`: Moved `children` from stored properties to the property hooks section, updated all `children()` / `childNodes()` / sibling method notes to reference the virtual property instead of a stored array, updated `append_child()` note.
- `constraints.md`: Updated the `Node::clear()` description to reflect that `children` is no longer explicitly nulled (it returns `null` when `nodes` is null).

### Verification Summary
- Tests run: Full PHPUnit suite (298 tests, 1296 assertions)
- Static analysis run: PHPStan level 6
- Result: All tests pass, 0 PHPStan errors

### Code Insights
- [low] (improvement) `src/SimpleHtmlDom/Node.php`: The virtual `children` property computes a new array on every access via `array_filter` + `array_values`. For typical HTML documents this is negligible, but if a consumer accesses `$node->children` in a tight loop on a node with many direct children, it could add overhead. All internal callers now cache the result, so this only matters for external consumer code. If profiling reveals an issue, a lazy-invalidation cache (`$_childrenCache` cleared when `nodes[]` changes) could be added. **DONE**.
- [low] (debt) `src/SimpleHtmlDom/Parser.php`: The `Parser::clear()` method previously had a dead code block iterating `$this->children` — a property that does not exist on the Parser class. The `isset()` guard prevented errors, but the code was unreachable. This dead code has been removed as part of this change. **DONE**.
- [low] (convention) `src/SimpleHtmlDom/Node.php`: The `dump_node()` method still outputs separate "children:" and "nodes:" counts. With the unification, the children count is now derived. The debug output format hasn't changed, which maintains backward compatibility for any tooling that parses dump output. **DONE**.

### Additional Comments
- The `children` virtual property filter uses `!== HDOM_TYPE_TEXT && !== HDOM_TYPE_ENDTAG` rather than `=== HDOM_TYPE_ELEMENT`. This matches the original `link_nodes($node, true)` call sites: comments (`HDOM_TYPE_COMMENT`) and unknown nodes (`HDOM_TYPE_UNKNOWN`, e.g. doctypes) were added as children. The filter preserves this exact behavior.
- External code that reads `$node->children` continues to work unchanged. Code that writes to `$node->children` (e.g., `$node->children[] = $x`) will now fail at runtime — but no such external usage pattern exists in the library's public API contract. The `append_child()` method is the supported way to add children.
