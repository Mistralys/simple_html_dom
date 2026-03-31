# Work Packages — Example Hub

> **Plan:** `docs/agents/plans/2026-03-26-example-hub/plan.md`
> **Generated:** 2026-03-26
> **Total WPs:** 9

---

## WP-001 — Infrastructure: Directory Structure and Bootstrap

**Description:** Create the `examples/` directory with all six numbered category subdirectories and the shared `_bootstrap.php` file. This establishes the foundation that every subsequent WP depends on.

**Scope:**
- `examples/` — top-level directory (new)
- `examples/01-getting-started/` — subdirectory (new)
- `examples/02-selectors/` — subdirectory (new)
- `examples/03-dom-navigation/` — subdirectory (new)
- `examples/04-modifying-html/` — subdirectory (new)
- `examples/05-practical-patterns/` — subdirectory (new)
- `examples/06-configuration/` — subdirectory (new)
- `examples/_bootstrap.php` — shared bootstrap file (new): resolves project root via `dirname(__DIR__)`, requires `vendor/autoload.php`, defines `section(string $title): void` helper for CLI output formatting

**Deliverables:**
- 7 directories created under `examples/`
- `_bootstrap.php` file that loads Composer autoload and provides the `section()` helper

**Acceptance Criteria:**
1. `examples/` and all six numbered subdirectories exist
2. `php examples/_bootstrap.php` executes without errors (exit code 0)
3. The `section()` function is globally available after requiring `_bootstrap.php` and prints a formatted CLI divider
4. `_bootstrap.php` resolves `vendor/autoload.php` using `dirname(__DIR__)`, not `getcwd()` or hardcoded paths
5. No `include('../simple_html_dom.php')` patterns are used

**Estimated Complexity:** Low

**Notes:** Every other WP depends on this one. Must be completed first.

---

## WP-002 — Getting Started Examples (Migration)

**Description:** Migrate three existing example files into the `01-getting-started/` category. Each file is rewritten to use Composer autoloading via `_bootstrap.php`, inline HTML heredoc fixtures (replacing all external URL fetches), and CLI-formatted output (no HTML tags in output).

**Scope:**
- `examples/01-getting-started/basic_selectors.php` — migrated from `example/example_basic_selector.php`: replace `file_get_html('http://www.google.com/')` with `str_get_html()` on inline HTML; replace `<br>` output with `PHP_EOL`
- `examples/01-getting-started/advanced_selectors.php` — migrated from `example/example_advanced_selector.php`: already uses inline heredocs; clean up output formatting for CLI; add `_bootstrap.php` bootstrap
- `examples/01-getting-started/extract_text.php` — migrated from `example/example_extract_html.php`: replace URL-based loading with `str_get_html()` on inline HTML; demonstrate `plaintext` extraction

**Deliverables:**
- 3 PHP example files in `examples/01-getting-started/`
- Each file demonstrates equivalent functionality to its original but with inline fixtures and CLI output

**Acceptance Criteria:**
1. All 3 files pass `php -l` syntax check
2. `php examples/01-getting-started/basic_selectors.php` runs without errors and produces non-empty CLI output demonstrating tag, `#id`, `.class`, and attribute selectors
3. `php examples/01-getting-started/advanced_selectors.php` runs without errors and produces non-empty CLI output
4. `php examples/01-getting-started/extract_text.php` runs without errors and demonstrates `plaintext` extraction
5. No file makes HTTP requests to external URLs
6. All files include `_bootstrap.php` for autoloading (no `include('../simple_html_dom.php')`)
7. No HTML tags (`<br>`, `<hr>`, etc.) appear in output

**Estimated Complexity:** Medium

**Notes:** Depends on WP-001. Source files to reference: `example/example_basic_selector.php`, `example/example_advanced_selector.php`, `example/example_extract_html.php`.

---

## WP-003 — Selector Examples (New)

**Description:** Create three new example files in the `02-selectors/` category demonstrating attribute selectors, negative index access, and text node selection — API features that have no existing examples.

**Scope:**
- `examples/02-selectors/attribute_selectors.php` — NEW: demonstrate `[attr^=val]`, `[attr$=val]`, `[attr*=val]`, `[!attr]`, comma-separated groups; use a navigation/content HTML fixture with data attributes
- `examples/02-selectors/negative_index.php` — NEW: demonstrate `find('img', 0)` (first), `find('img', -1)` (last), out-of-bounds returns `null`
- `examples/02-selectors/text_nodes.php` — NEW: demonstrate `find('text')`, text node properties (`innertext`/`outertext`/`plaintext` identical for text nodes), targeted text replacement without disturbing markup

**Deliverables:**
- 3 PHP example files in `examples/02-selectors/`

**Acceptance Criteria:**
1. All 3 files pass `php -l` syntax check
2. `attribute_selectors.php` demonstrates all five attribute selector variants (`^=`, `$=`, `*=`, `!`, comma groups) with visible output for each
3. `negative_index.php` demonstrates first-match, last-match, and null-on-out-of-bounds behaviors
4. `text_nodes.php` demonstrates `find('text')` and text node manipulation
5. All files use inline HTML heredoc fixtures — no external HTTP calls
6. All files include `_bootstrap.php` and produce CLI-formatted output

**Estimated Complexity:** Medium

**Notes:** Depends on WP-001. Verify attribute selector support against constraints: `[attr^=val]`, `[attr$=val]`, `[attr*=val]`, `[!attr]`, and comma groups are all confirmed supported in `constraints.md`.

---

## WP-004 — DOM Navigation Examples (New)

**Description:** Create two new example files in the `03-dom-navigation/` category demonstrating the tree traversal API (snake_case) and the camelCase W3C-style DOM API. These cover a major API surface gap.

**Scope:**
- `examples/03-dom-navigation/tree_traversal.php` — NEW: multi-level nested HTML structure; demonstrate `first_child()`, `last_child()`, `next_sibling()`, `prev_sibling()`, `parent()`, `has_child()`, `children()`, `children($idx)`, `find_ancestor_tag()`; include practical pattern (find heading, get next sibling for content)
- `examples/03-dom-navigation/dom_api.php` — NEW: demonstrate the camelCase API on the same or similar fixture; cover `getElementById()`, `getElementsByTagName()`, `getAttribute()`, `setAttribute()`, `removeAttribute()`, `hasAttribute()`, `getAllAttributes()`, `appendChild()`, `parentNode()`, `childNodes()`, `firstChild()`, `lastChild()`, `nextSibling()`, `previousSibling()`, `hasChildNodes()`, `nodeName()`

**Deliverables:**
- 2 PHP example files in `examples/03-dom-navigation/`

**Acceptance Criteria:**
1. Both files pass `php -l` syntax check
2. `tree_traversal.php` demonstrates all 9 navigation methods listed in scope and produces clear CLI output showing the traversal results
3. `dom_api.php` demonstrates at least 12 of the 16 camelCase DOM methods listed in scope
4. Both files use inline HTML heredoc fixtures — no external HTTP calls
5. Both files include `_bootstrap.php` and produce CLI-formatted output
6. Where applicable, examples show both the legacy and camelCase API for the same operation

**Estimated Complexity:** Medium

**Notes:** Depends on WP-001. The `dom_api.php` file has the largest method coverage surface of any single example. Verify method signatures against `api-surface.md` before implementation.

---

## WP-005 — Modifying HTML Examples (Mixed)

**Description:** Create three example files in the `04-modifying-html/` category: one migrated from the existing modify example, plus two new examples covering attribute manipulation and save-to-file — both previously undocumented features.

**Scope:**
- `examples/04-modifying-html/modify_content.php` — migrated from `example/example_modify_contents.php`: replace URL loading with inline HTML; CLI-formatted output
- `examples/04-modifying-html/attribute_manipulation.php` — NEW: demonstrate reading attributes (`$e->href`, `getAttribute()`), setting (`$e->href = '...'`, `setAttribute()`), removing (`unset($e->onclick)`, `removeAttribute()`), checking existence (`isset($e->target)`, `hasAttribute()`)
- `examples/04-modifying-html/save_to_file.php` — NEW: load HTML, modify, save to temp file via `$dom->save($filepath)`, read back and verify, clean up temp file

**Deliverables:**
- 3 PHP example files in `examples/04-modifying-html/`

**Acceptance Criteria:**
1. All 3 files pass `php -l` syntax check
2. `modify_content.php` demonstrates content modification equivalent to the original example, with inline HTML and CLI output
3. `attribute_manipulation.php` demonstrates all four attribute operations (read, set, remove, existence check) using both legacy property access and camelCase methods
4. `save_to_file.php` creates a temp file via `sys_get_temp_dir()`, saves modified HTML, reads it back to verify, and cleans up with `unlink()`
5. `save_to_file.php` leaves no temp files behind after execution
6. All files use inline HTML fixtures, no external HTTP calls
7. All files include `_bootstrap.php` and produce CLI-formatted output

**Estimated Complexity:** Medium

**Notes:** Depends on WP-001. Source file to reference for migration: `example/example_modify_contents.php`. The `save_to_file.php` example should use `sys_get_temp_dir()` to avoid filesystem issues across OS environments.

---

## WP-006 — Practical Patterns Examples (Mixed)

**Description:** Create four example files in the `05-practical-patterns/` category: one migrated callback example plus three new examples demonstrating common real-world patterns (table extraction, HTML sanitization, form extraction).

**Scope:**
- `examples/05-practical-patterns/callbacks.php` — migrated from `example/example_callback.php`: replace URL loading with inline HTML; demonstrate both `set_callback()` and `remove_callback()`
- `examples/05-practical-patterns/table_extraction.php` — NEW: parse HTML table with `<thead>`/`<tbody>`; extract header row as keys; build array of associative arrays per data row; print resulting structure
- `examples/05-practical-patterns/html_sanitization.php` — NEW: strip `<script>` and `<style>` elements; remove dangerous event-handler attributes (`onclick`, `onerror`, `onload`, etc.); remove `<iframe>` elements; show before/after HTML
- `examples/05-practical-patterns/form_extraction.php` — NEW: parse form with text inputs, selects, checkboxes, radio buttons, textareas; extract field names, types, values, states; build structured array

**Deliverables:**
- 4 PHP example files in `examples/05-practical-patterns/`

**Acceptance Criteria:**
1. All 4 files pass `php -l` syntax check
2. `callbacks.php` demonstrates both `set_callback()` and `remove_callback()` with visible before/after output
3. `table_extraction.php` parses a multi-row table and outputs a structured PHP array (via `print_r` or similar)
4. `html_sanitization.php` removes script, style, and iframe elements plus event-handler attributes, showing before and after HTML
5. `form_extraction.php` handles at least 4 form field types (text input, select, checkbox, textarea) and outputs field metadata
6. All files use inline HTML fixtures — no external HTTP calls
7. All files include `_bootstrap.php` and produce CLI-formatted output

**Estimated Complexity:** High

**Notes:** Depends on WP-001. This is the largest WP by file count (4 files) and pattern diversity. The sanitization example should be careful to demonstrate the library's capabilities without implying it's a complete security sanitizer — keep language factual. Source file to reference for migration: `example/example_callback.php`. Note the constraint from `constraints.md`: `tbody` selectors are silently skipped; the `table_extraction.php` example should navigate table rows without relying on an explicit `tbody` selector.

---

## WP-007 — Configuration Examples (New)

**Description:** Create two new example files in the `06-configuration/` category demonstrating error handling and settings management — the two configuration-related API features with no existing examples.

**Scope:**
- `examples/06-configuration/error_handling.php` — NEW: demonstrate error on empty HTML (`str_get_html('')` returns `false`); error on oversized HTML (set `Settings::setMaxFilesize()` low, then parse); `simple_html_dom_get_error()` to retrieve `Error` object; `Error::getMessage()`, `Error::getCode()`, `__toString()`; call `Settings::reset()` after each demo
- `examples/06-configuration/settings.php` — NEW: demonstrate `Settings::setMaxFilesize()` / `Settings::getMaxFilesize()`; `Settings::set()` / `Settings::get()` with custom keys; `Settings::reset()`

**Deliverables:**
- 2 PHP example files in `examples/06-configuration/`

**Acceptance Criteria:**
1. Both files pass `php -l` syntax check
2. `error_handling.php` triggers and displays at least 2 distinct error conditions (empty HTML, oversized HTML)
3. `error_handling.php` demonstrates `Error::getMessage()`, `Error::getCode()`, and `__toString()` on the error object
4. `error_handling.php` calls `Settings::reset()` after each error demonstration to avoid cross-contamination
5. `settings.php` demonstrates get/set/reset for both max filesize and custom keys
6. Both files include `_bootstrap.php` and produce CLI-formatted output
7. Error codes used match the documented codes in `constraints.md`: `1001` (empty), `1002` (oversized)

**Estimated Complexity:** Medium

**Notes:** Depends on WP-001. Must call `Settings::reset()` to clean up state — this mirrors the test convention documented in `constraints.md`.

---

## WP-008 — Documentation: Examples Hub README and Legacy Redirect

**Description:** Create the central `examples/README.md` index that catalogues every example with its category and a one-line description, and create the `example/README.md` redirect notice in the legacy directory pointing users to the new hub.

**Scope:**
- `examples/README.md` — NEW: title and introduction; table of contents listing every example with category and one-line description; instructions on running examples (`php examples/<category>/<file>.php` from project root); note about legacy `example/` directory; note about Composer autoload prerequisite
- `example/README.md` — NEW: short file stating these examples are legacy and preserved for backward compatibility; link to `examples/README.md` as the current example hub

**Deliverables:**
- `examples/README.md` — complete index document
- `example/README.md` — legacy redirect notice

**Acceptance Criteria:**
1. `examples/README.md` lists all 18 PHP example files organized by category
2. `examples/README.md` includes a run instruction showing the correct CLI invocation pattern
3. `examples/README.md` mentions the Composer autoload prerequisite
4. `example/README.md` exists and contains a clear redirect/link to `examples/README.md`
5. `example/README.md` states the legacy examples are preserved for backward compatibility
6. Both files are valid Markdown

**Estimated Complexity:** Low

**Notes:** Depends on WP-002 through WP-007 (needs to know all example file names and descriptions). Can be started in parallel using the plan's file list, but should be reviewed after all example WPs are complete. The `example/README.md` has no dependencies and could be created at any time.

---

## WP-009 — Project Manifest: File Tree Update

**Description:** Update `docs/agents/project-manifest/file-tree.md` to add the complete `examples/` directory tree and mark the existing `example/` entry as legacy. This keeps the project manifest in sync per the AGENTS.md maintenance rules.

**Scope:**
- `docs/agents/project-manifest/file-tree.md` — MODIFIED: add the full `examples/` tree (README.md, _bootstrap.php, 6 category directories, 18 PHP files); add "(legacy)" annotation to the existing `example/` entry

**Deliverables:**
- Updated `file-tree.md` with the `examples/` directory tree and legacy annotation on `example/`

**Acceptance Criteria:**
1. `file-tree.md` contains the complete `examples/` tree matching the actual directory structure
2. `file-tree.md` annotates the `example/` entry as legacy (e.g., `example/ # Legacy examples (see examples/)`)
3. The file tree syntax is valid Markdown with consistent indentation
4. `composer analyze` continues to pass (no regressions from documentation changes)
5. `composer test` continues to pass (no source or test files modified)

**Estimated Complexity:** Low

**Notes:** Depends on all prior WPs being finalized (the tree must reflect the actual structure). Per AGENTS.md maintenance rules, file-tree.md must be updated when files or directories are added.

---

## Dependency Summary

```
WP-001 (Infrastructure)
  ├── WP-002 (Getting Started)
  ├── WP-003 (Selectors)
  ├── WP-004 (DOM Navigation)
  ├── WP-005 (Modifying HTML)
  ├── WP-006 (Practical Patterns)
  └── WP-007 (Configuration)
        │
        ├── WP-008 (READMEs) — depends on WP-002..WP-007 for complete file list
        └── WP-009 (File Tree) — depends on WP-002..WP-008 for final structure
```

WP-002 through WP-007 are independent of each other and can be implemented in parallel once WP-001 is complete.
