# Plan: PHP 8.4 Modernization & Multi-File Refactor

## Summary

`simple_html_dom` is currently delivered as a single 1,917-line PHP file (`src/simple_html_dom.php`) containing two classes (`simple_html_dom` and `simple_html_dom_node`), two helper classes (`simple_html_dom_settings`, `simple_html_dom_error`), a handful of procedural functions (`file_get_html`, `str_get_html`, `simple_html_dom_get_error`, `dump_html_tree`), and a block of global `define()` constants. This plan describes how to split the file into a clean, namespaced, PHP-8.4-idiomatic multi-file library while preserving every detail of the public API and all 209 existing PHPUnit tests.

---

## Architectural Context

### Current file: `src/simple_html_dom.php`

**Global constants (top of file)**

| Constant | Value | Purpose |
|---|---|---|
| `HDOM_TYPE_ELEMENT` | 1 | Node type: element |
| `HDOM_TYPE_COMMENT` | 2 | Node type: comment |
| `HDOM_TYPE_TEXT` | 3 | Node type: text |
| `HDOM_TYPE_ENDTAG` | 4 | Node type: end tag |
| `HDOM_TYPE_ROOT` | 5 | Node type: root |
| `HDOM_TYPE_UNKNOWN` | 6 | Node type: unknown |
| `HDOM_QUOTE_DOUBLE` | 0 | Attribute quote style |
| `HDOM_QUOTE_SINGLE` | 1 | Attribute quote style |
| `HDOM_QUOTE_NO` | 3 | Attribute quote style (unquoted) |
| `HDOM_INFO_BEGIN` | 0 | Internal node info index |
| `HDOM_INFO_END` | 1 | Internal node info index |
| `HDOM_INFO_QUOTE` | 2 | Internal node info index |
| `HDOM_INFO_SPACE` | 3 | Internal node info index |
| `HDOM_INFO_TEXT` | 4 | Internal node info index |
| `HDOM_INFO_INNER` | 5 | Internal node info index |
| `HDOM_INFO_OUTER` | 6 | Internal node info index |
| `HDOM_INFO_ENDSPACE` | 7 | Internal node info index |
| `DEFAULT_TARGET_CHARSET` | `'UTF-8'` | Default charset |
| `DEFAULT_BR_TEXT` | `"\r\n"` | Text inserted for `<br>` in plaintext |
| `DEFAULT_SPAN_TEXT` | `" "` | Text appended to `<span>` in plaintext |
| `MAX_FILE_SIZE` | `600000` | Guard against enormous inputs |

**Classes**

| Class | Lines (approx.) | Responsibility |
|---|---|---|
| `simple_html_dom_settings` | ~30 | Static key/value store for library-global settings; exposes `setMaxFilesize()` / `getMaxFilesize()` |
| `simple_html_dom_error` | ~20 | Value object returned by `simple_html_dom_get_error()` |
| `simple_html_dom_node` | ~850 | A single DOM node: holds `tag`, `attr`, `children`, `nodes`, parent back-reference, and the `_[]` internal-info array; implements CSS selector `find()`, `seek()`, `match()`, `parse_selector()`; `__get`/`__set`/`__isset`/`__unset` for attribute access; navigation helpers; text-conversion logic |
| `simple_html_dom` | ~750 | The parser/document root: `load()`, `load_file()`, `prepare()`, `parse()`, `read_tag()`, `parse_attr()`, `parse_charset()`; noise-stripping and restoration; character-stream helpers (`copy_until`, `copy_skip`, `skip`, etc.); `find()` delegating to root node; `save()`, `clear()`, callback management; camelCase DOM API delegates |

**Procedural API (must remain globally callable)**

| Symbol | Signature |
|---|---|
| `file_get_html(...)` | Loads from URL/file; returns `simple_html_dom\|false` |
| `str_get_html(...)` | Loads from string; returns `simple_html_dom\|false` |
| `simple_html_dom_get_error()` | Returns last `simple_html_dom_error\|null` |
| `dump_html_tree($node, ...)` | Debug helper |

**Test suite (existing, all green)**

- `tests/DOM/` — 5 test classes covering callbacks, tree navigation, element mutation, miscellaneous, and reader-compatibility elements
- `tests/Parsing/` — 3 test classes covering valid HTML, invalid/malformed HTML, and noise stripping
- `tests/Selectors/` — 2 test classes covering all selector types and reader-compatibility selectors
- 209 tests / 1 014 assertions; bootstrapped via `vendor/autoload.php`

**Autoload configuration in `composer.json`**

```json
"autoload": {
    "classmap": ["src"],
    "files": ["src/simple_html_dom.php"]
}
```

The `files` key causes `simple_html_dom.php` to be included on every request. That is how the global constants and procedural functions are currently available. Any new split must continue to guarantee their availability.

---

## Approach / Architecture

The refactor is split into two clearly separated concerns:

### 1. File structure and namespacing

Move all classes into the `SimpleHtmlDom` namespace under a new `src/` subdirectory layout. Keep the procedural global API alive via a thin `src/simple_html_dom.php` "bridge" file that:

- Declares global constants (or imports them from a dedicated file)
- Defines the procedural functions (`file_get_html`, `str_get_html`, etc.)
- Aliases the namespaced classes back to their legacy global names so existing code using `new simple_html_dom()` or type-hinting `\simple_html_dom` continues to work without any change

All tests reference classes as `\simple_html_dom` and `\simple_html_dom_node`; the aliases must maintain that contract.

### 2. PHP 8.4 modernisation

Each class is rewritten using PHP 8.4 idioms while keeping observable behaviour byte-for-byte identical. The specific features to apply are documented per-class in the Detailed Steps section.

### New directory layout

```
src/
  SimpleHtmlDom/
    NodeType.php              (enum replacing HDOM_TYPE_* constants)
    QuoteStyle.php            (enum replacing HDOM_QUOTE_* constants)
    NodeInfo.php              (enum or final class replacing HDOM_INFO_* constants)
    Settings.php              (was simple_html_dom_settings)
    Error.php                 (was simple_html_dom_error)
    Node.php                  (was simple_html_dom_node — the DOM node)
    Parser.php                (was simple_html_dom — the parser/document)
    SelectorParser.php        (extracted from simple_html_dom_node::parse_selector + seek + match)
    TextConverter.php         (extracted from simple_html_dom_node::convert_text + is_utf8)
  simple_html_dom.php         (bridge: constants, procedural functions, class aliases)
```

### Public API compatibility strategy

The bridge file (`src/simple_html_dom.php`) contains:

```php
// 1. Constants (still defined at global scope for backward compat)
define('HDOM_TYPE_ELEMENT', NodeType::Element->value);
// … all other defines …

// 2. Class aliases
class_alias(\SimpleHtmlDom\Parser::class, 'simple_html_dom');
class_alias(\SimpleHtmlDom\Node::class,   'simple_html_dom_node');
// … etc …

// 3. Procedural functions
function str_get_html(...) { ... }
function file_get_html(...) { ... }
// … etc …
```

`composer.json` is updated to use PSR-4 for `SimpleHtmlDom\` and continues to include the bridge file via `files`.

---

## Rationale

- **PHP 8.4 enums** replace the `HDOM_TYPE_*`, `HDOM_QUOTE_*`, and `HDOM_INFO_*` integer constants. Enums provide type-safety, IDE completion, and exhaustiveness checking. The raw `int` values are preserved via backed enums (`int`) so the existing `$node->_[HDOM_INFO_BEGIN]` access pattern still works when code reads through the global `define()` aliases.
- **Constructor promotion** applies cleanly to `simple_html_dom_error` (two fields: `$message`, `$code`) and to the new `Settings` class.
- **Readonly properties** apply to `simple_html_dom_error::$message` and `::$code` — they are never mutated after construction.
- **`SelectorParser` extraction** separates concern: the node class currently handles both DOM tree navigation and CSS selector parsing. Moving `parse_selector()`, `seek()`, and `match()` to a dedicated `SelectorParser` class allows those to be unit-tested independently and reduces `Node.php`'s surface area substantially.
- **`TextConverter` extraction** moves the charset-conversion and UTF-8 detection logic out of `Node` into a stateless helper, making it independently testable.
- **Property hooks (PHP 8.4)** are applicable to the magic `__get`/`__set`/`__isset`/`__unset` pattern on `simple_html_dom_node`. The virtual properties `outertext`, `innertext`, `plaintext`, `xmltext` can be expressed as hooked properties, eliminating the procedural `switch` in `__get` and `__set`. However, attribute passthrough (the `$this->attr[$name]` fallback in `__get`) cannot be fully replaced by hooks because the key is not statically known. The plan uses hooks for the named virtual properties and retains `__get`/`__set` only for the dynamic attribute access path.
- **Asymmetric visibility** is applicable to any property that should be publicly readable but only privately writeable — e.g., `Node::$tag`, `Node::$nodetype`, `Node::$tag_start` once mutation is gated through methods rather than direct public assignment. This is an optional refinement available during Phase 2 and must not break the test suite (which directly assigns `$node->tag = 'span'`). For now, those properties stay `public` but are annotated for a future Phase 3.
- **Named arguments** improve clarity at internal call sites (`$this->prepare(str: $str, lowercase: $lowercase, ...)`).
- **`declare(strict_types=1)`** is added to every new file.
- **Fibers** are not relevant to this use case (synchronous, in-memory parsing).

---

## Detailed Steps

### Phase 1 — Create the namespaced skeleton (no behaviour change)

1. Create `src/SimpleHtmlDom/` directory.
2. Create `src/SimpleHtmlDom/NodeType.php` — backed `int` enum with cases `Element=1`, `Comment=2`, `Text=3`, `EndTag=4`, `Root=5`, `Unknown=6`.
3. Create `src/SimpleHtmlDom/QuoteStyle.php` — backed `int` enum with cases `Double=0`, `Single=1`, `None=3`.
4. Create `src/SimpleHtmlDom/NodeInfo.php` — backed `int` enum with cases `Begin=0`, `End=1`, `Quote=2`, `Space=3`, `Text=4`, `Inner=5`, `Outer=6`, `EndSpace=7`.
5. Create `src/SimpleHtmlDom/Error.php` — extracted from `simple_html_dom_error`; use constructor promotion with `readonly` properties.
6. Create `src/SimpleHtmlDom/Settings.php` — extracted from `simple_html_dom_settings`; keep the static `$settings` array; add typed method signatures.
7. Create `src/SimpleHtmlDom/TextConverter.php` — stateless class extracted from `simple_html_dom_node::convert_text()` and `::is_utf8()`. Takes `$sourceCharset` and `$targetCharset` as constructor parameters or as method parameters.
8. Create `src/SimpleHtmlDom/SelectorParser.php` — extracted from `simple_html_dom_node::parse_selector()`, `::seek()`, and `::match()`. Receives references to the node and DOM as needed. Keep the existing algorithm byte-for-byte; change only the code structure.
9. Create `src/SimpleHtmlDom/Node.php` — the main node class, using PHP 8.4 features (see below); delegates to `SelectorParser` and `TextConverter`.
10. Create `src/SimpleHtmlDom/Parser.php` — the main parser/document class, using PHP 8.4 features (see below); delegates to `Settings`.
11. Update `src/simple_html_dom.php` to become the bridge file:
    - Keep all `define()` calls (pointing to enum values where applicable).
    - Add `class_alias()` calls for `simple_html_dom` → `SimpleHtmlDom\Parser`, `simple_html_dom_node` → `SimpleHtmlDom\Node`, `simple_html_dom_settings` → `SimpleHtmlDom\Settings`, `simple_html_dom_error` → `SimpleHtmlDom\Error`.
    - Keep the three procedural functions (`file_get_html`, `str_get_html`, `simple_html_dom_get_error`, `dump_html_tree`).
12. Update `composer.json`:
    - Add PSR-4 mapping `"SimpleHtmlDom\\": "src/SimpleHtmlDom/"` to `autoload.psr-4`.
    - Remove the `classmap` entry (now redundant).
    - Keep the `files` entry pointing to `src/simple_html_dom.php`.
13. Fix the local `$dom` teardown pattern in the existing test suite (identified by the PHPUnit migration synthesis). In `MiscTest`, `ReaderElementTest`, `ReaderSelectorTest`, and `InvalidHtmlTest` the `$dom` object is created as a local variable with a manual `->clear()` at method end. If any assertion fails, the DOM object is never freed. Convert these to `$this->dom` assignments and add a `tearDown()` method that calls `$this->dom?->clear()`. This is a prerequisite fix before adding the new namespace layer, not a behaviour change.
14. Run `composer dump-autoload` and execute the full PHPUnit suite. All 209 tests must pass before proceeding to Phase 2.

### Phase 2 — Apply PHP 8.4 idioms (class by class)

**`Error.php`**

- Constructor promotion: `public function __construct(private readonly string $message, private readonly int $code)`.
- Return types on `getMessage(): string` and `getCode(): int`.
- `declare(strict_types=1)`.

**`Settings.php`**

- Typed static property: `protected static array $settings`.
- Typed method signatures: `setMaxFilesize(int $bytes): void`, `getMaxFilesize(): int`, `set(string $name, mixed $value): void`, `get(string $name, mixed $default = null): mixed`.
- Replace `MAX_FILE_SIZE` constant reference with `NodeConstants::DEFAULT_MAX_FILE_SIZE` (or leave it as-is referencing the global define — simpler).
- `declare(strict_types=1)`.

**`Node.php`** (formerly `simple_html_dom_node`)

- `declare(strict_types=1)`.
- All method signatures get proper PHP 8.x return and parameter types.
- Replace `function __construct($dom)` with constructor promotion where possible: `public function __construct(private ?Parser $dom)`.
- Apply **property hooks** for virtual properties. PHP 8.4 property hooks can replace most of the `__get`/`__set` `switch` logic for the named virtual properties:

  ```php
  public string $outertext {
      get => $this->outertext();
      set(string $value) { $this->_[NodeInfo::Outer->value] = $value; }
  }
  public string $innertext {
      get => $this->innertext();
      set(string $value) {
          if (isset($this->_[NodeInfo::Text->value])) {
              $this->_[NodeInfo::Text->value] = $value;
          } else {
              $this->_[NodeInfo::Inner->value] = $value;
          }
      }
  }
  ```

  **Important caveat**: PHP 8.4 property hooks on a property prevent `__get`/`__set` from being called for that property name. The generic attribute fallback (`$this->attr[$name]`) must remain in a `__get` that only fires for names that are NOT the hooked property names. Care is required when `$name === 'outertext'` etc. — the hooked property takes precedence over `__get`, which is exactly the desired behaviour. Test this carefully before accepting.

- Replace the `list()` call in `seek()` with array destructuring syntax: `[$tag, $key, $val, $exp, $no_key] = $selector;`.
- Use `match` expression in `match()` method (replace `switch`).
- `count($this->children) > 0` → `$this->children !== []` (micro-optimisation; also cleaner).
- Remove the `global $debugObject;` references throughout. These are dead code in the modern library; removing them cleans up the namespace pollution. Confirm no test exercises the debug object.
- Replace `function __construct`, `function clear`, etc. (bare `function` in class body) with proper visibility keywords (`public function`, etc.). The existing code is missing `public` on many methods due to PHP 4-era style.

**`SelectorParser.php`**

- Extracted from `Node::parse_selector()`, `Node::seek()`, `Node::match()`.
- Receives the calling node as a parameter (or holds a reference set at construction time).
- Apply `match` expression in the `matchValue()` method.
- All parameters and return types are typed.
- `declare(strict_types=1)`.

**`TextConverter.php`**

- Stateless helper; all methods are `static` or the class is instantiated with charset configuration.
- `convert(string $text, string $sourceCharset, string $targetCharset): string`.
- `isUtf8(string $str): bool` — existing algorithm preserved.
- `declare(strict_types=1)`.

**`Parser.php`** (formerly `simple_html_dom`)

- `declare(strict_types=1)`.
- Constructor: type all parameters explicitly. Keep signature compatible with `new simple_html_dom(null, true, true, 'UTF-8', true, "\r\n", " ")`.
- Named argument usage at internal call sites.
- Replace `function load_file()` + `func_get_args()` pattern with a proper variadic or named-parameter signature: `public function load_file(string ...$args): void`.
- Protected property types: `protected int $pos`, `protected string $doc`, `protected ?string $char`, `protected int $cursor`, etc.
- The `$noise` array is `protected array $noise = []`.
- Token constants (`$token_blank`, etc.) become `private const string TOKEN_BLANK = " \t\r\n"`, etc.
- Self-closing and block-tag sets become `private const array SELF_CLOSING_TAGS = [...]` and `private const array BLOCK_TAGS = [...]`. Similarly for `$optional_closing_tags`.
- The `global $debugObject` references are removed (same as in `Node.php`).
- `__toString(): string` gets return type.
- Replace bare `function` method declarations with `public function` where missing.
- **Replace `$http_response_header` reads with `http_get_last_response_headers()`** (synthesis item: lines 99, 102, 113, 120 of the original `src/simple_html_dom.php`). The predefined local variable `$http_response_header` is deprecated in PHP 8.4 and removed in PHP 9. This is a PHP 9 readiness fix and eliminates deprecation notices from every test run. The fix applies to `load_file()` / `file_get_html()`'s internal HTTP path.

**Tokeniser bug fix: `<digit` content-loss**

Fix the tag-detection heuristic that silently discards content after `<` followed by a digit (e.g. `<1 mol%`, `<2NaCl`, version strings like `<2.0`). The HTML5 spec only treats `<` as a tag opener when followed by a letter, `/`, `!`, or `?`. Update the tokeniser in `Parser.php` to apply this rule, restoring correct round-trip behaviour for chemistry formulas, math expressions, and version strings.

- `StandardTest::testChemistryFormula` currently documents this as a known non-round-trip via `assertIsString`. Once the heuristic is fixed, update the test assertion to verify correct round-trip equality.
- This is the highest-value correctness fix identified by the PHPUnit migration; address it in Phase 2 alongside the other `Parser.php` changes.

### Phase 3 — Composer and autoload hygiene

1. Verify `composer.json` `autoload` section:
   ```json
   "autoload": {
       "psr-4": {
           "SimpleHtmlDom\\": "src/SimpleHtmlDom/"
       },
       "files": [
           "src/simple_html_dom.php"
       ]
   }
   ```
2. Update `composer.json` `scripts.test` to use `vendor/bin/phpunit` instead of bare `phpunit` for contributor clarity (synthesis item).
3. Add `<source><include><directory>src/</directory></include></source>` to `phpunit.xml` so that `--coverage-html` and `--coverage-text` produce non-empty reports when coverage is needed (synthesis item).
4. Run `composer dump-autoload --optimize`.
5. Run full PHPUnit suite again. All 209 tests must still pass.
6. Update `changelog.md` with a new `v2.0` entry.

### Phase 4 — Test suite enhancements for new units

Add targeted unit tests for the newly extracted classes:

- `tests/Unit/SettingsTest.php` — get/set, max-filesize, error storage.
- `tests/Unit/ErrorTest.php` — constructor, getters, readonly enforcement.
- `tests/Unit/TextConverterTest.php` — charset conversion, BOM stripping, `isUtf8`.
- `tests/Unit/SelectorParserTest.php` — `parse_selector()` output for known inputs, `match()` for each operator.

Update `phpunit.xml` to add a `unit` testsuite pointing at `tests/Unit/`.

---

## Dependencies

- PHP `^8.4` (already declared in `composer.json`)
- `ext-mbstring` (already declared)
- `phpunit/phpunit ^12.0` (already declared as dev dependency)
- No new runtime dependencies

---

## Required Components

**New files (all under `src/SimpleHtmlDom/`)**

| File | New | Description |
|---|---|---|
| `src/SimpleHtmlDom/NodeType.php` | Yes | Backed int enum |
| `src/SimpleHtmlDom/QuoteStyle.php` | Yes | Backed int enum |
| `src/SimpleHtmlDom/NodeInfo.php` | Yes | Backed int enum |
| `src/SimpleHtmlDom/Error.php` | Yes | Replaces `simple_html_dom_error` |
| `src/SimpleHtmlDom/Settings.php` | Yes | Replaces `simple_html_dom_settings` |
| `src/SimpleHtmlDom/TextConverter.php` | Yes | Charset conversion helper |
| `src/SimpleHtmlDom/SelectorParser.php` | Yes | CSS selector parsing and seeking |
| `src/SimpleHtmlDom/Node.php` | Yes | Replaces `simple_html_dom_node` |
| `src/SimpleHtmlDom/Parser.php` | Yes | Replaces `simple_html_dom` |

**Modified files**

| File | Description |
|---|---|
| `src/simple_html_dom.php` | Becomes a bridge: constants, class aliases, procedural functions |
| `composer.json` | PSR-4 entry added; classmap removed |
| `changelog.md` | v2.0 entry |

**New test files**

| File | Description |
|---|---|
| `tests/Unit/SettingsTest.php` | Unit tests for `Settings` |
| `tests/Unit/ErrorTest.php` | Unit tests for `Error` |
| `tests/Unit/TextConverterTest.php` | Unit tests for `TextConverter` |
| `tests/Unit/SelectorParserTest.php` | Unit tests for `SelectorParser` |

---

## Assumptions

- All current tests reference `\simple_html_dom`, `\simple_html_dom_node`, `str_get_html()`, `file_get_html()`, and global `HDOM_*` constants. These must remain globally available.
- The `global $debugObject` pattern is dead code (no test exercises it, no user-facing documentation mentions it). Removing it is safe but must be confirmed by grepping the test suite before deletion.
- `app/` directory contains example/demo code (`app/index.php`, `app/google.htm`) and is not part of the library's deliverable; it will not be modified.
- PHP 8.4 property hooks can coexist with `__get`/`__set`; the hooked-property names take precedence. This is the documented PHP 8.4 behaviour but must be verified against the test suite.

---

## Constraints

- The public API must be 100% backward-compatible: every symbol that existed before the refactor (`simple_html_dom`, `simple_html_dom_node`, `simple_html_dom_settings`, `simple_html_dom_error`, all `HDOM_*` constants, all procedural functions) must continue to work without modification by existing consumers.
- Do not add any new `require` calls inside `src/SimpleHtmlDom/` files; rely entirely on Composer's PSR-4 autoload.
- The bridge file `src/simple_html_dom.php` must be the only file containing `define()` calls and `function` declarations at global scope.
- No changes to the test files in `tests/` during Phases 1–3 (they are the acceptance criteria). New test files are added in Phase 4 only.

---

## Out of Scope

- Changing observable parsing behaviour (the algorithm is not touched)
- Adding new selectors or features
- Changing the `file_get_html()` HTTP-redirect logic
- Adding a PSR-compliant logger to replace the dead `$debugObject` global (post-v2 work)
- Providing a PSR-4 autoloadable entry point that removes the need for the `files` bridge (requires a separate major version since it would break `new \simple_html_dom()` usage)
- Migrating the `app/` example directory

---

## Acceptance Criteria

- All 209 existing PHPUnit tests pass without modification to test files (except the `StandardTest::testChemistryFormula` assertion update after the `<digit` heuristic fix).
- `composer dump-autoload --optimize` completes without warnings.
- Every new class file begins with `declare(strict_types=1)` and is in the `SimpleHtmlDom` namespace.
- The legacy names `\simple_html_dom`, `\simple_html_dom_node`, `\simple_html_dom_settings`, `\simple_html_dom_error` resolve via `class_alias`.
- All `HDOM_*` constants remain globally defined.
- The procedural functions `str_get_html()`, `file_get_html()`, `simple_html_dom_get_error()`, `dump_html_tree()` remain globally callable.
- PHP 8.4 property hooks are used for `outertext` and `innertext` on `Node.php`.
- `simple_html_dom_error` fields are `readonly`.
- Backed enums cover all three constant groups (`NodeType`, `QuoteStyle`, `NodeInfo`).
- Constructor promotion is used in `Error` and (where non-nullable) in `Node` and `Parser`.
- No `global $debugObject;` lines remain.
- No `$http_response_header` reads remain; replaced with `http_get_last_response_headers()`.
- PHPUnit test run produces zero deprecation notices under PHP 8.4.
- `StandardTest::testChemistryFormula` passes as a round-trip equality assertion (not a known-failure workaround).
- `phpunit.xml` includes a `<source>` element pointing at `src/`.
- `composer.json` `scripts.test` uses `vendor/bin/phpunit`.
- The four test classes with local `$dom` variables (`MiscTest`, `ReaderElementTest`, `ReaderSelectorTest`, `InvalidHtmlTest`) use `$this->dom` with a `tearDown()` cleanup.

---

## Testing Strategy

**Regression gate (Phases 1–3)**: After every phase the full suite (`vendor/bin/phpunit`) must pass with zero failures and zero errors. The test suite already covers parsing, selector matching, DOM mutation, callbacks, invalid HTML, and fuzz strings — it serves as a complete regression harness for the refactor.

**New unit tests (Phase 4)**: Each extracted helper class (`Settings`, `Error`, `TextConverter`, `SelectorParser`) gets its own test class that exercises it directly without loading the parser. This provides isolation and makes future changes to those helpers safer.

**Smoke test**: After Phase 3, the `app/index.php` example should run without errors under `php -l` (syntax check) and `php app/index.php` if a web server is available.

---

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| **PHP 8.4 property hooks conflict with `__get`/`__set` fallback** | Prototype the hooked properties on a minimal scratch class before applying to `Node.php`. The existing test suite has exhaustive attribute-access coverage; any regression will surface immediately. |
| **`class_alias()` called before Composer autoload** | The bridge file is loaded via the `files` autoload key, which runs after PSR-4 is registered. Ensure no circular includes exist in the new namespace files. |
| **Enum values used as array indices** | The `$node->_` array is indexed by `HDOM_INFO_*` integers. After the refactor, those indices are still plain ints (via `define()` pointing to `NodeInfo::Begin->value`, etc.). Code inside `Node.php` and `Parser.php` can use `NodeInfo::Begin->value` directly. No risk of mismatch as long as the enum backing values match the original `define()` values — which must be validated in a test. |
| **`SelectorParser` requires deep coupling to `Node` internals** | `seek()` directly iterates `$this->dom->nodes[$i]` and reads `$c->_[HDOM_INFO_BEGIN]`. Pass the DOM reference explicitly to `SelectorParser`; mark the required properties `public` on `Node` (they already are). |
| **`load_file()` uses `func_get_args()` variadic trick** | Rewrite with `string ...$args` and forward to `file_get_contents(...$args)`. The existing test `StandardTest::testCloneDom` and `MiscTest::testErrorTagHandling` cover load paths; add a specific test for `load_file()` if none exists. |
| **Dead `$debugObject` removal breaks a user who actually set it** | Removal is safe only within the library's own code. If an application sets `$debugObject` globally, the calls inside the library were already no-ops unless that global existed. After removal those calls simply disappear — acceptable. |
| **`<digit` heuristic fix changes parsing output for existing consumers** | Any consumer relying on the current (broken) behaviour of discarding content after `<digit` patterns would see their output change. This is a correctness fix, not a breaking change by intent. Document in `changelog.md` under a "Bug Fixes" heading. |
| **`http_get_last_response_headers()` availability** | This function was introduced in PHP 8.4. Since `composer.json` already requires `^8.4`, this is safe. Confirm with a PHP version guard in a comment in case the function is ever backported incorrectly. |
