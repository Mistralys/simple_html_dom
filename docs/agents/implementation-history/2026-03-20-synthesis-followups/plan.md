# Plan

## Summary

Address all actionable items identified in the synthesis report for the `maintenance-modernization`
branch. The work covers five discrete bug-fixes and one structural-improvement task: (1) correcting
`testDumpNodeNullInnerInfo` so it exercises the actual null-branch of `Node::dump_node()`; (2)
silencing the `Array to string conversion` PHP warning inside `Node::dump_node()` for nested-array
`$_` entries; (3) repairing four pre-existing test failures and one risky-test classification; and
(4) capturing a deferred plan for a broader camelCase naming-convention pass across `Parser`. Items
1–3 are self-contained code fixes that can be executed in a single work-package. Item 4 is
planning-only and results in a new plan document, not code changes.

---

## Architectural Context

The library lives under `src/SimpleHtmlDom/` as a set of namespaced classes:

- `src/SimpleHtmlDom/Parser.php` — HTML tokeniser and document root; owns the `$_` node-info
  array structure, error-handler registration, and the `load()` method whose `string` type
  declaration is the root cause of the `testNullInput` failure.
- `src/SimpleHtmlDom/Node.php` — single DOM node; `dump_node()` iterates `$_` and writes debug
  output; `__get()` routes attribute access through `convert_text()`; `convert_text()` is
  `string`-typed, causing a `TypeError` when a boolean attribute value (e.g. `checked = true`) is
  passed.
- `src/SimpleHtmlDom/TextConverter.php` — stateless charset-conversion helper; `isUtf8()` contains
  an off-by-one in its leading-byte comparison (`> 128` instead of `>= 128`) that causes byte
  `0x80` (a lone continuation byte) to be reported as valid UTF-8.
- `tests/Unit/DumpTest.php` — unit tests for `dump()` / `dump_node()`.
- `tests/Unit/ParserTest.php` — unit tests for `Parser`; contains `testForceTagsClosedFalse` which
  restores the error handler incorrectly, leaving PHPUnit's handler-stack check out of balance.
- `tests/Unit/TextConverterTest.php` — unit tests for `TextConverter::isUtf8()`.
- `tests/DOM/DomTreeTest.php` — integration tests using the legacy `\simple_html_dom` bridge alias;
  `testNoValueAttributes` and `testCamelCaseDomApi` trigger the `convert_text` `TypeError` by
  reading a boolean-valued attribute (`checked = true`) through `Node::__get()`.
- `tests/Parsing/StandardTest.php` — integration tests using the legacy alias; `testNullInput`
  passes `null` to `load()`.

The `$_` node-info array uses integer keys mapping to `HDOM_INFO_*` constants:
`0=BEGIN`, `1=END`, `2=QUOTE`, `3=SPACE`, `4=TEXT`, `5=INNER`, `6=OUTER`, `7=ENDSPACE`.
`HDOM_INFO_QUOTE` (2) is a flat array of integers; `HDOM_INFO_SPACE` (3) is an array of
three-element string arrays. The nested structure of `HDOM_INFO_SPACE` is what causes the
`Array to string conversion` warning in `dump_node()`.

---

## Approach / Architecture

All fixes are minimal, surgical, and self-contained. No new classes or files are needed. The plan
is split into four logical groups corresponding to the synthesis items:

**Group A — Test fix: `testDumpNodeNullInnerInfo` (FU-1)**
Replace the `<br>` fixture (which has `HDOM_INFO_INNER` set to the default BR text) with `<hr>`
(a self-closing tag that the parser never assigns `HDOM_INFO_INNER`). Add an explicit assertion
`assertStringContainsString(' NULL ', $result)` to verify the else-branch of `dump_node()`. The
existing `assertStringContainsString('HDOM_INNER_INFO', $result)` can remain as a structural guard.
As an optional improvement, tighten `testDumpNodeReturnMode`'s trivially-broad
`assertStringContainsString('a', $result)` to something more discriminating (e.g. `'[href]'`).

**Group B — Source fix: `Node::dump_node()` array warning (FU-2)**
The inner loop in `dump_node()` already guards the outer `$v` level with `is_array($v)`. The
problem is that for `HDOM_INFO_SPACE` the inner values (`$v2`) are themselves three-element arrays.
The guard must be extended one level deeper: wrap the `$v2` concatenation in an `is_array($v2)`
check, and when true emit `implode(', ', $v2)` (or a bracketed representation) instead of
attempting string concatenation. This does not change visible semantics — it is a debug output
method — so any readable serialisation of the nested array is acceptable.

**Group C — Source and test fixes: pre-existing failures (3 errors + 1 failure + 1 risky)**

1. `Tests\Parsing\StandardTest::testNullInput` — `Parser::load()` has `string $str` in its
   signature. The legacy API accepted `null`. Fix the test: the test's intent is to confirm that
   `null` input is handled; either (a) make the test assert the TypeError via `expectException`,
   or (b) change the test to use `''` (empty string) since `testEmptyString` already covers that
   path, or (c) broaden `load()`'s parameter to `string|null $str` and handle null as empty string
   inside the method. Option (c) is preferred because it preserves legacy API compatibility and
   matches the constructor's `if ($str)` guard which already implies null tolerance. The method
   body already routes through `$this->prepare($str, ...)` which calls `strlen($str)` — so null
   must be coerced to `''` before the prepare call.

2. `Tests\DOM\DomTreeTest::testNoValueAttributes` and `testCamelCaseDomApi` — Both fail because
   `Node::__get()` calls `convert_text($this->attr[$name])` where `$this->attr[$name]` can be
   `true` (boolean) for no-value attributes (`checked`, `selected`, etc.). `convert_text()` has
   `string $text` in its signature, causing a `TypeError`. Fix `Node::__get()`: before calling
   `convert_text()`, check if the attribute value is a boolean and return it directly (booleans
   are meaningful — `true` signals presence, `false`/`null` signals absence). The corrected
   `__get()` logic:
   ```php
   if (isset($this->attr[$name])) {
       $val = $this->attr[$name];
       if (is_bool($val)) {
           return $val;
       }
       return $this->convert_text($val);
   }
   ```
   This mirrors the intent of `__isset()` which already treats boolean values as presence flags,
   and it aligns with `makeup()` which explicitly checks `$val === true` for no-value attributes.

3. `Tests\Unit\TextConverterTest::testIsUtf8WithInvalidSequence` — `TextConverter::isUtf8()`
   uses `if ($c > 128)` but byte `0x80` (decimal 128) is a lone continuation byte — invalid
   UTF-8 — yet the condition `128 > 128` is `false`, so it passes as if it were valid ASCII.
   Fix: change `$c > 128` to `$c >= 128`. This single-character change correctly catches all
   continuation bytes (0x80–0xBF) that appear without a valid leading byte.

4. `Tests\Unit\ParserTest::testForceTagsClosedFalse` — PHPUnit 12 flags the test as risky because
   it calls `set_error_handler()` in the try block and then calls `set_error_handler($previous)`
   in the finally block. When `$previous` is `null` (no prior handler), this removes handlers from
   the stack in a way PHPUnit counts as an imbalance. Fix: replace `set_error_handler($previous)`
   in the `finally` block with `restore_error_handler()`. This is the idiomatic PHP way to undo a
   `set_error_handler()` call regardless of what `$previous` is.

**Group D — Deferred plan: naming convention sweep (FU-3)**
The three private methods added in the previous sprint (`parseDoubleQuotedAttr`,
`parseSingleQuotedAttr`, `parseUnquotedAttr`) use `camelCase` in a class whose other methods use
`snake_case`. Also noted: the inherited `// next` comments in those methods are now redundant since
they were copied from the inline context and no longer needed. This item should be a standalone
plan documenting the full scope of the naming sweep rather than an ad-hoc fix. The engineer
executing this plan should create a new plan file at
`docs/agents/plans/2026-03-20-naming-convention-sweep/plan.md` (or similar) as a separate
deliverable; no code changes for this item are in scope here.

---

## Rationale

- **No behaviour changes to the public API** from Groups A, B, and the risky-test fix — these are
  purely defensive hardening.
- **`load(string|null)` over test-change-only** for `testNullInput`: the legacy global API never
  had strict typing; callers may pass `null`. Making `load()` accept `null` is a one-line guard
  that costs nothing and preserves backward compatibility. Changing only the test to
  `expectException(TypeError::class)` would encode a regression as the expected behaviour.
- **Returning `bool` directly from `__get()`** rather than casting to string: the existing code in
  `makeup()` and `__isset()` treats `true`/`false` as presence-absence signals, not strings.
  Casting would corrupt that semantic. `getAttribute()` (camelCase alias) delegates to `__get()`,
  so callers expecting a truthy value from `$node->checked` rely on this.
- **`>= 128` not `> 128`** in `isUtf8()`: the fix closes the documented test case exactly, and
  the full range 0x80–0xBF (continuation bytes that cannot legally open a sequence) becomes
  correctly rejected.
- **`restore_error_handler()`** is the PHP-idiomatic undo of `set_error_handler()`, independent
  of whether a prior handler existed.

---

## Detailed Steps

1. **`src/SimpleHtmlDom/Node.php` — fix `__get()` for boolean attribute values**
   In `Node::__get()`, before calling `convert_text()`, add an `is_bool()` guard:
   ```php
   if (isset($this->attr[$name])) {
       $val = $this->attr[$name];
       if (is_bool($val)) {
           return $val;
       }
       return $this->convert_text($val);
   }
   ```

2. **`src/SimpleHtmlDom/Node.php` — fix `dump_node()` array warning**
   In the inner loop of `dump_node()` (lines ~126-129), wrap the `$v2` concatenation:
   ```php
   foreach ($v as $k2 => $v2) {
       if (is_array($v2)) {
           $string .= "[{$k2}]=>[" . implode(', ', $v2) . "], ";
       } else {
           $string .= "[{$k2}]=>\"" . $v2 . '", ';
       }
   }
   ```

3. **`src/SimpleHtmlDom/Parser.php` — allow `null` in `load()`**
   Change the method signature from `load(string $str, ...)` to `load(string|null $str, ...)` and
   add at the very top of the method body:
   ```php
   $str ??= '';
   ```

4. **`src/SimpleHtmlDom/TextConverter.php` — fix off-by-one in `isUtf8()`**
   Change line 59 from `if ($c > 128)` to `if ($c >= 128)`.

5. **`tests/Unit/ParserTest.php` — fix risky test error-handler cleanup**
   In `testForceTagsClosedFalse`, replace the `finally` body:
   ```php
   // Before:
   set_error_handler($previous);
   // After:
   restore_error_handler();
   ```
   Remove the `$previous` variable assignment entirely since it is no longer used.

6. **`tests/Unit/DumpTest.php` — fix `testDumpNodeNullInnerInfo`**
   Replace the `<br>` fixture with `<hr>` (which has no `HDOM_INFO_INNER` set):
   ```php
   $parser = $this->parse('<hr>');
   $node   = $parser->find('hr', 0);
   ```
   Add the explicit null-branch assertion:
   ```php
   $this->assertStringContainsString(' NULL ', $result);
   ```

7. **`tests/Unit/DumpTest.php` — tighten `testDumpNodeReturnMode` (optional improvement)**
   Replace the trivially-broad `assertStringContainsString('a', $result)` with a more
   discriminating assertion such as `assertStringContainsString('[href]', $result)`.

8. **Create deferred plan document (FU-3)**
   Create `docs/agents/plans/2026-03-20-naming-convention-sweep/plan.md` documenting the scope of
   the naming-convention pass: rename `parseDoubleQuotedAttr`, `parseSingleQuotedAttr`,
   `parseUnquotedAttr` to `parse_double_quoted_attr`, `parse_single_quoted_attr`,
   `parse_unquoted_attr` in `Parser.php`; remove the now-redundant `// next` comments in those
   methods; assess whether any other `camelCase` methods in `Parser` or other classes warrant
   renaming. This plan document is the only deliverable for step 8 — no code changes.

---

## Dependencies

- Steps 1–7 have no inter-dependencies and can be executed in any order by a single agent in one
  pass.
- Step 8 (deferred plan) can be done concurrently or after steps 1–7; it produces a plan document
  only.

---

## Required Components

Existing files to be modified:

- `src/SimpleHtmlDom/Node.php` (steps 1, 2)
- `src/SimpleHtmlDom/Parser.php` (step 3)
- `src/SimpleHtmlDom/TextConverter.php` (step 4)
- `tests/Unit/ParserTest.php` (step 5)
- `tests/Unit/DumpTest.php` (steps 6, 7)

New files to be created:

- `docs/agents/plans/2026-03-20-naming-convention-sweep/plan.md` (step 8, plan document only)

---

## Assumptions

- PHP 8.4+ strict-types mode is in effect throughout; all type-signature changes must remain
  compatible with strict mode.
- The legacy bridge (`\simple_html_dom` alias to `Parser`) must continue to work; `testNullInput`
  uses the alias. Making `load()` accept `null` is therefore a source change, not a test-only fix.
- `dump_node()` is a debug/diagnostic method — the exact format of nested-array serialisation is
  not part of the public API. Using `implode(', ', $v2)` for inner arrays is sufficient.
- `HDOM_INFO_INNER` is genuinely absent for `<hr>` and `<input>` self-closing tags not assigned
  by the parser's BR-text special case (confirmed by local inspection).

---

## Constraints

- Do not alter any public method signatures beyond adding `null` to the `load()` union type.
- Do not change test expectations for passing tests.
- The FU-3 naming sweep is explicitly out of scope for implementation in this plan.

---

## Out of Scope

- FU-3 naming convention sweep implementation (deferred; plan document only in step 8).
- The redundant `// next` comment removal from `parseDoubleQuotedAttr` / `parseSingleQuotedAttr`
  (minor; may be included in the naming sweep plan as a low-effort companion fix).
- Any refactoring of `dump_node()` beyond silencing the array warning.
- Addressing PHPUnit deprecation warnings (3 warnings shown in test output) — these are
  framework-level notices, not actionable items from this synthesis report.

---

## Acceptance Criteria

- `Tests\Parsing\StandardTest::testNullInput` passes without `TypeError`.
- `Tests\DOM\DomTreeTest::testNoValueAttributes` passes without `TypeError`.
- `Tests\DOM\DomTreeTest::testCamelCaseDomApi` passes without `TypeError`.
- `Tests\Unit\TextConverterTest::testIsUtf8WithInvalidSequence` asserts false (invalid UTF-8
  detected correctly).
- `Tests\Unit\ParserTest::testForceTagsClosedFalse` passes and is no longer flagged as risky.
- `Tests\Unit\DumpTest::testDumpNodeNullInnerInfo` asserts `assertStringContainsString(' NULL ', $result)`.
- `Node::dump_node()` emits no `Array to string conversion` PHP warning for any node with
  attributes (verified by a warning-trapping assertion or manual inspection).
- All 274 tests pass with 0 errors, 0 failures, 0 risky tests.
- `docs/agents/plans/2026-03-20-naming-convention-sweep/plan.md` exists and documents the
  naming-convention sweep scope.

---

## Testing Strategy

All changes are verified by the existing PHPUnit test suite (`./vendor/bin/phpunit`). No new test
files need to be created — the fixes to existing tests are the tests. The engineer should run the
full suite before and after each change to confirm the diff is clean.

For the `dump_node()` array warning (FU-2), add a brief inline `set_error_handler` check in
`testDumpNodeReturnMode` (or a new focused test) that asserts no `E_WARNING` is emitted when
calling `dump_node(false)` on a node with attributes.

---

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **Making `load()` accept `null` could silently swallow programmer errors** | The guard `$str ??= ''` is equivalent to the constructor's existing `if ($str)` — null has always been tolerated at the constructor level. Document the coercion in a docblock comment. |
| **`is_bool()` guard in `__get()` could break callers that expect string `"1"` from `$node->checked`** | Boolean attribute values are already handled as `true`/`false` throughout the codebase (`makeup()`, `__isset()`, `testNoValueAttributes`). String coercion is not the established contract. |
| **Changing `> 128` to `>= 128` in `isUtf8()` could affect callers relying on the existing (buggy) behaviour** | `isUtf8()` is only called from `TextConverter::convert()` to decide whether to skip an `iconv()` call. The stricter check means a string with a lone `0x80` byte would no longer skip conversion — the correct behaviour. |
| **`restore_error_handler()` in the `finally` may not fully undo nested handler installations** | The test installs exactly one handler; `restore_error_handler()` pops exactly one handler — the pair is balanced. This is the same pattern PHPUnit itself uses internally. |
