## Synthesis

### Completion Status
- Status: COMPLETE
- Completed by: Standalone Developer Agent

### Implementation Summary
- Added a lazy-invalidation cache (`$_childrenCache`) to the virtual `children` property on `Node`.
- The `children` property hook now checks the cache before computing `array_filter` + `array_values`. On first access (or after invalidation), the result is computed and stored; subsequent reads return the cached array directly.
- All internal mutation sites that modify a node's `nodes[]` array now invalidate the cache:
  - `Node::clear()` — nulls the cache alongside `$nodes`
  - `Node::append_child()` — invalidates after appending to `$this->nodes`
  - `Node::detach_from_parent()` — invalidates the parent's cache after filtering its `nodes`
  - `Parser::link_nodes()` — invalidates the parent's cache after appending a node
- Added public methods `invalidate_children_cache()` and `invalidateChildrenCache()` (camelCase delegate) for consumer code that mutates `$node->nodes` directly.

### Documentation Updates
- `api-surface.md`: Updated `children` property hook description to mention lazy caching. Added `invalidate_children_cache()` to DOM Manipulation section and `invalidateChildrenCache()` to camelCase delegates, both with usage notes.
- `constraints.md`: Updated Memory Management section to document the lazy-invalidation cache behaviour and the consumer obligation to call `invalidate_children_cache()` after direct `nodes[]` mutation.

### Verification Summary
- Tests run: Full PHPUnit suite (298 tests, 1296 assertions)
- Static analysis run: PHPStan level 6
- Result: All pass, 0 errors

### Code Insights
- [low] (improvement) No observations — the code in the touched files is clean and consistent. The existing local caching in `children()`, `next_sibling()`, `prev_sibling()`, and `SelectorParser::seek()` is now redundant (the property hook returns a cached result), but the local variables add negligible overhead and serve as documentation of intent, so removing them is not warranted.

### Additional Comments
- The `$_childrenCache` is a `private` property, keeping it out of the serialisation/public surface. Internal code within the `Node` class can access it on other instances (PHP allows same-class private access), so `detach_from_parent()` can directly null the parent's cache without needing the public method.
- The public `invalidate_children_cache()` method exists solely for external consumers who bypass `append_child()` and mutate `$node->nodes` directly. All library-internal paths are handled automatically.
