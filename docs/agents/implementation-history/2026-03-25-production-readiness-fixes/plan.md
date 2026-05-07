# Plan

## Summary

Address all actionable findings from the production-readiness audit. The work covers six areas: resolving PHPStan static analysis errors, handling `iconv()` failure in `TextConverter`, guarding against post-`clear()` node access, fixing `removeAttribute()` metadata leak, correcting manifest inaccuracies, and adding targeted tests for the new guard/fix code paths.

## Architectural Context

The library consists of 9 source files under `src/SimpleHtmlDom/` (3 enums, 6 classes) plus a bridge file `src/simple_html_dom.php`. PHPStan is configured at Level 6 via `phpstan.neon`. The project manifest lives under `docs/agents/project-manifest/` with five documents that must be kept in sync with code changes.

Key files affected by this plan:

- `src/SimpleHtmlDom/Node.php` — DOM node class (654 LOC). Has untyped array properties, null-unsafe `$this->dom->` calls after `clear()`, and a `removeAttribute()` that delegates to `__set($name, null)` instead of `__unset($name)`.
- `src/SimpleHtmlDom/Parser.php` — Parser/document root (826 LOC). Has untyped array properties.
- `src/SimpleHtmlDom/SelectorParser.php` — Selector engine (212 LOC). Has a by-ref parameter type mismatch.
- `src/SimpleHtmlDom/Settings.php` — Static settings store (48 LOC). Has untyped `$settings` property.
- `src/SimpleHtmlDom/TextConverter.php` — Charset converter (90 LOC). Unchecked `iconv()` return value.
- `docs/agents/project-manifest/tech-stack.md` — States no LICENSE file exists/no license in `composer.json` (both false).
- `docs/agents/project-manifest/constraints.md` — No mention of post-`clear()` undefined behavior.

## Approach / Architecture

The fixes are grouped into independent steps that can be implemented and tested sequentially. No architectural changes are required — all fixes are localized edits within existing files.

1. **PHPStan type annotations** — Add `@var` PHPDoc annotations to array properties and `@param`/`@return` generics to method signatures to satisfy Level 6. Fix the `SelectorParser::seek()` by-ref parameter type. Address the two `foreach.emptyArray` and one `isset.offset` warnings in `Node::find()`.
2. **`iconv()` failure handling** — Add a `false` check on the `iconv()` return in `TextConverter::convert()` and fall back to the original text.
3. **Post-`clear()` null guards** — Add early returns in `Node::innertext()`, `Node::outertext()`, `Node::text()`, and `Node::makeup()` when `$this->dom` is null.
4. **`removeAttribute()` fix** — Delegate to `__unset()` instead of `__set($name, null)` so the attribute is fully removed from `$attr`, `HDOM_INFO_SPACE`, and `HDOM_INFO_QUOTE`.
5. **Manifest corrections** — Update `tech-stack.md` to reflect that a LICENSE file exists and that `composer.json` declares `"license": "MIT"`. Add dev dependencies `phpstan/phpstan` and `phpstan/phpstan-phpunit` to the manifest. Add a note about post-`clear()` behavior to `constraints.md`.
6. **Tests** — Add tests for the `iconv()` fallback, `removeAttribute()` cleanup, and post-`clear()` guard behavior.

## Rationale

- **PHPStan annotations over baseline:** Adding proper types improves IDE support and catches future regressions. A baseline file would only suppress warnings without adding value.
- **Fallback on `iconv()` failure rather than exception:** Throwing would be a backward-incompatible behavior change. Returning the original text is the safest degradation.
- **Early return on null `$dom`:** These methods are called during rendering. Returning an empty string for a cleared node is the least-surprise behavior and matches how `outertext()` already guards `$this->dom` for the callback check.
- **`removeAttribute()` → `__unset()`:** The existing `__unset()` already does the right thing. The current `__set(null)` path leaves orphaned metadata in the `_` info arrays. However, `__unset()` currently does not clean up `HDOM_INFO_SPACE`/`HDOM_INFO_QUOTE` entries either — but since `makeup()` skips `null`/`false` values by index and the info arrays are positional, removing entries by index would break alignment. The correct fix is to call `__unset()` (which removes from `$attr`) — `makeup()` already handles the rendering correctly by skipping null/false values at the corresponding index. This is the same approach the legacy library used.

## Detailed Steps

### Step 1: Add PHPStan type annotations

**Files:** `src/SimpleHtmlDom/Node.php`, `src/SimpleHtmlDom/Parser.php`, `src/SimpleHtmlDom/Settings.php`, `src/SimpleHtmlDom/SelectorParser.php`

1. **`Node.php`** — Add PHPDoc to properties:
   - `$attr`: `/** @var array<string, string|bool|null> */`
   - `$children`: `/** @var list<Node>|null */`
   - `$nodes`: `/** @var list<Node>|null */`
   - `$_`: `/** @var array<int, mixed> */`

2. **`Node.php`** — Add return/parameter types to methods:
   - `children()`: `@return list<Node>|Node|null`
   - `find()`: `@return list<Node>|Node|null`
   - `seek()`: `@param array<string, int> &$ret` and `@param list<mixed> $selector`
   - `parse_selector()`: matching return annotation
   - `get_display_size()`: `@return array{height: int, width: int}|false`
   - `getAllAttributes()`: `@return array<string, string|bool|null>`
   - `getElementsById()`, `getElementsByTagName()`, `childNodes()`: matching return annotations

3. **`Node.php`** — Fix the two `foreach.emptyArray` warnings at lines 465/476 and the `isset.offset` at line 486. These occur in `find()` where `$head` is initialized as `[]` and PHPStan can see the empty array flowing into the foreach. The fix is to ensure `$head` is typed as `array<int, int>` via a PHPDoc `@var` annotation at the point of initialization.

4. **`Parser.php`** — Add PHPDoc to properties:
   - `$nodes`: `/** @var list<Node> */`
   - `$optionalClosingArray`: `/** @var array<string, array<string, int>>|null */`
   - `$noise`: `/** @var array<string, string> */`

5. **`Parser.php`** — Add return/parameter types to methods:
   - `find()`: `@return list<Node>|Node|null`
   - `parse_attr()`: `@param list<string> &$space`
   - `childNodes()`: matching return annotation
   - `getElementsById()`, `getElementsByTagName()`: matching return annotations

6. **`Settings.php`** — `$settings`: `/** @var array<string, mixed> */`

7. **`SelectorParser.php`** — Fix the by-ref parameter type on `seek()`:
   - Change `@param array<int, int> $ret` to `@param-out array<int, int> $ret` to satisfy the `parameterByRef.type` rule.

8. Run `composer analyze` and verify 0 errors.

### Step 2: Handle `iconv()` failure in `TextConverter`

**File:** `src/SimpleHtmlDom/TextConverter.php`

1. In the `convert()` method, change:
   ```php
   $converted = iconv($sourceCharset, $targetCharset, $text);
   ```
   to:
   ```php
   $result = iconv($sourceCharset, $targetCharset, $text);
   if ($result !== false) {
       $converted = $result;
   }
   ```
   This falls back to the original `$text` (already assigned to `$converted`) when iconv fails.

### Step 3: Add null guards for post-`clear()` access

**File:** `src/SimpleHtmlDom/Node.php`

The following methods access `$this->dom->` without checking for null. After `clear()` is called, `$this->dom` is set to `null`. Add early returns:

1. **`innertext()`** (line ~290): After the `HDOM_INFO_INNER` check, before the `HDOM_INFO_TEXT` branch:
   ```php
   if (isset($this->_[HDOM_INFO_TEXT])) {
       if ($this->dom === null) {
           return $this->_[HDOM_INFO_TEXT];
       }
       return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
   }
   ```

2. **`outertext()`** (line ~318): Same pattern for the `HDOM_INFO_TEXT` branch. Also guard the `HDOM_INFO_BEGIN` node lookup and the `makeup()` call path.

3. **`text()`** (line ~348): Guard the `HDOM_TYPE_TEXT` case that calls `$this->dom->restore_noise()`.

4. **`makeup()`** (line ~399): Guard the `HDOM_INFO_TEXT` branch and the `restore_noise()` call near the end.

In each case, when `$this->dom` is null, return the raw text without noise restoration (the best available fallback).

### Step 4: Fix `removeAttribute()`

**File:** `src/SimpleHtmlDom/Node.php`

Change line 640:
```php
public function removeAttribute(string $name): void { $this->__set($name, null); }
```
to:
```php
public function removeAttribute(string $name): void { $this->__unset($name); }
```

This properly removes the attribute from `$this->attr` instead of setting it to `null`. The rendering in `makeup()` already handles both cases (skips `null`/`false` values), so this change only affects programmatic inspection of the `$attr` array — after `removeAttribute('class')`, `isset($node->attr['class'])` will now correctly return `false`.

### Step 5: Correct manifest documents

1. **`docs/agents/project-manifest/tech-stack.md`** — Update the Package Identity table:
   - Change `License` row from `MIT (declared in source file header; no \`LICENSE\` file or \`composer.json\` field)` to `MIT (declared in \`composer.json\` and \`LICENSE\` file)`

2. **`docs/agents/project-manifest/tech-stack.md`** — In the Dev dependencies section, add:
   - `phpstan/phpstan` `^2.1`
   - `phpstan/phpstan-phpunit` `^2.0`
   - `roave/security-advisories` `dev-latest`

3. **`docs/agents/project-manifest/constraints.md`** — Add a new section after "Memory Management":

   ```markdown
   ## Post-Clear Behavior

   - After `Node::clear()` or `Parser::clear()` is called, the node's `$dom` reference is set to `null`.
   - Accessing `innertext()`, `outertext()`, `text()`, or `makeup()` on a cleared node returns raw text without noise restoration.
   - Consumers should not rely on node output after calling `clear()` — treat it as end-of-lifecycle.
   ```

### Step 6: Add tests

**Files:** New or extended test files

1. **`tests/Unit/TextConverterTest.php`** — Add a test for `iconv()` failure fallback:
   - Call `TextConverter::convert($text, 'INVALID-CHARSET', 'UTF-8')` and assert the original text is returned unchanged.

2. **`tests/Unit/NodeTest.php`** — Add tests for:
   - `removeAttribute()`: Parse HTML with an attribute, call `removeAttribute()`, assert `isset($node->attr['...'])` returns `false` and that outertext renders correctly without the attribute.
   - Post-`clear()` access: Parse HTML, get a node, call `clear()` on it, then call `innertext()` and assert it returns a string (no fatal error).

3. Run `composer test` and verify all tests pass.
4. Run `composer analyze` and verify 0 errors.

## Dependencies

- Step 1 is independent and can be done first.
- Step 2 is independent.
- Step 3 is independent.
- Step 4 is independent.
- Step 5 is independent (manifest-only).
- Step 6 depends on Steps 2, 3, and 4 (tests validate those changes).

## Required Components

- `src/SimpleHtmlDom/Node.php` — Steps 1, 3, 4
- `src/SimpleHtmlDom/Parser.php` — Step 1
- `src/SimpleHtmlDom/Settings.php` — Step 1
- `src/SimpleHtmlDom/SelectorParser.php` — Step 1
- `src/SimpleHtmlDom/TextConverter.php` — Step 2
- `docs/agents/project-manifest/tech-stack.md` — Step 5
- `docs/agents/project-manifest/constraints.md` — Step 5
- `tests/Unit/TextConverterTest.php` — Step 6
- `tests/Unit/NodeTest.php` — Step 6

## Assumptions

- The 27 PHPStan errors are exclusively the ones identified in the audit (all `missingType.iterableValue`, `foreach.emptyArray`, `isset.offset`, and `parameterByRef.type`). No new errors have been introduced since the audit.
- `iconv()` with an invalid charset name returns `false` (documented PHP behavior) and does not throw.
- The `__unset()` method on `Node` is the correct removal mechanism and its behavior (removing from `$attr` only, not from `HDOM_INFO_*` arrays) is intentional in the original architecture.

## Constraints

- All changes must preserve backward compatibility with the legacy procedural API.
- PHPStan must pass at Level 6 with zero errors after Step 1.
- All 275 existing tests must continue to pass after every step.
- No new dependencies may be introduced.
- PHP 8.4+ minimum version requirement is maintained.

## Out of Scope

- Adding code coverage tooling (Xdebug/PCOV installation).
- SSRF mitigation in `file_get_html()` / `Parser::load_file()` — documented as consumer responsibility.
- Redirect URL validation in `file_get_html()` — documented as consumer responsibility.
- `Parser::createElement()` performance optimization — functional but not high-frequency in typical usage.
- Noise placeholder key-space overflow (theoretical, not practical).
- Adding `@param-out` or phpstan baseline for the `compress.zlib://` wrapper or `preg_match` false-return edge cases.

## Acceptance Criteria

- `composer analyze` reports 0 errors at Level 6.
- `composer test` reports 275+ tests passing with 0 failures.
- `TextConverter::convert()` returns the original string when `iconv()` fails (verified by new test).
- `Node::removeAttribute()` fully removes the attribute from `$attr` (verified by new test).
- Accessing node content methods after `clear()` returns a string without fatal errors (verified by new test).
- `tech-stack.md` accurately reflects the license and dev dependency status.
- `constraints.md` documents post-`clear()` behavior.

## Testing Strategy

- **Unit tests** for the `iconv()` fallback in `TextConverterTest`: use an intentionally invalid source charset to trigger `iconv()` failure.
- **Unit tests** for `removeAttribute()` in `NodeTest`: parse a tag with known attributes, remove one, verify removal from `$attr` and correct rendering.
- **Unit tests** for post-`clear()` resilience in `NodeTest`: parse HTML, obtain a node reference, call `clear()`, then call `innertext()` / `outertext()` and assert no crash.
- **Regression**: Run the full test suite (`composer test`) after each step to ensure no existing behavior is broken.
- **Static analysis**: Run `composer analyze` after Step 1 and again after the final step.

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **PHPDoc type annotations break existing PHPStan analysis** | Run `composer analyze` after each annotation batch; fix iteratively. |
| **`iconv()` fallback masks real encoding problems** | The fallback only applies when `iconv()` returns `false` (conversion impossible). The original text is the best available data in this case. Log-level warnings are out of scope for this library. |
| **Null guards in rendering methods change output for edge cases** | The guards only activate when `$dom` is null (post-`clear()`). Before this fix, the same scenario caused a fatal error — returning raw text is strictly better. |
| **`removeAttribute()` behavioral change** | The only difference is that `$node->attr` no longer contains a `null` entry for the removed attribute. Any code checking `isset($node->attr[$name])` now gets `false` instead of `true` — which is the correct semantic. `makeup()` rendering is unaffected. |
| **Manifest edits conflict with other manifest updates** | Manifest changes are isolated to specific sections; merge conflicts are unlikely. |
