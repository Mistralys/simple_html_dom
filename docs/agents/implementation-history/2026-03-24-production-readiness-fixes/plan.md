# Plan

## Summary

Address all issues identified in the production-readiness audit of the Simple HTML DOM library. The library has been successfully upgraded from a legacy single-file architecture to a modern PSR-4 namespaced class structure with a backward-compatible bridge file. All 274 tests pass and the core architecture is sound, but 7 issues ranging from a security-relevant regex injection to missing license metadata need resolution before the library can be considered production-ready.

## Architectural Context

The library consists of 9 source files under `src/SimpleHtmlDom/` (3 enums, 6 classes) plus a bridge file at `src/simple_html_dom.php` that provides backward compatibility via `define()` constants, `class_alias()` mappings, and procedural wrapper functions.

Key files affected by this plan:
- `src/SimpleHtmlDom/SelectorParser.php` — CSS selector matching logic (regex injection fix)
- `src/SimpleHtmlDom/Node.php` — DOM node class (PHPStan annotation)
- `src/SimpleHtmlDom/Parser.php` — HTML tokeniser/parser (PHPStan fix)
- `src/SimpleHtmlDom/QuoteStyle.php` — Backed enum (documentation)
- `src/simple_html_dom.php` — Bridge file (redirect loop fix)
- `composer.json` — Package metadata (license field)
- `docs/agents/project-manifest/constraints.md` — Project constraints documentation

Static analysis: PHPStan Level 6, currently reporting 29 errors (26 cosmetic `missingType.iterableValue`, 2 real type errors, 1 parameter type mismatch).

Test suites: `unit`, `parsing`, `selectors`, `dom` — all in `tests/`.

## Approach / Architecture

All fixes are surgical, targeted changes. No architectural refactoring is needed. The work is organized by severity:

1. **Security fixes** — Patch the regex injection vector and add a redirect loop bound
2. **Static analysis fixes** — Resolve the 2 real PHPStan errors so the analysis is clean at Level 6 (cosmetic `missingType.iterableValue` warnings are acceptable at this level)
3. **Package metadata** — Add `license` field to `composer.json` and create a `LICENSE` file
4. **Documentation** — Add SSRF warning to constraints doc, add comment to `QuoteStyle` enum
5. **Manifest updates** — Update project manifest documents to reflect changes

## Rationale

- The regex injection in `*=` is the highest-priority fix because it causes both runtime errors on legitimate selector values and a potential ReDoS vector.
- The redirect loop bound is a straightforward robustness fix that prevents hanging on malicious or misconfigured servers.
- PHPStan errors should be resolved so that `composer analyze` passes cleanly — a production library should have no real static analysis errors.
- License metadata is required for Packagist listing and legal clarity.
- Documentation changes ensure consumers are warned about SSRF and future contributors understand the `QuoteStyle` gap.

## Detailed Steps

### Step 1: Fix regex injection in `SelectorParser::match()` `*=` operator

**File:** `src/SimpleHtmlDom/SelectorParser.php` (line ~211)

**Current code:**
```php
'*=' => ($pattern[0] === '/')
    ? (bool) preg_match($pattern, $value)
    : (bool) preg_match("/" . $pattern . "/i", $value),
```

**Fix:** Escape the non-regex branch with `preg_quote()`:
```php
'*=' => ($pattern[0] === '/')
    ? (bool) preg_match($pattern, $value)
    : (bool) preg_match("/" . preg_quote($pattern, '/') . "/i", $value),
```

**Testing:** Existing selector tests must still pass. Add a new test case in `tests/Selectors/SelectorTest.php` that verifies a selector with regex metacharacters in the value works correctly (e.g., `[attr*="foo(bar)"]`).

---

### Step 2: Add redirect loop bound in `file_get_html()`

**File:** `src/simple_html_dom.php` (lines ~82–103)

**Fix:** Add a `$maxRedirects = 5` counter before the `do` loop. Increment on each redirect. Break out of the loop when the counter is exceeded, allowing the existing "wrong response code" error handling to catch it.

```php
$maxRedirects = 5;
$redirectCount = 0;
do {
    $repeat = false;
    // ... existing code ...
    if (/* redirect detected */) {
        if (++$redirectCount > $maxRedirects) {
            break;
        }
        $url    = $matches[1];
        $repeat = true;
    }
} while ($repeat);
```

**Testing:** This is a network-dependent code path. Add a note in the code comment explaining the bound. No automated test required (would need HTTP mocking infrastructure that doesn't exist in this project).

---

### Step 3: Fix PHPStan error — `Parser::parse_charset()` line 299

**File:** `src/SimpleHtmlDom/Parser.php` (line ~298–299)

**Current code:**
```php
$el = $this->root->find('meta[http-equiv=Content-Type]', 0);
if (!empty($el)) {
    $fullvalue = $el->content;
```

**Issue:** `find()` returns `Node|array|null`, but passing `0` as `$idx` restricts it to `Node|null`. PHPStan cannot infer this from the union return type.

**Fix:** Add a type assertion:
```php
$el = $this->root->find('meta[http-equiv=Content-Type]', 0);
if ($el instanceof Node) {
    $fullvalue = $el->content;
```

This makes the intent explicit and resolves the PHPStan error.

---

### Step 4: Fix PHPStan error — `Node::$plaintext` undefined property

**File:** `src/SimpleHtmlDom/Node.php`

**Issue:** `$plaintext` is accessed via `__get()` magic, so PHPStan reports it as undefined.

**Fix:** Add a `@property-read` PHPDoc annotation to the `Node` class docblock:
```php
/**
 * ...existing docblock...
 *
 * @property-read string $plaintext
 */
class Node
```

---

### Step 5: Add `license` field to `composer.json`

**File:** `composer.json`

**Fix:** Add `"license": "MIT"` after the `"type"` field:
```json
"type": "library",
"license": "MIT",
```

---

### Step 6: Create `LICENSE` file at project root

**File:** `LICENSE` (new file)

Create a standard MIT license file with the original author (S.C. Chen) and current year range as copyright holders.

---

### Step 7: Add SSRF warning to constraints documentation

**File:** `docs/agents/project-manifest/constraints.md`

**Fix:** Add a new section after "Max File Size":

```markdown
## URL Loading Security

- `file_get_html()` and `Parser::load_file()` accept arbitrary URLs and pass them to `file_get_contents()`.
- If consumer code passes user-supplied URLs, this creates a Server-Side Request Forgery (SSRF) surface.
- Consumers **must** validate/whitelist URLs before passing them to these functions.
- The library intentionally does not restrict URLs — that is the consumer's responsibility.
```

---

### Step 8: Add explanatory comment to `QuoteStyle::None` value gap

**File:** `src/SimpleHtmlDom/QuoteStyle.php`

**Fix:** Add an inline comment explaining why value `2` is skipped:
```php
case None   = 3; // Value 2 is intentionally skipped to match the legacy HDOM_QUOTE_NO constant
```

---

### Step 9: Update project manifest documents

Per the manifest maintenance rules in `AGENTS.md`:

- **`api-surface.md`**: Add `@property-read string $plaintext` to the `Node` class section (Step 4).
- **`constraints.md`**: Already updated in Step 7 (SSRF warning). Also add a note about the redirect-follow limit (Step 2).
- **`data-flows.md`**: Update Flow #2 (Parse HTML from URL/File) to mention the 5-redirect limit.

---

### Step 10: Run verification

1. Run `composer test` — all 274+ tests must pass (including the new selector test from Step 1).
2. Run `composer analyze` — verify the 2 real PHPStan errors are resolved. The remaining errors should all be `missingType.iterableValue` warnings only.

## Dependencies

- Steps 1–8 are independent and can be executed in any order or in parallel.
- Step 9 (manifest updates) depends on Steps 2, 4, and 7 being finalized.
- Step 10 (verification) must run after all other steps.

## Required Components

- `src/SimpleHtmlDom/SelectorParser.php` — edit (Step 1)
- `src/simple_html_dom.php` — edit (Step 2)
- `src/SimpleHtmlDom/Parser.php` — edit (Step 3)
- `src/SimpleHtmlDom/Node.php` — edit (Step 4)
- `composer.json` — edit (Step 5)
- `LICENSE` — **new file** at project root (Step 6)
- `src/SimpleHtmlDom/QuoteStyle.php` — edit (Step 8)
- `docs/agents/project-manifest/constraints.md` — edit (Steps 7, 9)
- `docs/agents/project-manifest/api-surface.md` — edit (Step 9)
- `docs/agents/project-manifest/data-flows.md` — edit (Step 9)
- `tests/Selectors/SelectorTest.php` — edit (Step 1, new test case)

## Assumptions

- The `*=` selector's regex-pass-through behavior (when pattern starts with `/`) is an intentional feature and should be preserved.
- The `missingType.iterableValue` PHPStan warnings (26 of 29) are acceptable at Level 6 and are out of scope for this plan.
- The existing test suite adequately covers the bridge file's backward compatibility.
- The MIT license attribution should reference the original author (S.C. Chen) from the source headers.

## Constraints

- All changes must preserve backward compatibility with the legacy API (bridge file).
- PHP 8.4+ is the minimum supported version — no need to support older PHP.
- Both snake_case and camelCase method naming conventions must be maintained per the project's dual naming convention rule.
- `Settings::reset()` must be called in `tearDown()` for any new tests modifying `Parser` or `Node`.

## Out of Scope

- Resolving the 26 `missingType.iterableValue` PHPStan warnings (cosmetic at Level 6).
- Adding HTTP mocking infrastructure for testing `file_get_html()` network behavior.
- Upgrading the CSS selector engine (e.g., adding child/sibling combinators or pseudo-classes).
- Refactoring the noise system or parser architecture.
- Adding exception-based error handling (current error-object pattern is a deliberate design choice).

## Acceptance Criteria

- `composer test` passes with 0 failures (274+ tests).
- `composer analyze` reports only `missingType.iterableValue` warnings — no real type errors.
- The `*=` selector correctly handles values containing regex metacharacters (e.g., `(`, `[`, `+`).
- `file_get_html()` stops following redirects after 5 hops.
- `composer.json` has a `"license": "MIT"` field.
- A `LICENSE` file exists at the project root.
- `constraints.md` contains an SSRF warning section.
- Project manifest documents are consistent with all code changes.

## Testing Strategy

| Step | Test Approach |
|---|---|
| 1 (regex fix) | Add test case with metacharacter selector value; verify existing selector tests pass |
| 2 (redirect bound) | Manual verification via code review; no automated test (requires HTTP mocking) |
| 3 (PHPStan fix) | `composer analyze` should no longer report line 299 error |
| 4 (PHPStan fix) | `composer analyze` should no longer report line 316 error |
| 5–8 (metadata/docs) | Manual review |
| 10 (full suite) | `composer test` + `composer analyze` |

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **`preg_quote()` in `*=` changes existing behavior** | The unescaped path was a bug, not a feature. Existing tests will catch any regressions. The regex-pass-through path (starting with `/`) is preserved unchanged. |
| **Redirect bound breaks legitimate deep redirect chains** | 5 is a generous limit; RFC 7231 recommends clients handle at least 5 redirects. The bound can be made configurable later if needed. |
| **`instanceof Node` check changes `parse_charset()` behavior** | `!empty($el)` and `$el instanceof Node` are functionally equivalent when `find(..., 0)` returns `Node|null`. No behavior change. |
| **New test case fails on edge case** | The test targets a specific, well-understood code path. Review selector parser regex before writing the test. |
