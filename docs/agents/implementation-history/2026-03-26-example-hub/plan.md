# Plan

## Summary

Replace the flat, legacy `example/` directory with a modular, category-organised example hub. Retire the outdated scraping examples that depend on external live websites, add 10 new examples covering the API gaps identified in the gap analysis, and provide a central `README.md` index that lets consumers find the right example in seconds. All examples must be self-contained (inline HTML fixtures, Composer autoloading) and runnable from the CLI without a web server.

## Architectural Context

### Current State

The existing `example/` directory contains 9 PHP files in a flat structure:

```
example/
├── example_advanced_selector.php
├── example_basic_selector.php
├── example_callback.php
├── example_extract_html.php
├── example_modify_contents.php
├── simple_html_dom_utility.php
└── scraping/
    ├── example_scraping_digg.php
    ├── example_scraping_imdb.php
    └── example_scraping_slashdot.php
```

**Problems with the current setup:**

1. **No autoloading** — all files use `include('../simple_html_dom.php')`, a pre-Composer pattern.
2. **External dependencies** — `example_basic_selector.php`, `example_callback.php`, `example_extract_html.php`, and `example_modify_contents.php` fetch `http://www.google.com/` at runtime. All three scraping examples depend on live third-party sites (Digg, IMDB, Slashdot) whose HTML structures have changed, making the examples non-functional.
3. **Flat organisation** — no categorisation; the `scraping/` subfolder is the only grouping.
4. **No discoverability** — no index or README; consumers must open each file to understand what it demonstrates.
5. **No CLI focus** — examples output HTML (`<br>`, `<hr>`) assuming browser rendering.
6. **API gaps** — major library features (error handling, tree navigation, camelCase DOM API, attribute manipulation, save-to-file, table extraction, advanced selectors, text nodes) have no examples.

### Library API Surface (relevant features)

- **Loading:** `str_get_html()`, `file_get_html()`, `new Parser()`
- **Selectors:** tag, `#id`, `.class`, `[attr]`, `[attr=val]`, `[attr!=val]`, `[attr^=val]`, `[attr$=val]`, `[attr*=val]`, `[!attr]`, comma groups, descendant, negative index
- **Tree navigation:** `parent()`, `first_child()`, `last_child()`, `next_sibling()`, `prev_sibling()`, `find_ancestor_tag()`, `has_child()`, `children()`
- **Content access:** `innertext`, `outertext`, `plaintext`, `text()`, `xmltext()`, `makeup()`
- **camelCase DOM API:** `getElementById()`, `getElementsByTagName()`, `getAttribute()`, `setAttribute()`, `removeAttribute()`, `appendChild()`, `parentNode()`, `childNodes()`, `firstChild()`, `lastChild()`, `nextSibling()`, `previousSibling()`, `hasChildNodes()`, `nodeName()`, `getAllAttributes()`, `hasAttribute()`
- **Mutation:** attribute set/unset via `__set`/`__unset`, `innertext`/`outertext` property hooks
- **Output:** `save()`, `__toString()`
- **Callbacks:** `set_callback()`, `remove_callback()`
- **Settings:** `Settings::setMaxFilesize()`, `Settings::set()`, `Settings::get()`, `Settings::reset()`
- **Error handling:** `simple_html_dom_get_error()`, `Error` object
- **Memory:** `clear()`, destructor

## Approach / Architecture

### New Directory Structure

```
examples/                          # NEW directory (note: plural)
├── README.md                      # Central index with table of contents
├── _bootstrap.php                 # Shared bootstrap: Composer autoload + helper function
│
├── 01-getting-started/
│   ├── basic_selectors.php        # MIGRATED from example_basic_selector.php (rewritten with inline HTML)
│   ├── advanced_selectors.php     # MIGRATED from example_advanced_selector.php (cleaned up)
│   └── extract_text.php           # MIGRATED from example_extract_html.php (inline HTML)
│
├── 02-selectors/
│   ├── attribute_selectors.php    # NEW — [attr^=], [attr$=], [attr*=], [!attr], comma groups
│   ├── negative_index.php         # NEW — find('img', -1) to get last match
│   └── text_nodes.php             # NEW — find('text'), manipulating text nodes
│
├── 03-dom-navigation/
│   ├── tree_traversal.php         # NEW — parent, children, siblings, find_ancestor_tag
│   └── dom_api.php                # NEW — camelCase W3C-style API (getElementById, etc.)
│
├── 04-modifying-html/
│   ├── modify_content.php         # MIGRATED from example_modify_contents.php (inline HTML)
│   ├── attribute_manipulation.php # NEW — read/set/remove attributes, isset checks
│   └── save_to_file.php           # NEW — load → modify → save($filepath)
│
├── 05-practical-patterns/
│   ├── table_extraction.php       # NEW — extract HTML table to PHP array
│   ├── html_sanitization.php      # NEW — strip scripts, dangerous attrs, rewrite URLs
│   ├── form_extraction.php        # NEW — extract form fields, values, states
│   └── callbacks.php              # MIGRATED from example_callback.php (inline HTML)
│
└── 06-configuration/
    ├── error_handling.php          # NEW — detect and handle parse errors
    └── settings.php               # NEW — max filesize, custom settings
```

### Design Principles

1. **Self-contained** — every example uses inline HTML heredoc fixtures. Zero external HTTP calls.
2. **Composer autoload** — `_bootstrap.php` requires `vendor/autoload.php` via `__DIR__` resolution. No `include('../simple_html_dom.php')`.
3. **CLI-first output** — plain text output with `echo` and `PHP_EOL`. No HTML tags in output.
4. **Section headers** — each example starts with a file-level docblock explaining what it demonstrates, followed by clearly commented sections.
5. **Progressive complexity** — directories are numbered `01-` through `06-` to suggest a reading order.
6. **Both APIs shown** — where relevant, examples show both the legacy `find()`/snake_case API and the camelCase DOM API side by side.

### Bootstrap File (`_bootstrap.php`)

A minimal shared file that:
- Resolves and requires `vendor/autoload.php` from the project root.
- Defines a `section(string $title): void` helper that prints a section divider (e.g. `--- Title ---`) for readable CLI output.

### Legacy Directory

The existing `example/` directory is left in place (not deleted) to avoid breaking any external links or consumer references. A short `example/README.md` is added to redirect to `examples/`.

## Rationale

- **Plural `examples/` vs existing `example/`**: Using a new directory avoids migration risk. The old directory stays as a legacy reference. The plural form is the more common convention.
- **Numbered categories**: Users can browse sequentially from basics to advanced patterns. The numbering also gives filesystem sorting a natural reading order.
- **Inline HTML instead of live URLs**: Eliminates the dependency on external websites, making examples deterministic, offline-capable, and future-proof. This was the single biggest problem with the existing examples.
- **No utility file migration**: `simple_html_dom_utility.php` defines custom helper functions that aren't part of the library API. Its patterns (comment stripping, text-based filtering) are incorporated into the relevant new examples instead.
- **Separate `_bootstrap.php`**: Avoids repeating the autoload path resolution in every file while keeping each example independently runnable.

## Detailed Steps

### Phase 1: Infrastructure

1. **Create `examples/` directory** with the category subdirectories (`01-getting-started/` through `06-configuration/`).

2. **Create `examples/_bootstrap.php`**:
   - Resolve project root via `dirname(__DIR__)`.
   - Require `vendor/autoload.php`.
   - Define `section(string $title): void` helper for CLI output formatting.

3. **Create `examples/README.md`**:
   - Title and introduction explaining the example hub.
   - Table of contents listing every example with a one-line description.
   - Instructions on how to run examples (`php examples/01-getting-started/basic_selectors.php`).
   - Note about the legacy `example/` directory.

### Phase 2: Migrate Existing Examples (4 files)

4. **`examples/01-getting-started/basic_selectors.php`** — migrated from `example/example_basic_selector.php`:
   - Replace `file_get_html('http://www.google.com/')` with an inline HTML heredoc containing a representative HTML structure (links, images, divs with IDs, spans with classes, table with alignment attributes).
   - Use `str_get_html()` instead.
   - Replace `<br>` output with `PHP_EOL`.
   - Add Composer autoload via `_bootstrap.php`.

5. **`examples/01-getting-started/advanced_selectors.php`** — migrated from `example/example_advanced_selector.php`:
   - Already uses inline heredocs — clean up output formatting for CLI.
   - Add `_bootstrap.php` bootstrap.

6. **`examples/01-getting-started/extract_text.php`** — migrated from `example/example_extract_html.php`:
   - Replace `file_get_html` URL call with `str_get_html()` on inline HTML.
   - Show `plaintext` extraction.

7. **`examples/04-modifying-html/modify_content.php`** — migrated from `example/example_modify_contents.php`:
   - Replace URL loading wth inline HTML.
   - CLI-formatted output.

8. **`examples/05-practical-patterns/callbacks.php`** — migrated from `example/example_callback.php`:
   - Replace URL loading with inline HTML.
   - Show both `set_callback()` and `remove_callback()`.

### Phase 3: New Examples

9. **`examples/02-selectors/attribute_selectors.php`** — NEW:
   - Demonstrate `[attr^=val]` (starts-with), `[attr$=val]` (ends-with), `[attr*=val]` (contains), `[!attr]` (negated), comma-separated groups (`h1, h2, h3`).
   - Use a navigation/content HTML fixture with data attributes.

10. **`examples/02-selectors/negative_index.php`** — NEW:
    - Demonstrate `find('img', 0)` (first), `find('img', -1)` (last), out-of-bounds returns `null`.

11. **`examples/02-selectors/text_nodes.php`** — NEW:
    - Demonstrate `find('text')` to locate text nodes.
    - Show text node properties: `innertext`, `outertext`, `plaintext` are identical for text nodes.
    - Demonstrate targeted text replacement without disturbing markup.

12. **`examples/03-dom-navigation/tree_traversal.php`** — NEW:
    - Build a multi-level nested HTML structure.
    - Demonstrate: `first_child()`, `last_child()`, `next_sibling()`, `prev_sibling()`, `parent()`, `has_child()`, `children()`, `children($idx)`, `find_ancestor_tag()`.
    - Show a practical pattern: "find a heading, then get its next sibling for the content."

13. **`examples/03-dom-navigation/dom_api.php`** — NEW:
    - Demonstrate the camelCase W3C-style API on the same HTML fixture.
    - Show: `getElementById()`, `getElementsByTagName()`, `getAttribute()`, `setAttribute()`, `removeAttribute()`, `hasAttribute()`, `getAllAttributes()`, `appendChild()`, `parentNode()`, `childNodes()`, `firstChild()`, `lastChild()`, `nextSibling()`, `previousSibling()`, `hasChildNodes()`, `nodeName()`.

14. **`examples/04-modifying-html/attribute_manipulation.php`** — NEW:
    - Demonstrate reading attributes (`$e->href`, `$e->getAttribute('href')`).
    - Setting attributes (`$e->href = '...'`, `$e->setAttribute('rel', 'nofollow')`).
    - Removing attributes (`unset($e->onclick)`, `$e->removeAttribute('onclick')`).
    - Checking existence (`isset($e->target)`, `$e->hasAttribute('target')`).

15. **`examples/04-modifying-html/save_to_file.php`** — NEW:
    - Load HTML, modify it, save to a temp file via `$dom->save($filepath)`.
    - Read back and verify.
    - Clean up the temp file.

16. **`examples/05-practical-patterns/table_extraction.php`** — NEW:
    - Parse an HTML table with `<thead>`/`<tbody>`.
    - Extract header row into keys.
    - Build an array of associative arrays (one per data row).
    - Print the resulting PHP structure.

17. **`examples/05-practical-patterns/html_sanitization.php`** — NEW:
    - Strip `<script>` and `<style>` elements.
    - Remove dangerous event-handler attributes (`onclick`, `onerror`, `onload`, etc.).
    - Remove `<iframe>` elements.
    - Show before/after HTML output.

18. **`examples/05-practical-patterns/form_extraction.php`** — NEW:
    - Parse a form with text inputs, selects, checkboxes, radio buttons, textareas.
    - Extract field names, types, values, and states (checked/selected).
    - Build a structured array of form field data.

19. **`examples/06-configuration/error_handling.php`** — NEW:
    - Demonstrate error on empty HTML (`str_get_html('')` returns `false`).
    - Demonstrate error on oversized HTML (set `Settings::setMaxFilesize()` low, then parse).
    - Show `simple_html_dom_get_error()` to retrieve the `Error` object.
    - Show `Error::getMessage()`, `Error::getCode()`, `__toString()`.
    - Reset settings after each demo.

20. **`examples/06-configuration/settings.php`** — NEW:
    - Demonstrate `Settings::setMaxFilesize()` / `Settings::getMaxFilesize()`.
    - Demonstrate `Settings::set()` / `Settings::get()` with custom keys.
    - Demonstrate `Settings::reset()`.

### Phase 4: Legacy Directory Redirect

21. **Create `example/README.md`** — a short file redirecting to `examples/`:
    - State that these examples are legacy and preserved for backward compatibility.
    - Link to `examples/README.md` as the current example hub.

### Phase 5: Project Documentation Updates

22. **Update `docs/agents/project-manifest/file-tree.md`**:
    - Add the entire `examples/` tree.
    - Add note to the `example/` entry marking it as legacy.

## Dependencies

- Composer autoload (`vendor/autoload.php`) must be installed before running examples.
- No new Composer packages are required.
- No changes to `src/` or `tests/` are needed.

## Required Components

### New Files (22 files)

| File | Type |
|---|---|
| `examples/README.md` | Index document |
| `examples/_bootstrap.php` | Shared bootstrap |
| `examples/01-getting-started/basic_selectors.php` | Migrated example |
| `examples/01-getting-started/advanced_selectors.php` | Migrated example |
| `examples/01-getting-started/extract_text.php` | Migrated example |
| `examples/02-selectors/attribute_selectors.php` | New example |
| `examples/02-selectors/negative_index.php` | New example |
| `examples/02-selectors/text_nodes.php` | New example |
| `examples/03-dom-navigation/tree_traversal.php` | New example |
| `examples/03-dom-navigation/dom_api.php` | New example |
| `examples/04-modifying-html/modify_content.php` | Migrated example |
| `examples/04-modifying-html/attribute_manipulation.php` | New example |
| `examples/04-modifying-html/save_to_file.php` | New example |
| `examples/05-practical-patterns/table_extraction.php` | New example |
| `examples/05-practical-patterns/html_sanitization.php` | New example |
| `examples/05-practical-patterns/form_extraction.php` | New example |
| `examples/05-practical-patterns/callbacks.php` | Migrated example |
| `examples/06-configuration/error_handling.php` | New example |
| `examples/06-configuration/settings.php` | New example |
| `example/README.md` | Legacy redirect notice |

### Modified Files (1 file)

| File | Change |
|---|---|
| `docs/agents/project-manifest/file-tree.md` | Add `examples/` tree, mark `example/` as legacy |

## Assumptions

- The existing `example/` directory is kept intact (no deletions).
- Examples are intended to be run from the CLI via `php examples/<path>/<file>.php` from the project root.
- Consumers have run `composer install` before running examples.
- All HTML fixtures are inline heredocs — no external files or URLs are needed.
- The `_bootstrap.php` file is the only shared dependency between examples.

## Constraints

- Examples must use the library's public API only (both legacy and namespaced).
- Examples must not import any classes that aren't part of the library's public surface.
- Examples must work on PHP 8.4+.
- Every example must be independently runnable (include `_bootstrap.php` at the top).
- Output must be CLI-friendly (no HTML in output).
- No modifications to `src/`, `tests/`, or `composer.json`.

## Out of Scope

- Deleting or modifying the legacy `example/` directory files (they remain as-is).
- Updating the HTML manual in `manual/`.
- Updating `README.md` in the project root to reference the new examples directory (can be a follow-up).
- Adding a Composer script to run examples.
- Performance benchmarks or profiling examples.
- Examples for `load_file()` / `file_get_html()` with actual URLs (all examples use inline HTML).

## Acceptance Criteria

- All 18 PHP example files exist under `examples/` and are syntactically valid PHP.
- Every example can be executed via `php examples/<category>/<file>.php` from the project root and produces meaningful CLI output without errors or warnings.
- `examples/README.md` exists and lists every example with its category and a one-line description.
- `examples/_bootstrap.php` correctly resolves and loads Composer autoload.
- No example makes HTTP requests to external URLs.
- No example uses `include('../simple_html_dom.php')` — all use Composer autoload.
- The migrated examples produce equivalent functionality to their originals (same API features demonstrated) but with inline HTML fixtures and CLI-formatted output.
- `example/README.md` exists and redirects to the new hub.
- `docs/agents/project-manifest/file-tree.md` reflects the new `examples/` directory structure.
- `composer analyze` (PHPStan) continues to pass (examples are outside PHPStan scope, but changes to `file-tree.md` must be syntactically valid).
- `composer test` continues to pass (no source or test files are modified).

## Testing Strategy

- **Smoke test**: Run each example file individually from the project root:
  ```bash
  for f in examples/**/*.php; do echo "=== $f ===" && php "$f" && echo "OK" || echo "FAIL"; done
  ```
  Every file must exit with code 0 and produce non-empty output.
- **Lint check**: Run `php -l` on every example file to verify syntax.
- **Existing suite**: Run `composer test` to confirm no regressions in the library itself.
- **Static analysis**: Run `composer analyze` to confirm no regressions.

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **`_bootstrap.php` path resolution fails when run from different working directories** | Use `dirname(__DIR__)` relative to the bootstrap file's own `__DIR__`, not `getcwd()`. Document that examples should be run from the project root. |
| **Inline HTML fixtures become verbose and hard to read** | Keep fixtures minimal — only include the HTML elements needed to demonstrate the feature. Use realistic but compact structures. |
| **Consumers confuse `example/` and `examples/`** | Add redirect `README.md` in legacy `example/` directory. Reference only `examples/` in any new documentation. |
| **Examples drift out of sync with API changes** | Each example exercises public API methods that are covered by tests. If a method signature changes, the corresponding test will fail, signaling that examples may need updating. |
| **`save_to_file.php` leaves temp files on disk** | Use `sys_get_temp_dir()` and `unlink()` in the example. Add a comment explaining the cleanup. |
