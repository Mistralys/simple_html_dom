# Plan: Migrate Manual Tests to PHPUnit

## Summary

Replace the existing assert-based manual test suite under `testcase/` (including the `testcase/reader/` subfolder) with a proper PHPUnit test suite. The library's public API remains unchanged. All ~500+ existing assertions across 10 test case files will be converted to equivalent PHPUnit assertions organized into well-structured test classes under a new `tests/` directory, grouped into test suites by library responsibility.

## Architectural Context

### Library Structure
- **Single source file:** `src/simple_html_dom.php` (~1917 lines)
- **Classes:** `simple_html_dom_node`, `simple_html_dom`, `simple_html_dom_settings`, `simple_html_dom_error`
- **Global helper functions:** `file_get_html()`, `str_get_html()`, `simple_html_dom_get_error()`, `dump_html_tree()`
- **Constants:** `HDOM_TYPE_*`, `HDOM_QUOTE_*`, `HDOM_INFO_*`, `DEFAULT_*`, `MAX_FILE_SIZE`

### Existing Test Files

#### Root test files (8 files, ~2539 lines total)
All under `testcase/`, all use PHP's native `assert()` function, no test framework:

| File | Lines | Coverage Domain |
|------|-------|----------------|
| `callback_testcase.php` | 73 | Callback mechanism: `set_callback()`, `remove_callback()` |
| `dom_testcase.php` | 385 | DOM tree traversal: `first_child()`, `last_child()`, `next_sibling()`, `prev_sibling()`, no-value attrs, attr removal, camelCase API aliases |
| `element_testcase.php` | 246 | Element manipulation: `innertext`, `outertext`, replacement, nested replacement, `<p>` parsing, `<embed>` |
| `invalid_testcase.php` | 657 | Malformed HTML resilience: optional closing tags, broken markup, invalid `<`/`>`, nested errors |
| `misc_testcase.php` | 59 | Last-element bug, error-tag handling, `save()` |
| `selector_testcase.php` | 741 | CSS selector engine: wildcard, tag, class, id, attribute selectors (`=`, `!=`, `^=`, `$=`, `*=`), multiple selectors, nested selectors, `[]` names, regex, multiple class, comma-separated selectors, negation (`!`), namespaces |
| `std_testcase.php` | 242 | Edge cases: empty/null input, string quotes, clone, monkey testing, random strings, lowercase mode |
| `strip_testcase.php` | 136 | Noise stripping: comments, `<code>`, `<pre>`, `<script>`, `<style>`, PHP short tags |

#### Reader test files (2 files, ~825 lines total)
Under `testcase/reader/`, these reference a non-existent `simple_html_dom_reader.php` and `str_get_dom()`:

| File | Lines | Coverage Domain |
|------|-------|----------------|
| `element_testcase.php` | 242 | Element manipulation via the reader variant: innertext/outertext, replacement, nested replacement, `<p>`, `<embed>`, `<pre>`, `<code>` |
| `selector_testcase.php` | 583 | Selector engine via the reader variant: tag/class/id/attr selectors, attribute comparisons, text selectors, namespaces, user-defined tags, nested/multiple selectors, `[]` names, regex, multiple class, comma-separated selectors |

> **Note:** The reader tests import `simple_html_dom_reader.php` which does not exist in the current codebase. They also call `str_get_dom()` (not `str_get_html()`). These tests will be converted to test the main `simple_html_dom` class instead. Assertions whose expected values differ due to whitespace normalization differences (e.g., the reader appears to strip extra spaces in attributes and normalize self-closing tags) will be adjusted to match the actual behavior of the main library's parser. Some commented-out assertions (features not supported in the reader) will be omitted.

### Autoloading
- `composer.json` uses `classmap` + `files` autoloading for `src/`
- PHP requirement: `^8.4` (runtime is PHP 8.5.4)
- No dev dependencies currently

## Approach / Architecture

### Test Suite Organization

Tests are organized into **four suites** reflecting the library's distinct responsibilities:

| Suite | Responsibility | Test Classes |
|-------|---------------|--------------|
| **Parsing** | HTML parsing, noise stripping, malformed/invalid HTML resilience | `StripTest`, `InvalidHtmlTest`, `StandardTest` |
| **Selectors** | CSS selector engine (find, seek, match, parse_selector) | `SelectorTest`, `ReaderSelectorTest` |
| **DOM** | DOM tree traversal, element access, reading/writing properties, callbacks | `DomTreeTest`, `ElementTest`, `ReaderElementTest`, `CallbackTest`, `MiscTest` |

### Directory Layout
```
tests/
├── Parsing/
│   ├── InvalidHtmlTest.php
│   ├── StandardTest.php
│   └── StripTest.php
├── Selectors/
│   ├── SelectorTest.php
│   └── ReaderSelectorTest.php
└── DOM/
    ├── CallbackTest.php
    ├── DomTreeTest.php
    ├── ElementTest.php
    ├── MiscTest.php
    └── ReaderElementTest.php
phpunit.xml
```

### Mapping: Old → New

| Old File | Suite | New PHPUnit Class | Namespace |
|----------|-------|-------------------|-----------|
| `callback_testcase.php` | DOM | `CallbackTest` | `Tests\DOM` |
| `dom_testcase.php` | DOM | `DomTreeTest` | `Tests\DOM` |
| `element_testcase.php` | DOM | `ElementTest` | `Tests\DOM` |
| `misc_testcase.php` | DOM | `MiscTest` | `Tests\DOM` |
| `reader/element_testcase.php` | DOM | `ReaderElementTest` | `Tests\DOM` |
| `selector_testcase.php` | Selectors | `SelectorTest` | `Tests\Selectors` |
| `reader/selector_testcase.php` | Selectors | `ReaderSelectorTest` | `Tests\Selectors` |
| `invalid_testcase.php` | Parsing | `InvalidHtmlTest` | `Tests\Parsing` |
| `std_testcase.php` | Parsing | `StandardTest` | `Tests\Parsing` |
| `strip_testcase.php` | Parsing | `StripTest` | `Tests\Parsing` |

### Conversion Rules
1. Each `assert($a==$b)` → `$this->assertSame($b, $a)` or `$this->assertEquals($b, $a)` depending on strictness (use `===` original → `assertSame`, `==` original → `assertEquals`)
2. Each `assert($a===null)` → `$this->assertNull($a)`
3. Each `assert(count($x)==N)` → `$this->assertCount(N, $x)`
4. Each `assert(isset($x))` / `assert(!isset($x))` → `$this->assertTrue(isset($x))` / `$this->assertFalse(isset($x))`
5. Group related assertions into descriptive test methods with clear names
6. Use `setUp()` to instantiate a shared `simple_html_dom` instance where tests reuse it
7. Use `tearDown()` to call `clear()` and unset, mirroring the existing cleanup pattern
8. Callback functions (in `callback_testcase.php`) become private methods or closures within the test class

### PHPUnit Configuration
- Use PHPUnit 12.x (latest stable compatible with PHP 8.4+)
- Bootstrap via Composer autoloader (`vendor/autoload.php`)
- `phpunit.xml` at project root
- **Three named test suites** defined in `phpunit.xml`:
  - `parsing` → `tests/Parsing/`
  - `selectors` → `tests/Selectors/`
  - `dom` → `tests/DOM/`
- Allows running individual suites: `vendor/bin/phpunit --testsuite=selectors`

## Rationale

- **PHPUnit over Pest/Codeception:** PHPUnit is the PHP ecosystem standard, has no additional abstraction layers, and is the most commonly used framework in established projects. It requires minimal additional dependencies.
- **`tests/` directory (not replacing `testcase/`):** Keep the old `testcase/` directory intact during migration so tests can be compared side-by-side. The old directory can be removed in a follow-up after verifying all PHPUnit tests pass.
- **One-to-one file mapping:** Each old test file maps to exactly one new test class. This makes conversion validation straightforward — run the old assert-based tests, then run PHPUnit and compare coverage.
- **`setUp()`/`tearDown()` pattern:** Mirrors the existing `$dom = new simple_html_dom` / `$dom->clear(); unset($dom)` pattern in each test file.
- **Suite-per-responsibility:** Grouping by library responsibility (Parsing, Selectors, DOM) reflects the three distinct concerns of the library: (1) parsing raw HTML into a DOM tree, (2) querying the tree with CSS selectors, and (3) traversing/manipulating the DOM. This allows running subsets independently and makes it clear where new tests for future changes should go.
- **Reader tests adapted to main library:** Since `simple_html_dom_reader.php` does not exist, the reader tests are converted to test the main `simple_html_dom` class. Where the reader variant had different expected output (e.g., whitespace normalization in attributes, self-closing tag handling), assertions are adjusted to match the main library's actual behavior. Commented-out assertions in the reader files are omitted.

## Detailed Steps

1. **Add PHPUnit as a dev dependency**
   - Run `composer require --dev phpunit/phpunit:^12.0`
   - Add `autoload-dev` PSR-4 mapping for `tests/` namespace to `composer.json`

2. **Create `phpunit.xml` configuration**
   - Place at project root
   - Define three named test suites (`parsing`, `selectors`, `dom`) pointing to respective subdirectories
   - Set bootstrap to `vendor/autoload.php`
   - Set `colors="true"`, `stopOnFailure="false"`

3. **Create `tests/DOM/CallbackTest.php`**
   - Convert `testcase/callback_testcase.php` (73 lines, ~15 assertions)
   - Test methods: `testCallbackRemovesImgTags`, `testCallbackModifiesInnertext`, `testCallbackModifiesAttributes`, `testCallbackAddAttribute`, `testRemoveCallbackAndManualEdit`

4. **Create `tests/DOM/DomTreeTest.php`**
   - Convert `testcase/dom_testcase.php` (385 lines, ~80+ assertions)
   - Groups: empty DOM tree, single div, nested divs, sibling navigation, deep nesting, no-value attributes (checkboxes), attribute removal, plaintext extraction, camelCase DOM API aliases (`getElementById`, `getAttribute`, `firstChild`, etc.), list structure parsing

5. **Create `tests/DOM/ElementTest.php`**
   - Convert `testcase/element_testcase.php` (246 lines, ~60+ assertions)
   - Groups: innertext get/set, outertext read, replacement, table parsing, list parsing, nested replacement, `<p>` tag parsing, `<embed>` tag

6. **Create `tests/DOM/MiscTest.php`**
   - Convert `testcase/misc_testcase.php` (59 lines, ~15 assertions)
   - Groups: last-element-not-found bug, error tag handling, `save()` method

7. **Create `tests/DOM/ReaderElementTest.php`**
   - Convert `testcase/reader/element_testcase.php` (242 lines, ~60+ assertions)
   - Adapt from reader variant to test main `simple_html_dom` class (replace `require_once('../../simple_html_dom_reader.php')` usage)
   - Adjust assertions for whitespace differences where the reader normalized attribute spacing
   - Omit commented-out assertions (unsupported reader features)
   - Groups: attribute quoting, innertext/outertext, replacement, nested replacement, `<p>` tag with links, `<embed>`, `<pre>`, `<code>`

8. **Create `tests/Selectors/SelectorTest.php`**
   - Convert `testcase/selector_testcase.php` (741 lines, ~150+ assertions)
   - Groups: tab/newline in tags, wildcard selectors, tag/class/id/attr selectors, negative index, attribute value comparisons (`=`, `!=`, `^=`, `$=`, `*=`), text/plaintext selectors, XML namespaces, user-defined tags, multiple/nested selectors, `[]` array-style names, regex attribute values, multiple class matching, comma-separated selectors, negation attribute `[!attr]`, JS-style attribute values, dash in attribute names

9. **Create `tests/Selectors/ReaderSelectorTest.php`**
   - Convert `testcase/reader/selector_testcase.php` (583 lines, ~120+ assertions)
   - Adapt from reader variant to test main `simple_html_dom` class
   - Key differences from main selector tests: reader uses `str_get_dom()` → replace with `str_get_html()`, reader normalizes self-closing tag output (removes trailing ` />`), reader doesn't support XML namespace selectors (`bw:bizy` finds 0 vs 1), reader doesn't support `div::test` selectors
   - Adjust expected values to match main library behavior
   - Groups: tag/class/id/attr selectors, text selectors, user-defined tags, multiple/nested selectors, attribute comparisons, `[]` names, regex, multiple class, comma-separated selectors

10. **Create `tests/Parsing/InvalidHtmlTest.php`**
    - Convert `testcase/invalid_testcase.php` (657 lines, ~100+ assertions)
    - Groups: optional closing tags (`<tr>`, `<td>`, `<p>`, `<nobr>`, `<dt>`/`<dd>`, `<li>`), broken nesting, invalid `<` characters, invalid `>` characters, malformed attributes, BAD HTML edge cases

11. **Create `tests/Parsing/StandardTest.php`**
    - Convert `testcase/std_testcase.php` (242 lines, ~70+ assertions)
    - Groups: empty/null input, DOCTYPE handling, string quote handling, clone behavior, monkey tests (edge-case strings), random string fuzz, lowercase mode

12. **Create `tests/Parsing/StripTest.php`**
    - Convert `testcase/strip_testcase.php` (136 lines, ~20+ assertions)
    - Groups: HTML comment stripping, `<code>` tag treatment, `<pre>` + `<code>`, `<script>` + `<style>` stripping, PHP short tags, noise stripping with comments

13. **Run the full PHPUnit suite and verify all tests pass**
    - Execute `vendor/bin/phpunit` (all suites)
    - Execute each suite individually: `vendor/bin/phpunit --testsuite=parsing`, `--testsuite=selectors`, `--testsuite=dom`
    - Compare pass count against the number of old assertions
    - Fix any discrepancies

14. **Update `composer.json` scripts section** (optional convenience)
    - Add `"test": "phpunit"` script alias

## Dependencies

- PHPUnit ^12.0 (dev dependency, compatible with PHP ^8.4)
- Composer autoloader (already in place via `vendor/autoload.php`)

## Required Components

- **New files:**
  - `phpunit.xml` (project root)
  - `tests/DOM/CallbackTest.php`
  - `tests/DOM/DomTreeTest.php`
  - `tests/DOM/ElementTest.php`
  - `tests/DOM/MiscTest.php`
  - `tests/DOM/ReaderElementTest.php`
  - `tests/Selectors/SelectorTest.php`
  - `tests/Selectors/ReaderSelectorTest.php`
  - `tests/Parsing/InvalidHtmlTest.php`
  - `tests/Parsing/StandardTest.php`
  - `tests/Parsing/StripTest.php`

- **Modified files:**
  - `composer.json` — add `require-dev` for PHPUnit, add `autoload-dev` PSR-4 mapping, optional `scripts` section

- **Existing files (unchanged):**
  - `src/simple_html_dom.php` — library source, no modifications
  - `testcase/` — kept intact for reference during migration (including `testcase/reader/`)

## Assumptions

- PHPUnit 12.x is compatible with PHP 8.4+ (confirmed by PHPUnit's requirements)
- The existing `assert()` calls in testcase files represent the intended behavior — they serve as the ground truth for the new tests
- The random string fuzz test in `std_testcase.php` can be converted to a PHPUnit data provider or loop-based test
- Callback functions defined globally in `callback_testcase.php` will be replaced with closures or private methods within the test class

## Constraints

- The library's public API **must not change** — the tests are read-only consumers of the existing API
- The old `testcase/` directory is preserved (not deleted) during this work
- Test namespaces follow the suite structure: `Tests\DOM\`, `Tests\Selectors\`, `Tests\Parsing\` with PSR-4 autoloading mapping `tests/` → `Tests\`

## Out of Scope

- Refactoring or modernizing the library source code (`src/simple_html_dom.php`)
- Adding new test coverage beyond what the existing manual tests cover
- Removing the old `testcase/` directory (can be done in a follow-up)
- CI/CD pipeline configuration (GitHub Actions, etc.)
- Code coverage reporting configuration
- Performance tests (`performance_test.php`, `memory_test.php`, `testcase/reader/performance_test.php`, `testcase/reader/memory_test.php`) — these are benchmarks, not unit tests
- The `slick_test.php` / `slickspeed.htm` files — browser-based comparison tests
- Recreating or implementing the missing `simple_html_dom_reader.php` — reader tests are adapted to the main class instead

## Acceptance Criteria

- All PHPUnit tests pass with `vendor/bin/phpunit` returning exit code 0
- Every non-commented `assert()` from the 10 original `*_testcase.php` files (8 root + 2 reader) has a corresponding PHPUnit assertion
- Reader test assertions that differ due to whitespace normalization are adjusted to match the main library's actual behavior
- Tests are organized into three named suites: `parsing`, `selectors`, `dom`
- Each suite can be run independently: `vendor/bin/phpunit --testsuite=<name>`
- Test classes follow PSR-4 autoloading under `Tests\DOM\`, `Tests\Selectors\`, `Tests\Parsing\` namespaces
- `phpunit.xml` is present with all three suites properly configured
- `composer.json` includes PHPUnit as a dev dependency with autoload-dev configured
- No modifications to `src/simple_html_dom.php`
- Old `testcase/` directory remains intact

## Testing Strategy

The testing strategy is the migration itself — we are converting an existing test suite. Validation is done by:

1. **Assertion parity:** Verify that every `assert()` in the old files has a corresponding PHPUnit assertion in the new files
2. **Green suite:** All PHPUnit tests must pass (`vendor/bin/phpunit` returns 0)
3. **Cross-reference:** Run the old `testcase/all_test.php` manually to confirm it still passes, then compare coverage areas with the new suite
4. **Edge case preservation:** Ensure the monkey tests, random string fuzz tests, and malformed HTML tests are all faithfully converted

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **PHPUnit version incompatibility with PHP 8.5** | PHPUnit 12.x supports PHP 8.4+; verify with `composer require --dry-run` before installing |
| **Assertion semantics drift (`==` vs `===`)** | Carefully map each original `assert()` to the correct PHPUnit assertion (`assertEquals` vs `assertSame`); the existing tests mix `==` and `===` |
| **Global state pollution between tests** | Use `setUp()` to create a fresh `simple_html_dom` instance and `tearDown()` to `clear()` it, matching the existing test pattern |
| **Constants already defined errors** | The library uses `define()` for constants which can only be defined once; bootstrap via Composer autoloader handles this since the file is loaded once via `autoload.files` |
| **Callback function name collisions** | Replace global callback functions with closures inside test methods, or use unique names per test method |
| **Random/fuzz test non-determinism** | Convert the fuzz test to use a fixed seed or a data provider with predetermined strings, ensuring reproducibility |
| **Reader tests have different expected values** | Carefully compare reader assertions against actual main library output; adjust expected values where whitespace normalization differs; omit commented-out assertions |
