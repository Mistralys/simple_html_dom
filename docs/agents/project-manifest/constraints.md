# Constraints & Conventions

## PHP Version

- **Minimum PHP 8.4**: The codebase uses property hooks, `http_get_last_response_headers()`, and other 8.4-only features. Earlier PHP versions will not work.

## Backward Compatibility

- The bridge file (`src/simple_html_dom.php`) **must** be maintained. It defines:
  - All `HDOM_*` global constants (pointing to enum values)
  - `class_alias()` mappings for the four legacy class names
  - Four procedural functions (`file_get_html`, `str_get_html`, `simple_html_dom_get_error`, `dump_html_tree`)
- Legacy consumer code using `new simple_html_dom()` or `file_get_html()` must continue to work without modification.

## Autoloading

- PSR-4 namespace `SimpleHtmlDom\` maps to `src/SimpleHtmlDom/`.
- `src/simple_html_dom.php` is loaded via Composer `files` autoload (always loaded, defines constants and aliases).

## Max File Size

- Default: 600,000 bytes (`MAX_FILE_SIZE` constant).
- Can be changed at runtime via `Settings::setMaxFilesize($bytes)`.
- Both `file_get_html()` and `str_get_html()` enforce this limit before parsing.

## URL Loading Security

- `file_get_html()` and `Parser::load_file()` accept arbitrary URLs and pass them to `file_get_contents()`.
- If consumer code passes user-supplied URLs, this creates a Server-Side Request Forgery (SSRF) surface.
- Consumers **must** validate/whitelist URLs before passing them to these functions.
- The library intentionally does not restrict URLs — that is the consumer's responsibility.
- `file_get_html()` follows HTTP redirects up to a maximum of 5 hops to prevent infinite redirect loops.

## Error Handling

- Parse-time errors (empty content, oversized content, bad HTTP response) are stored in the static `Settings` store under key `__error` as an `Error` object.
- Errors are **not** thrown as exceptions. Consumer code must call `simple_html_dom_get_error()` to check.
- Error codes: `1001` (empty HTML), `1002` (oversized HTML), `1003` (bad HTTP response code).

## Memory Management

- PHP circular references between `Parser`, `Node`, and child nodes cause memory leaks. Always call `$dom->clear()` when done, or ensure the `Parser` goes out of scope (the destructor calls `clear()`).
- `Node::clear()` nulls out `$dom`, `$nodes`, `$parent`, `$children`.

## Noise Handling

- Before parsing, the tokeniser strips comments, CDATA, `<script>`, `<style>`, `<code>`, PHP tags, and Smarty tags into a `$noise[]` array keyed by placeholder strings (`___noise___XXXXX`).
- `restore_noise()` re-inserts them on output. Any code that manipulates raw `_[HDOM_INFO_TEXT]` values may encounter these placeholders.

## CSS Selector Limitations

- Supports: tag, `#id`, `.class`, `[attr]`, `[attr=val]`, `[attr!=val]`, `[attr^=val]`, `[attr$=val]`, `[attr*=val]`, `[!attr]`, comma-separated groups, descendant combinators.
- Does **not** support: child combinator (`>`), sibling combinators (`+`, `~`), pseudo-classes (`:nth-child`, `:not`, etc.), pseudo-elements.
- `tbody` selectors are silently skipped (browser-generated XPath compatibility).

## Tag Parsing Rules

- Self-closing tags: `img`, `br`, `input`, `meta`, `link`, `hr`, `base`, `embed`, `spacer`.
- Block tags (for end-tag recovery): `root`, `body`, `form`, `div`, `span`, `table`.
- Optional closing tags are auto-closed when a sibling of the same type opens: `tr`, `th`, `td`, `li`, `dt`, `dd`, `dl`, `p`, `nobr`, `b`, `option`.
- `<` followed by a digit is treated as plain text, not a tag opener (HTML5 compliant).

## Test Organisation

| Suite | Directory | Purpose |
|---|---|---|
| `unit` | `tests/Unit/` | Pure unit tests for namespaced classes |
| `parsing` | `tests/Parsing/` | Parsing fidelity via the legacy bridge API |
| `selectors` | `tests/Selectors/` | CSS selector engine tests |
| `dom` | `tests/DOM/` | DOM-level integration tests |

- Tests use `Tests\` PSR-4 namespace mapped to `tests/`.
- `Settings::reset()` should be called in `tearDown()` to avoid cross-test contamination.

## Code Style

- Snake_case method names in legacy API (`find_ancestor_tag`, `first_child`, `load_file`).
- camelCase delegate methods provided for DOM-like API (`firstChild`, `loadFile`, `getElementById`).
- Both naming styles are maintained; do not remove either.

## No External Parser

- The library implements its own character-stream tokeniser. It does **not** use `DOMDocument`, `libxml`, or any external parsing library.
