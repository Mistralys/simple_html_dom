# Plan

## Summary

Two maintenance items drawn from the strategic recommendations of the `maintenance-modernization`
synthesis report. Item 1 refactors the IIFE closures inside `Parser::parse_attr()` into named
private helper methods, improving readability and making the individual parsing arms independently
testable. Item 4 adds a dedicated test class for the dump/debug output paths (`Node::dump()`,
`Node::dump_node()`, and the `dump_html_tree()` procedural bridge function), which currently have
only incidental regression coverage. Both changes are backward-compatible with no public API
surface changes.

---

## Architectural Context

### Source layout

The library lives in `src/SimpleHtmlDom/` (namespaced, PHP 8.4):

| File | Relevant contents |
|---|---|
| `src/SimpleHtmlDom/Parser.php` | `Parser` class — HTML document root, tokenizer, attribute parser |
| `src/SimpleHtmlDom/Node.php` | `Node` class — single DOM node, debug/dump methods |
| `src/simple_html_dom.php` | Bridge file — legacy constants, class aliases, procedural helpers including `dump_html_tree()` |

### Attribute parsing (Item 1)

`Parser::parse_attr()` (lines 565–599 of `Parser.php`) is a `protected` method called from
`read_tag()`. After consuming leading whitespace into `$space[2]`, it dispatches on the current
character via a `match` expression. Each arm is written as an IIFE (Immediately Invoked Function
Expression) closure:

```php
match ($this->char) {
    '"'     => (function () use ($node, $name): void { /* double-quote arm */ })(),
    "'"     => (function () use ($node, $name): void { /* single-quote arm */ })(),
    default => (function () use ($node, $name): void { /* unquoted arm   */ })(),
};
```

The three arms access `$this` through the closure (each closure binds to the surrounding object
automatically in PHP because the method is not static) and mutate `$node->attr`, `$node->_`, and
`$this->pos`/`$this->char`. The IIFE pattern adds visual noise without any functional benefit —
PHP `match` arms can call methods directly.

Relevant constants used inside the arms:
- `HDOM_QUOTE_DOUBLE`, `HDOM_QUOTE_SINGLE`, `HDOM_QUOTE_NO` (from `src/simple_html_dom.php` bridge)
- `self::TOKEN_ATTR` (private class constant in `Parser`)

Parser methods called inside the arms:
- `$this->copy_until_char_escape('"')` / `$this->copy_until_char_escape("'")`
- `$this->restore_noise(...)`
- `$this->copy_until(self::TOKEN_ATTR)`

### Dump/debug paths (Item 4)

Three closely related callables exist:

| Symbol | Location | Signature | Behaviour |
|---|---|---|---|
| `Node::dump()` | `Node.php` lines 87–106 | `dump(bool $show_attr = true, int $deep = 0): void` | Echoes a recursive tree of tag names with optional attribute list; indents by `$deep`. |
| `Node::dump_node()` | `Node.php` lines 111–155 | `dump_node(bool $echo = true): ?string` | Builds a one-line debug string for a single node covering tag, attr, `$_` info-array, `HDOM_INFO_INNER`, child/node counts, and `tag_start`. When `$echo=true` prints it; when `false` returns it. |
| `dump_html_tree()` | `src/simple_html_dom.php` line 225 | `dump_html_tree(Node $node, bool $show_attr = true, int $deep = 0): void` | Procedural bridge that delegates to `$node->dump($show_attr, $deep)`. |

Existing coverage:
- `tests/Unit/NodeTest.php::testDumpNodeRegressionB001B002` — verifies `dump_node(false)` returns
  a non-empty string containing the tag name, for a single-attribute `<div>`.
- `tests/Unit/NodeTest.php::testDumpHtmlTree` — regression for B-003, verifies `dump_html_tree()`
  does not throw a `TypeError` and produces output.

Neither test validates the actual format/content of the output beyond `assertStringContainsString('div', ...)`.

### Test layout and conventions

| Directory | Namespace | Style |
|---|---|---|
| `tests/Unit/` | `Tests\Unit` | Pure unit tests via `PHPUnit\Framework\TestCase`; use `new Parser(html)` helper pattern |
| `tests/DOM/` | `Tests\DOM` | DOM-level integration; use `\simple_html_dom` alias via bridge |
| `tests/Parsing/` | `Tests\Parsing` | Parsing fidelity; use `\simple_html_dom` alias |
| `tests/Selectors/` | `Tests\Selectors` | Selector engine tests |

All test files declare `strict_types=1`. PHPUnit 10+ attributes style is used where needed
(e.g., `#[DataProvider]` in `StandardTest`). The test suite runs via `phpunit.xml` which
auto-discovers all four directories.

---

## Approach / Architecture

### Item 1 — Extract IIFE closures into private helper methods

Replace the three IIFE closures in `Parser::parse_attr()` with three new `private` methods on
`Parser`:

- `parseDoubleQuotedAttr(Node $node, string $name): void`
- `parseSingleQuotedAttr(Node $node, string $name): void`
- `parseUnquotedAttr(Node $node, string $name): void`

The `match` expression arms then become simple method calls:

```php
match ($this->char) {
    '"'     => $this->parseDoubleQuotedAttr($node, $name),
    "'"     => $this->parseSingleQuotedAttr($node, $name),
    default => $this->parseUnquotedAttr($node, $name),
};
```

This is a pure internal refactor: `parse_attr()` remains `protected`, the three new helpers are
`private`, and the public observable behaviour (attribute values, quote-style tracking in
`HDOM_INFO_QUOTE`, roundtrip fidelity) is identical. No changes to any public or protected method
signatures.

### Item 4 — Dedicated dump test class

Add `tests/Unit/DumpTest.php` in the `Tests\Unit` namespace. It covers `Node::dump()`,
`Node::dump_node()`, and `dump_html_tree()` across the following behaviours:

1. **`dump_node()` return mode** (`$echo = false`) — verifies the exact structural components of
   the output string for a simple element node.
2. **`dump_node()` echo mode** (`$echo = true`) — verifies the method echoes and returns `null`
   (using `ob_start()`/`ob_get_clean()`).
3. **`dump_node()` node with no attributes** — verifies the attr section is absent from the string.
4. **`dump_node()` node with the `HDOM_INFO_INNER` key populated** — verifies the inner-text
   section reflects the actual value.
5. **`dump_node()` node without `HDOM_INFO_INNER`** — verifies the ` NULL ` placeholder appears.
6. **`dump()` single node, attrs hidden** — verifies indentation/tag output without attribute block.
7. **`dump()` single node, attrs shown** — verifies the attribute key/value fragment appears in the
   echoed output.
8. **`dump()` recursive tree** — verifies nested indentation (child nodes produce deeper leading
   spaces).
9. **`dump_html_tree()` delegation** — verifies the procedural bridge produces the same output as
   calling `$node->dump()` directly (content equality).
10. **`dump_html_tree()` depth parameter** — verifies the `$deep` offset shifts indentation.

The test class pattern follows `NodeTest.php`: a private `parse(string $html): Parser` helper
method that wraps `new Parser($html)`.

---

## Rationale

- **Extracting the IIFE closures** removes an unusual PHP pattern that surprises maintainers, makes
  each arm's logic independently grep-able, and (importantly) makes each arm unit-testable by
  subclassing `Parser` in a future test if needed. The `match` expression retains its structural
  role as the dispatch mechanism — only the action per arm changes.
- **A dedicated dump test file** rather than adding more methods to the existing `NodeTest.php`
  keeps concerns separated. The dump/debug surface has distinct semantics (output formatting,
  echo-vs-return duality) that warrant their own test lifecycle. This also makes it straightforward
  to add snapshot-style assertions in the future.
- Both items avoid touching any public API, so backward compatibility is guaranteed by construction.

---

## Detailed Steps

### Item 1

1. Open `src/SimpleHtmlDom/Parser.php`.
2. Locate `parse_attr()` (line 565). Copy the body of the `'"'` arm out of its IIFE and into a new
   `private function parseDoubleQuotedAttr(Node $node, string $name): void` method below
   `parse_attr()`. The body is:
   ```php
   $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
   $this->char                 = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
   $node->attr[$name]          = $this->restore_noise($this->copy_until_char_escape('"'));
   $this->char                 = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
   ```
3. Repeat for `"'"` arm into `parseSingleQuotedAttr(Node $node, string $name): void`.
4. Repeat for the `default` arm into `parseUnquotedAttr(Node $node, string $name): void`:
   ```php
   $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
   $node->attr[$name]          = $this->restore_noise($this->copy_until(self::TOKEN_ATTR));
   ```
5. Replace the three IIFE closure arms in `parse_attr()` with method calls:
   ```php
   match ($this->char) {
       '"'     => $this->parseDoubleQuotedAttr($node, $name),
       "'"     => $this->parseSingleQuotedAttr($node, $name),
       default => $this->parseUnquotedAttr($node, $name),
   };
   ```
6. Run the full PHPUnit suite (`./vendor/bin/phpunit`) and verify all 229+ tests pass without
   change.

### Item 4

1. Create `tests/Unit/DumpTest.php` with namespace `Tests\Unit`.
2. Implement a private `parse(string $html): Parser` helper (mirrors `NodeTest.php`).
3. Write the ten test methods described in the Approach section above. Key implementation notes:
   - Capture echo output with `ob_start()` / `ob_get_clean()`.
   - For `dump_node()` content assertions, parse a known fixed HTML string (e.g.,
     `<a href="https://example.com" class="btn">click</a>`) and assert the following substrings
     are present in the returned string: the tag name `a`, the attribute key `href`, the substring
     `HDOM_INNER_INFO`, `children:`, `nodes:`, `tag_start:`.
   - For `dump()` tree depth, parse `<ul><li>item</li></ul>`, call `dump()` on the `ul` node, and
     verify the output contains a line starting with four leading spaces (the `li` child indented
     one level) as well as `li` and `ul`.
   - For `dump_html_tree()` equivalence, capture output from both `$node->dump(true, 0)` and
     `dump_html_tree($node, true, 0)` and assert they are identical strings.
   - Use `assertSame` for scalar equality, `assertStringContainsString` for substring presence, and
     `assertNull` where `dump_node(true)` return value is checked.
4. Run `./vendor/bin/phpunit --testsuite unit` and verify the new tests pass and the count increases
   by the number of test methods added.
5. Run the full suite to confirm no regressions.

---

## Dependencies

- PHPUnit (already present, configured in `phpunit.xml`).
- No new Composer packages required.
- Item 4 depends on Item 1 only in the trivial sense that they share the same PR; the new dump
  tests do not exercise `parse_attr()` at all. Either can be implemented independently.

---

## Required Components

**Modified files:**
- `src/SimpleHtmlDom/Parser.php` — add three `private` methods; simplify `parse_attr()`.

**New files:**
- `tests/Unit/DumpTest.php` — new dedicated dump/debug test class (new file).

---

## Assumptions

- The IIFE closures in `parse_attr()` do not rely on any closure-specific behaviour (late binding,
  `static`, etc.) — confirmed by reading the source: they use `use ($node, $name)` and call
  `$this->*` methods, all of which are identically accessible from a private method on the same
  class.
- PHPUnit is already bootstrapped via `vendor/autoload.php` (confirmed in `phpunit.xml`).
- The bridge file (`src/simple_html_dom.php`) is loaded by the autoloader in tests, making
  `dump_html_tree()` available in the global namespace.

---

## Constraints

- All changes must be backward-compatible — no public or protected API changes.
- The three new private methods in `Parser` must not be declared `static` (they access `$this`).
- The new test class must follow the existing file and namespace conventions (`declare(strict_types=1)`,
  namespace `Tests\Unit`, extend `PHPUnit\Framework\TestCase`).
- The full test suite (229+ tests) must remain green after each change.

---

## Out of Scope

- Changing `parse_attr()` visibility (it remains `protected`).
- Adding `@internal` annotations or other docblock changes not directly motivated by this refactor.
- Snapshot/golden-file testing of dump output (the plan uses substring assertions, which are
  maintainable without golden files).
- Any changes to `Node::dump()` or `Node::dump_node()` logic — the test plan exercises the current
  behaviour, not new behaviour.
- Covering `Parser::dump()` (the one-liner delegate on Parser that calls `$this->root->dump()`)
  as a separate case — it is covered implicitly via `Node::dump()` tests.

---

## Acceptance Criteria

**Item 1:**
- `Parser::parse_attr()` contains no anonymous function / closure literals.
- Three new `private` methods — `parseDoubleQuotedAttr`, `parseSingleQuotedAttr`,
  `parseUnquotedAttr` — exist on `Parser`, each accepting `(Node $node, string $name): void`.
- The `match` expression in `parse_attr()` calls those methods directly.
- All 229+ existing tests pass unchanged.

**Item 4:**
- `tests/Unit/DumpTest.php` exists with namespace `Tests\Unit`.
- The file contains at least 10 test methods covering `dump_node()` (return and echo modes,
  with/without attrs, with/without `HDOM_INFO_INNER`), `dump()` (attrs on/off, recursive depth),
  and `dump_html_tree()` (delegation and depth).
- All new tests pass.
- Total PHPUnit test count increases by the number of new test methods.
- No existing tests are broken.

---

## Testing Strategy

Item 1 is verified entirely by the existing test suite — it is a pure refactor with no behaviour
change, so a green suite after the change is sufficient evidence of correctness.

Item 4 introduces new tests by definition. Each test method is self-contained: it constructs a
small HTML fixture, obtains a `Node` via `Parser::find()`, invokes the dump method under test,
and asserts on the captured or returned string. Output-capturing tests use the standard PHPUnit
`ob_start()`/`ob_get_clean()` idiom already established in `NodeTest::testDumpHtmlTree()`.

---

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| **Closure `$this` binding is implicit in PHP — the extracted private methods are equivalent, but a subtle difference in binding could change behaviour.** | The IIFE closures are not `static`, so they already bind to `$this`. A `private` instance method has identical access. Run the full suite after extraction to confirm. |
| **`dump_node()` output format is undocumented and could change in a future refactor, making substring assertions brittle.** | Assertions target structural tokens (`HDOM_INNER_INFO`, `children:`, `nodes:`, `tag_start:`) that are literal strings in the code, not computed. If the format changes, the tests break intentionally — that is the desired safety net. |
| **`dump_html_tree()` is a global function — test isolation could be affected by autoload order.** | The PHPUnit bootstrap loads `vendor/autoload.php` which includes the bridge file unconditionally. Already proven by the existing `testDumpHtmlTree` test in `NodeTest.php`. |
