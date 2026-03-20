# Plan: Follow-Up Fixes, Modernization Completions, and New Unit Tests

## Summary

Following the PHP 8.4 modernization and multi-file refactor completed on the `maintenance-modernization` branch, a set of residual defects and incomplete modernization items have been identified. This plan addresses four categories of work: (1) four confirmed bugs in `Node.php`, `Parser.php`, and the bridge file `src/simple_html_dom.php`; (2) six PHP 8.4 / modernization items that were deferred or not reached during the prior cycle; (3) two new unit test classes for `Parser` and `Node` plus a regression guard for one of the bugs; and (4) two minor quality-of-life additions to `Error` and `Settings`. All work is contained within the already-established `src/SimpleHtmlDom/` namespace and bridge file. No new classes, no new dependencies, and no BC-breaking changes are introduced. Full enum adoption for `nodetype` (M-008) is explicitly deferred.

---

## Architectural Context

### Current layout after PHP 8.4 modernization

```
src/
  SimpleHtmlDom/
    NodeType.php          — backed int enum (HDOM_TYPE_* values)
    QuoteStyle.php        — backed int enum (HDOM_QUOTE_* values)
    NodeInfo.php          — backed int enum (HDOM_INFO_* values)
    Error.php             — value object, constructor promotion, readonly properties
    Settings.php          — static key/value store; setMaxFilesize / getMaxFilesize / get / set
    TextConverter.php     — stateless charset helper; convert() + isUtf8()
    SelectorParser.php    — CSS selector parsing (parseSelector), seek, match
    Node.php              — single DOM node; PHP 8.4 property hooks on $outertext / $innertext
    Parser.php            — document root / parser; const token sets; PSR-4 autoloaded
  simple_html_dom.php     — bridge: define() constants, class_alias() calls, procedural functions
tests/
  DOM/                    — 5 existing test classes (callbacks, tree navigation, mutation, etc.)
  Parsing/                — 3 existing test classes (valid HTML, invalid HTML, noise)
  Selectors/              — 2 existing test classes (selector types, reader-compat selectors)
  Unit/
    ErrorTest.php         — already exists
    SettingsTest.php      — already exists
    SelectorParserTest.php— already exists
    TextConverterTest.php — already exists
```

`composer.json` uses PSR-4 for `SimpleHtmlDom\` pointing at `src/SimpleHtmlDom/` and a `files` entry for the bridge. The test bootstrap loads `vendor/autoload.php`. PHPUnit 12 is the test runner.

### Key integration points relevant to this plan

| File | Affected items |
|---|---|
| `src/SimpleHtmlDom/Node.php` | B-001, B-002, M-005, M-007, T-002 |
| `src/SimpleHtmlDom/Parser.php` | B-004, M-001, M-003, M-004, M-006 |
| `src/simple_html_dom.php` | B-003, M-002 |
| `src/SimpleHtmlDom/Error.php` | L-001 |
| `src/SimpleHtmlDom/Settings.php` | L-002 |
| `tests/Unit/ParserTest.php` | T-001 (new file) |
| `tests/Unit/NodeTest.php` | T-002, T-003 (new file) |

---

## Approach / Architecture

The work is organized into four work packages executed sequentially. Each package is self-contained and leaves the PHPUnit suite green before the next begins.

**WP-1 Bug Fixes** — The four bugs are independent of one another and can be fixed in a single pass through the affected files. All are small, surgical changes (1–3 lines each). None alter the observable public API.

**WP-2 PHP 8.4 Modernization Completions** — Six items that either were deferred during the prior cycle or were identified in the synthesis as known remaining work. These are internal changes (type narrowing, `match` expression, null guards, refactoring) that do not change behaviour. The `optional_closing_array` dynamic-property item (M-001) requires declaring a new nullable instance property and a small conditional in the constructor and `read_tag()`.

**WP-3 New Unit Tests** — Two new test classes. `ParserTest.php` covers the core `Parser` API at the integration level (load/find/save). `NodeTest.php` covers individual `Node` methods in isolation and includes the B-003 regression guard.

**WP-4 Minor Improvements** — Additive changes only: a `__toString()` on `Error` and a `reset()` static method on `Settings`. Neither touches existing method signatures or behaviour.

---

## Rationale

- **Bugs first**: All four bugs can cause silent misbehaviour or fatal errors at runtime. They must be fixed before any other work proceeds so that the test suite becomes a reliable baseline.
- **M-001 before tests**: The `optional_closing_array` dynamic-property fix (WP-2) declares the property; the new `ParserTest` (WP-3) exercises the constructor paths that set it. WP-3 therefore depends on WP-2 being complete.
- **Deferring M-008 (full enum adoption for `nodetype`)**: Replacing the public `int $nodetype` field on `Node` with a `NodeType` enum instance is a BC break for any consumer reading or writing `$node->nodetype` as an integer. The synthesis document flagged this explicitly; it belongs in a separate major-version plan.
- **No new runtime dependencies**: All changes use PHP 8.4 language features already required by `composer.json`.

---

## Detailed Steps

### Work Package 1 — Bug Fixes

**B-001** Fix `Node::dump_node()` — undefined `$node` variable (line 142 of `src/SimpleHtmlDom/Node.php`)

The block:
```php
if (isset($node->_[HDOM_INFO_INNER])) {
    $string .= $node->_[HDOM_INFO_INNER] . "'";
```
must be changed to:
```php
if (isset($this->_[HDOM_INFO_INNER])) {
    $string .= $this->_[HDOM_INFO_INNER] . "'";
```
`$node` is not declared anywhere in `dump_node()`; this is a copy-paste residue from the original single-file source.

**B-002** Fix `Node::dump_node()` — dead `isset($this->text)` block (line 137 of `src/SimpleHtmlDom/Node.php`)

`text` is not a declared property on `Node`. Under PHP 8.4, accessing an undeclared property on a class without `#[AllowDynamicProperties]` via `isset()` always returns `false`. The entire block:
```php
if (isset($this->text)) {
    $string .= " text: (" . $this->text . ")";
}
```
must be removed. It has never executed and produces a deprecation notice on PHP 8.4 in some configurations.

**B-003** Fix `dump_html_tree()` in `src/simple_html_dom.php` — wrong argument passed to `->dump()`

The current body:
```php
function dump_html_tree($node, $show_attr = true, $deep = 0)
{
    $node->dump($node);
}
```
passes `$node` (a `Node` object) as the first argument to `Node::dump(bool $show_attr, int $deep)`. The correct call is:
```php
$node->dump($show_attr, $deep);
```

**B-004** Fix operator-precedence bug in `Parser::read_tag()` (line 433 of `src/SimpleHtmlDom/Parser.php`)

The current expression:
```php
if ($pos = strpos($tag, '<') !== false) {
```
assigns the result of `strpos($tag, '<') !== false` — which is a `bool` — to `$pos` because `!==` binds tighter than `=`. The result is that `$pos` is always `true` (cast to `1`) rather than the actual byte offset.

Fix with explicit parentheses:
```php
if (($pos = strpos($tag, '<')) !== false) {
```

---

### Work Package 2 — PHP 8.4 Modernization Completions

**M-001** Declare `$optionalClosingArray` as a nullable instance property on `Parser`

The constructor currently assigns:
```php
$this->optional_closing_array = [];
```
`optional_closing_array` is not declared as a class property, causing a PHP 8.4 dynamic-property deprecation notice on every `new Parser(..., forceTagsClosed: false)` call.

Steps:
1. Add `private ?array $optionalClosingArray = null;` to the `Parser` class body alongside the other property declarations.
2. In the constructor `!$forceTagsClosed` branch, change the assignment to use the correctly-cased name: `$this->optionalClosingArray = [];`.
3. In `read_tag()`, replace every reference to `self::OPTIONAL_CLOSING_TAGS` with a helper expression: `$this->optionalClosingArray ?? self::OPTIONAL_CLOSING_TAGS`. This preserves the original behaviour: when `forceTagsClosed` is `true` (the default), `$optionalClosingArray` is `null` and the constant is used; when `false`, the empty array is used, effectively disabling all optional-closing-tag logic.

Note: search `read_tag()` for all three uses of `self::OPTIONAL_CLOSING_TAGS` — at lines approximately 361, 465, and 466 of the current `Parser.php` — and apply the replacement to each.

**M-002** Add type declarations to bridge file procedural functions in `src/simple_html_dom.php`

The four procedural functions currently have no type information:

| Function | Typed signature to apply |
|---|---|
| `file_get_html()` | Parameters: `string $url, bool $use_include_path = false, mixed $context = null, int $offset = -1, int $maxLen = -1, bool $lowercase = true, bool $forceTagsClosed = true, string $target_charset = DEFAULT_TARGET_CHARSET, bool $stripRN = true, string $defaultBRText = DEFAULT_BR_TEXT, string $defaultSpanText = DEFAULT_SPAN_TEXT` Return: `\SimpleHtmlDom\Parser\|false` |
| `str_get_html()` | Parameters: `string $str, bool $lowercase = true, bool $forceTagsClosed = true, string $target_charset = DEFAULT_TARGET_CHARSET, bool $stripRN = true, string $defaultBRText = DEFAULT_BR_TEXT, string $defaultSpanText = DEFAULT_SPAN_TEXT` Return: `\SimpleHtmlDom\Parser\|false` |
| `simple_html_dom_get_error()` | No parameters. Return: `\SimpleHtmlDom\Error\|null` |
| `dump_html_tree()` | Parameters: `\SimpleHtmlDom\Node $node, bool $show_attr = true, int $deep = 0` Return: `void` |

The bridge file has no `declare(strict_types=1)` at the top (intentionally, as it is a global-scope file loaded via `files`). Type declarations on the function signatures are safe to add without `strict_types`.

**M-003** Convert `Parser::parse_attr()` `switch` to `match` expression

`parse_attr()` contains:
```php
switch ($this->char) {
    case '"': ...
    case "'": ...
    default:  ...
}
```
Replace with a `match` expression consistent with the `match` usage elsewhere in the modernized codebase. The three arms map cleanly: `'"'` → double-quote handling, `"'"` → single-quote handling, `default` → unquoted attribute. Each arm must produce the same side effects as the current `case` block (setting `HDOM_INFO_QUOTE`, advancing `$this->pos`, reading the attribute value). Since the arms have different lengths of logic, structure the `match` as a statement (not expression) by immediately calling a closure or by extracting each path to its own inline block — the idiomatic PHP 8.1+ approach is to keep it as a statement-like `match` where each arm is a `do` block or to restructure with named helper methods. The simplest transformation uses `match(true)` if the arms are non-trivial, but `match($this->char)` with side-effect-free arm values followed by a conditional after the match is cleaner. Use whichever form the engineer judges most readable; the requirement is that `switch` is eliminated.

**M-004** Narrow `Parser::$callback` type from `mixed` to `callable|null`

```php
public mixed $callback = null;
```
Change to:
```php
public (callable&mixed)|null $callback = null;
```
or, since PHP does not yet support intersection types on callables in property declarations, use:
```php
/** @var callable|null */
public mixed $callback = null;
```
with an inline `@var` docblock until PHP natively supports `callable` as a standalone property type. Alternatively, declare as `public $callback = null;` with a docblock — but prefer the PHPDoc approach to make the semantic clear without losing strict mode compatibility. The key change is removing the bare `mixed` and documenting the intent.

Note: PHP does not allow `callable` as a standalone property type declaration. The correct approach here is `mixed` with an `@var callable|null` PHPDoc, which is what most typed codebases do today.

**M-005** Refactor `Node::find()` to create one `SelectorParser` instance

`Node::seek()`, `Node::match()`, and `Node::parse_selector()` each instantiate a fresh `SelectorParser($this)`. Because `find()` calls `parse_selector()` first, then calls `seek()` for each selector group (which each call `SelectorParser` again internally), up to 3 objects are created per `find()` call.

Refactor: create a single `SelectorParser` instance inside `Node::find()` and pass it down. The three wrapper methods become:
- `Node::parse_selector()` — accepts an optional `?SelectorParser $parser = null` and creates one if null, or refactors so `find()` creates it and passes it directly to the internal loop that calls `seek()`.
- `Node::seek()` — accepts an optional `?SelectorParser $parser = null`.

The simplest implementation is to make `find()` instantiate `new SelectorParser($this)` once and pass it as a parameter to `seek()`, and have `seek()` pass it further down to `match()`. The three `protected` wrapper methods can remain for backward compatibility (any subclass that overrides them retains the old instantiation path as a fallback) but their bodies are updated to accept and thread the instance.

**M-006** Fix `Parser::createElement()` and `Parser::createTextNode()` — remove `@str_get_html()` calls

Current code (lines 786–787 of `src/SimpleHtmlDom/Parser.php`):
```php
public function createElement(string $name, mixed $value = null): mixed
{
    return @str_get_html("<$name>$value</$name>");
}
public function createTextNode(mixed $value): mixed
{
    return @end(str_get_html($value)->nodes);
}
```

Both call the procedural `str_get_html()` from the bridge file with error suppression. The correct approach is to instantiate `Parser` directly:
```php
public function createElement(string $name, mixed $value = null): Node|false
{
    $parser = new Parser("<{$name}>{$value}</{$name}>");
    return $parser->find($name, 0) ?? false;
}
public function createTextNode(string $value): Node|false
{
    $parser = new Parser($value);
    $last = end($parser->nodes);
    return ($last instanceof Node) ? $last : false;
}
```
Tighten the return types from `mixed` to `Node|false`.

**M-007** Add null guards in `Node::first_child()`, `last_child()`, `next_sibling()`, `prev_sibling()`

`Node::$children` is declared `?array` and is set to `null` by `clear()`. Four navigation methods call `count($this->children)` without a null check, which would throw a `TypeError` in PHP 8 if called on a cleared node:

| Method | Current | Fix |
|---|---|---|
| `first_child()` | `count($this->children) > 0` | `count($this->children ?? []) > 0` |
| `last_child()` | `count($this->children)` | `count($this->children ?? [])` |
| `next_sibling()` | `count($this->parent->children)` | `count($this->parent->children ?? [])` |
| `prev_sibling()` | `count($this->parent->children)` | `count($this->parent->children ?? [])` |

---

### Work Package 3 — New Unit Tests

**T-001** Create `tests/Unit/ParserTest.php`

New file. Covers `Parser` directly using `SimpleHtmlDom\Parser` (bypasses the bridge). Test methods:

| Method | What it asserts |
|---|---|
| `testLoadRoundTrip()` | `$p->load('<p>hello</p>'); assert $p->save() contains 'hello'` |
| `testFindByTag()` | Load `<b>x</b><i>y</i>`; `find('b', 0)` returns a `Node` with `tag === 'b'` |
| `testFindByClass()` | Load `<span class="foo">x</span>`; `find('.foo', 0)` returns the span |
| `testFindById()` | Load `<div id="bar">x</div>`; `find('#bar', 0)` returns the div |
| `testSave()` | Load then `save()` returns a non-empty string |
| `testToString()` | `(string)$parser` equals `$parser->save()` |
| `testFindReturnsArrayWithNoIndex()` | `find('p')` with no index returns an array |
| `testForceTagsClosedFalse()` | Constructing with `forceTagsClosed: false` does not raise a deprecation notice (regression for M-001 / B-004-area) |

**T-002** Create `tests/Unit/NodeTest.php`

New file. Loads a controlled HTML fixture via `Parser` and exercises individual `Node` methods. Test methods:

| Method | What it asserts |
|---|---|
| `testFirstChild()` | `<ul><li>a</li><li>b</li></ul>` — `ul->first_child()->tag === 'li'` |
| `testLastChild()` | Same fixture — `ul->last_child()` returns the second `li` |
| `testNextSibling()` | First `li` `next_sibling()->tag === 'li'` |
| `testPrevSibling()` | Second `li` `prev_sibling()` returns first `li` |
| `testNextSiblingOnLastReturnsNull()` | `next_sibling()` on the last child returns `null` |
| `testPrevSiblingOnFirstReturnsNull()` | `prev_sibling()` on the first child returns `null` |
| `testHasChild()` | `ul->has_child()` is `true`; a `li` text node has no child |
| `testFindAncestorTag()` | Nested `<table><tr><td>` — `td->find_ancestor_tag('table')` returns the table node |
| `testText()` | `<p>hello <b>world</b></p>` — `p->text()` returns `'hello world'` |
| `testMakeup()` | `<a href="x">link</a>` — `makeup()` on the `a` node starts with `<a href=` |
| `testDumpNodeRegressionB001B002()` | Call `dump_node(false)` on a freshly-parsed node; assert result is a non-empty string and contains no PHP warnings (regression for B-001 + B-002) |
| `testNullChildrenAfterClear()` | Call `clear()` on a node; then call `first_child()` and `last_child()` — assert both return `null` without error (regression for M-007) |

**T-003** Regression test for B-003 — add to `NodeTest.php`

| Method | What it asserts |
|---|---|
| `testDumpHtmlTree()` | Call `dump_html_tree($node, false, 0)` via the bridge function; assert it does not throw a `TypeError` (prior bug passed `$node` object as `bool $show_attr`) |

---

### Work Package 4 — Minor Improvements

**L-001** Add `__toString()` to `Error`

In `src/SimpleHtmlDom/Error.php`, add:
```php
public function __toString(): string
{
    return "[{$this->code}] {$this->message}";
}
```

**L-002** Add `reset()` to `Settings`

In `src/SimpleHtmlDom/Settings.php`, add:
```php
public static function reset(): void
{
    self::$settings = [];
}
```

This is important for test isolation: any test that writes to `Settings` (e.g., via `setMaxFilesize()` or by triggering an error that calls `Settings::set('__error', ...)`) should call `Settings::reset()` in `tearDown()` to prevent cross-test contamination. Update the existing `SettingsTest.php` to call `Settings::reset()` in its `tearDown()` if it does not already do so.

---

## Dependencies

- PHP `^8.4` (already declared in `composer.json`)
- `ext-mbstring` (already declared)
- `phpunit/phpunit ^12.0` (already declared as dev dependency)
- No new runtime or dev dependencies

---

## Required Components

**Modified files**

| File | Work packages | Nature of change |
|---|---|---|
| `src/SimpleHtmlDom/Node.php` | WP-1 (B-001, B-002), WP-2 (M-005, M-007) | Bug fixes; null guards; `SelectorParser` threading |
| `src/SimpleHtmlDom/Parser.php` | WP-1 (B-004), WP-2 (M-001, M-003, M-004, M-006) | Bug fix; new property; `match`; type narrowing; direct `Parser` instantiation |
| `src/simple_html_dom.php` | WP-1 (B-003), WP-2 (M-002) | Bug fix; type declarations on procedural functions |
| `src/SimpleHtmlDom/Error.php` | WP-4 (L-001) | Add `__toString()` |
| `src/SimpleHtmlDom/Settings.php` | WP-4 (L-002) | Add `reset()` |
| `tests/Unit/SettingsTest.php` | WP-4 (L-002) | Add `tearDown()` with `Settings::reset()` if not already present |

**New files**

| File | Work package | Description |
|---|---|---|
| `tests/Unit/ParserTest.php` | WP-3 (T-001) | Unit tests for `Parser` load/find/save |
| `tests/Unit/NodeTest.php` | WP-3 (T-002, T-003) | Unit tests for `Node` navigation methods + regressions |

---

## Assumptions

- The PHPUnit suite (currently comprising the `DOM/`, `Parsing/`, `Selectors/`, and `Unit/` directories) remains the regression gate. All existing tests must continue to pass after every work package.
- `Node::$children` being `?array` is intentional (it is set to `null` by `clear()` to break circular references). The null guards in M-007 defend against callers invoking navigation methods on cleared nodes; they do not change the semantics of `clear()`.
- `Parser::$callback` typed as `mixed` is a PHP language limitation: `callable` is not valid as a standalone property type in any current PHP version. The fix is documentation-only (`@var callable|null` PHPDoc). This is not a runtime behaviour change.
- `dump_html_tree()` in the bridge file is used as a debugging helper; it is not exercised by any existing test. The B-003 regression test in WP-3 is the first coverage for it.
- The `SelectorParser` refactor (M-005) must not break the existing `Selectors/` test classes, which exercise `Node::find()` exhaustively.

---

## Constraints

- No BC-breaking changes. `Node::$nodetype` stays as `public int` (M-008 is deferred).
- All `src/SimpleHtmlDom/` files must retain `declare(strict_types=1)`.
- The bridge file `src/simple_html_dom.php` must not gain `declare(strict_types=1)` (it is a global-scope file; adding strict mode would require all call sites to pass correctly-typed arguments).
- New test files must be in the `SimpleHtmlDom\Tests\Unit` namespace (or a compatible namespace matching the existing `tests/Unit/` classes — verify from `tests/Unit/SettingsTest.php`).
- Do not modify any existing test file in `tests/DOM/`, `tests/Parsing/`, or `tests/Selectors/`. Only `tests/Unit/SettingsTest.php` may receive a small additive `tearDown()` change (L-002).

---

## Out of Scope

- M-008: Full enum adoption for `Node::$nodetype` — deferred; BC risk with consumers reading or writing the field as an integer.
- Changing observable parsing behaviour or selector semantics.
- Adding new selectors, new public API methods, or new constructor parameters.
- Adding a PSR-compliant logger to replace dead `$debugObject` global (removed in prior cycle; no replacement planned here).
- Providing a `changelog.md` update — that is the Documentation agent's responsibility.
- Any change to `app/` example directory.

---

## Acceptance Criteria

**WP-1 Bug Fixes**
- `Node::dump_node()` uses `$this->_[HDOM_INFO_INNER]` — no `$node` variable reference remains in that method.
- `Node::dump_node()` contains no `isset($this->text)` block.
- `dump_html_tree($node, false, 0)` calls `$node->dump(false, 0)` — verified by inspection and by the regression test in WP-3.
- `Parser::read_tag()` uses `($pos = strpos($tag, '<')) !== false` with explicit parentheses.
- All existing PHPUnit tests pass after WP-1.

**WP-2 PHP 8.4 Modernization Completions**
- No PHP 8.4 deprecation notice is emitted for `optional_closing_array` dynamic property; `Parser` declares `private ?array $optionalClosingArray = null`.
- All four bridge-file procedural functions carry typed parameter and return type declarations.
- `Parser::parse_attr()` uses a `match` expression; no `switch` statement remains in that method.
- `Parser::$callback` has a `@var callable|null` PHPDoc annotation.
- `Node::find()`, `Node::seek()`, and `Node::match()` share a single `SelectorParser` instance per `find()` call — at most one instantiation per call.
- `Parser::createElement()` and `Parser::createTextNode()` instantiate `Parser` directly; no `@str_get_html()` calls remain.
- `Node::first_child()`, `last_child()`, `next_sibling()`, `prev_sibling()` use `?? []` to guard against null `$children`.
- All existing PHPUnit tests pass after WP-2.
- PHPUnit run produces zero deprecation notices under PHP 8.4.

**WP-3 New Unit Tests**
- `tests/Unit/ParserTest.php` exists and all its test methods pass.
- `tests/Unit/NodeTest.php` exists and all its test methods pass.
- Regression for B-003: `testDumpHtmlTree()` passes without `TypeError`.
- Regression for B-001/B-002: `testDumpNodeRegressionB001B002()` passes without warning.
- Regression for M-007: `testNullChildrenAfterClear()` passes without `TypeError`.

**WP-4 Minor Improvements**
- `Error::__toString()` returns `"[{code}] {message}"`.
- `Settings::reset()` clears all settings and is callable without error.
- `SettingsTest.php` calls `Settings::reset()` in `tearDown()`.
- All existing and new PHPUnit tests pass after WP-4.

---

## Testing Strategy

**Regression gate**: After each work package, the full PHPUnit suite (`vendor/bin/phpunit`) must pass with zero failures, zero errors, and zero deprecation notices. The existing 209 tests in `DOM/`, `Parsing/`, and `Selectors/` serve as the primary regression harness.

**New tests (WP-3)**: `ParserTest` exercises `Parser` at the integration boundary (real HTML in, `Node` objects out) without mocking. `NodeTest` exercises `Node` methods in isolation by constructing a `Parser` with a minimal fixture and extracting specific nodes. Both test classes focus on the methods most likely to regress under the changes made in WP-1 and WP-2.

**Deprecation-notice detection**: Configure PHPUnit to convert `E_DEPRECATED` to test errors (this is the default in PHPUnit 12 when `convertDeprecationsToExceptions` is set in `phpunit.xml`). Confirm this setting is present before WP-2 begins so that M-001 and M-004 are validated automatically.

---

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| **M-005 SelectorParser threading breaks subclass overrides of `seek()` / `match()`** | Make the `SelectorParser` parameter optional (`?SelectorParser $parser = null`) in both `seek()` and `match()`. Any subclass that overrides these methods without the new parameter will still work (the base implementation creates a new instance as before). |
| **M-001 `optionalClosingArray` property rename causes a silent behaviour change if any test or consumer accesses `$parser->optional_closing_array` directly** | Grep the entire test suite and `src/` for `optional_closing_array` before renaming. If found in tests, update those references (they would be in the new test files added in WP-3, not in the existing 209 tests). The existing tests do not access this property directly. |
| **M-006 `createElement()` / `createTextNode()` return type change from `mixed` to `Node\|false` is a BC concern** | The previous return type was `mixed`, so narrowing to `Node\|false` is safe for consumers that already handled `mixed`. It is a BC concern only for consumers who relied on these methods being callable in a mixed-type context without null-checking. This is an accepted modernization trade-off documented here. |
| **B-004 parentheses fix changes actual behaviour of `read_tag()` for HTML containing `<tag<other>`** | Before the fix, `$pos` was `true` (cast to `1`), which is a wrong but possibly consistent behaviour. After the fix, `$pos` is the real byte offset. Test with the existing `Parsing/InvalidHtmlTest` class which exercises malformed markup to confirm no regressions. |
| **L-002 `Settings::reset()` called in test teardown races with parallel test execution** | PHPUnit runs test methods sequentially within a class by default. If test parallelism (process isolation) is used, each process has its own `Settings` state. No race condition risk. |
