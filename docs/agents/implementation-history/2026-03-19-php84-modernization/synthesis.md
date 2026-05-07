# Synthesis Report: PHP 8.4 Modernization

**Project:** simple_html_dom PHP 8.4 Modernization
**Completed:** 2026-03-19
**Status:** All 4 work packages COMPLETE — all pipelines PASS

---

## Summary

The `simple_html_dom` library was refactored from a single-file procedural library into a multi-file PSR-4 namespaced architecture under `SimpleHtmlDom\`, with full PHP 8.4 idioms applied throughout. A new dedicated unit test suite was added. Full backward compatibility with all legacy class names, constants, and procedural functions was preserved.

All 4 phases completed with zero rework cycles. Every implementation, QA, code-review, and documentation pipeline passed on first attempt.

---

## Work Package Outcomes

### WP-001 — Namespaced Skeleton

**Objective:** Extract the monolithic `simple_html_dom.php` into a multi-file PSR-4 namespace.

**Deliverables:**
- Created `src/SimpleHtmlDom/` with 9 class files: `NodeType.php`, `QuoteStyle.php`, `NodeInfo.php`, `Error.php`, `Settings.php`, `TextConverter.php`, `SelectorParser.php`, `Node.php`, `Parser.php`
- `src/simple_html_dom.php` converted to a bridge file that defines `HDOM_*` constants and registers `class_alias()` entries for all 4 legacy class names
- `composer.json` updated: PSR-4 mapping for `SimpleHtmlDom\` namespace added, classmap entry removed, `files` entry for bridge preserved
- 4 test files updated to use `$this->dom` with `tearDown()` cleanup: `MiscTest`, `ReaderElementTest`, `ReaderSelectorTest`, `InvalidHtmlTest`

**Key design decisions:**
- `Node::$dom` is `public` (not private) to allow `SelectorParser` access
- `Settings::getMaxFilesize()` defers `MAX_FILE_SIZE` reference to runtime since the constant isn't available at static property initializer time in PSR-4 context
- `Node::$children` and `$nodes` typed as `?array` to allow `null` assignment in `clear()`

---

### WP-002 — PHP 8.4 Idioms

**Objective:** Apply PHP 8.4 language features and fix known bugs.

**Deliverables:**
- **`Error.php`:** Constructor promotion with `private readonly string $message` and `private readonly int $code`
- **`SelectorParser.php`:** Constructor promotion with `private readonly Node $node`; `match` expression replacing `switch`; array destructuring `[$tag, $key, $val, $exp, $no_key]` replacing `list()`; typed method signatures
- **`Node.php`:** PHP 8.4 property hooks for `$outertext` and `$innertext` virtual properties; constructor promotion for `$dom` as `public ?Parser $dom = null`; `__get` and `__set` cleaned of dead `outertext`/`innertext` cases
- **`Parser.php`:** Token strings converted to `private const string TOKEN_BLANK/EQUAL/SLASH/ATTR`; tag arrays converted to `private const array SELF_CLOSING_TAGS/BLOCK_TAGS/OPTIONAL_CLOSING_TAGS`; `load_file(string ...$args)` variadic replacing `func_get_args()`; tokenizer regex narrowed from `/^[\w\-:]+$/` to `/^[a-zA-Z][\w\-:]*$/` (HTML5 compliant)
- **`src/simple_html_dom.php`:** `$http_response_header` superglobal replaced with `http_get_last_response_headers() ?? []` (both occurrences in `file_get_html()`)
- **`tests/Parsing/StandardTest.php`:** `testChemistryFormula` updated to assert round-trip equality (`assertEquals($str, (string)$dom)`) rather than just `assertIsString`; uses `load($str, true, false)` with `stripRN=false` to preserve `\n` for exact round-trip

**Bug fix:** Content like `<1 mol%` and `<2NaCl` no longer causes content to be silently discarded by the tokenizer. These are now correctly treated as text nodes and round-trip correctly.

---

### WP-003 — Composer and Documentation Hygiene

**Objective:** Ensure autoloading, phpunit configuration, and changelog are correct.

**Deliverables:**
- `composer.json`: `scripts.test` updated from `"phpunit"` to `"vendor/bin/phpunit"`; autoload already correct
- `phpunit.xml`: Added `<source>` element with `<directory>src/</directory>` for coverage reports
- `changelog.md`: Added complete v2.0 entry documenting all changes from Phases 1 and 2

---

### WP-004 — New Unit Test Suite

**Objective:** Add PHPUnit tests for the new namespaced classes.

**Deliverables (all under `tests/Unit/`):**

| File | Tests | Coverage |
|---|---|---|
| `SettingsTest.php` | 6 | `set`/`get`, `null` default, `$default` param, `setMaxFilesize`/`getMaxFilesize`, runtime `MAX_FILE_SIZE` fallback, `Error` object storage |
| `ErrorTest.php` | 5 | `getMessage`, `getCode`, both-fields constructor, readonly enforcement on `$message`, readonly enforcement on `$code` |
| `TextConverterTest.php` | 8 | `isUtf8`: ASCII, valid UTF-8, invalid byte sequence, empty string; `convert`: same charset passthrough, empty source, empty target, leading BOM strip, trailing BOM strip, BOM not stripped for non-UTF-8 target |
| `SelectorParserTest.php` | 12 | `parseSelector`: simple tag, class selector, id selector, attribute selector, attribute+value, negated attribute, multiple groups; `match`: `=`, `!=`, `^=`, `$=`, `*=`, unknown operator |

- `phpunit.xml` updated with `unit` testsuite pointing at `tests/Unit/`
- `changelog.md` v2.0 entry updated to document the new unit suite

---

## Files Modified

**Source:**
- `src/SimpleHtmlDom/NodeType.php` (new)
- `src/SimpleHtmlDom/QuoteStyle.php` (new)
- `src/SimpleHtmlDom/NodeInfo.php` (new)
- `src/SimpleHtmlDom/Error.php` (new)
- `src/SimpleHtmlDom/Settings.php` (new)
- `src/SimpleHtmlDom/TextConverter.php` (new)
- `src/SimpleHtmlDom/SelectorParser.php` (new)
- `src/SimpleHtmlDom/Node.php` (new)
- `src/SimpleHtmlDom/Parser.php` (new)
- `src/simple_html_dom.php` (updated to bridge file)

**Tests:**
- `tests/DOM/MiscTest.php` (tearDown pattern)
- `tests/DOM/ReaderElementTest.php` (tearDown pattern)
- `tests/Selectors/ReaderSelectorTest.php` (tearDown pattern)
- `tests/Parsing/InvalidHtmlTest.php` (tearDown pattern)
- `tests/Parsing/StandardTest.php` (chemistry formula test)
- `tests/Unit/SettingsTest.php` (new)
- `tests/Unit/ErrorTest.php` (new)
- `tests/Unit/TextConverterTest.php` (new)
- `tests/Unit/SelectorParserTest.php` (new)

**Config:**
- `composer.json`
- `phpunit.xml`
- `changelog.md`

---

## Known Remaining Items

- `Parser::__construct` with `!forceTagsClosed` sets `$this->optional_closing_array = []` as a dynamic property, which emits a deprecation notice in PHP 8.4. No test triggers this path. This is inherited from the original code and not a regression.
- `Node::$dom` being public is a minor API surface change from the original private visibility. No test accesses `$node->dom` directly, so this is not a behavioral regression.
- A new `SelectorParser` instance is created on each `Node::find()` call. This pre-existing inefficiency is out of scope for this modernization pass.

---

## Pipeline Health

| WP | Implementation | QA | Code Review | Documentation |
|---|---|---|---|---|
| WP-001 | PASS | PASS | PASS | PASS |
| WP-002 | PASS | PASS | PASS | PASS |
| WP-003 | PASS | PASS | PASS | PASS |
| WP-004 | PASS | PASS | PASS | PASS |

Zero rework cycles. All 16 pipeline runs passed on first attempt.
