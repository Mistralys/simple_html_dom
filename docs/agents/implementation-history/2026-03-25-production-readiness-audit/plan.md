# Plan

## Summary

Full production-readiness audit of the Simple HTML DOM library following its upgrade from a legacy single-file library to a modern PSR-4, class-based architecture. This plan identifies all remaining issues that block a production release, proposes fixes, and defines acceptance criteria. A prior sprint (2026-03-24) already resolved the critical security vulnerabilities and PHPStan errors. This plan addresses the **remaining 4 defects** — 3 null-safety bugs that cause PHP warnings or potential fatal errors, and 1 code artifact — plus a missing changelog entry.

## Architectural Context

The library comprises 9 source files under `src/SimpleHtmlDom/` (3 enums, 6 classes) plus a bridge file `src/simple_html_dom.php` that defines `HDOM_*` constants, `class_alias()` mappings, and 4 procedural functions. The bridge preserves full backward compatibility with the original single-file API.

Key files for this audit:

- `src/SimpleHtmlDom/Node.php` — DOM node class (~710 LOC). Contains 3 remaining null-safety defects and 1 stale code artifact.
- `src/SimpleHtmlDom/Parser.php` — HTML tokeniser and document root (~850 LOC). Clean.
- `src/SimpleHtmlDom/SelectorParser.php` — CSS selector engine (~215 LOC). Clean.
- `src/SimpleHtmlDom/TextConverter.php` — Charset converter (~90 LOC). Clean (`iconv()` failure handling already fixed).
- `src/simple_html_dom.php` — Bridge file. Clean (redirect limit already implemented).
- `changelog.md` — Missing entries for the v2.1 → v2.2 changes from the 2026-03-24 security/quality sprint.

**Current quality gates:**
- PHPUnit: **278 tests, 1194 assertions, 0 failures, 1 PHP warning** (Node.php:304 `foreach()` on null)
- PHPStan Level 6: **0 errors** (23 `missingType.iterableValue` warnings acceptable at this level)

## Audit Findings

### Already Resolved (2026-03-24 Sprint) — No Action Required

| # | Issue | Status |
|---|---|---|
| 1 | Missing `"license"` field in `composer.json` | ✅ Fixed |
| 2 | Missing root `LICENSE` file | ✅ Created |
| 3 | CSS `*=` selector regex injection (`SelectorParser::match()`) | ✅ Fixed with `preg_quote()` |
| 4 | Unbounded redirect loop in `file_get_html()` | ✅ Fixed with 5-hop cap |
| 5 | SSRF risk undocumented | ✅ Documented in `constraints.md` |
| 6 | PHPStan Level 6 type errors (`parse_charset()`, `@property-read`) | ✅ Fixed |
| 7 | `removeAttribute()` delegated to `__set(null)` instead of `__unset()` | ✅ Fixed |
| 8 | `iconv()` failure in `TextConverter::convert()` unchecked | ✅ Fixed |
| 9 | Several null guards added to `innertext()`, `outertext()`, `text()` | ✅ Partially done |
| 10 | Manifest updates (api-surface, constraints, data-flows, tech-stack) | ✅ Done |

### Remaining Defects — Action Required

#### DEFECT-1: `innertext()` iterates over null `$this->nodes` (PHP Warning)

**File:** `src/SimpleHtmlDom/Node.php`, line 304
**Severity:** Medium (triggers PHP warning in production; confirmed by test `testPostClearAccess`)

```php
$ret = '';
foreach ($this->nodes as $n) {  // ← $this->nodes is ?array; null after clear()
    $ret .= $n->outertext();
}
```

The `$this->nodes` property is typed `?array` and is set to `null` by `Node::clear()`. The `text()` method at line 387 already has the correct pattern (`if (!is_null($this->nodes))`), but `innertext()` lacks this guard.

**Fix:** Wrap the foreach in a null check, matching the existing pattern in `text()`:
```php
$ret = '';
if ($this->nodes !== null) {
    foreach ($this->nodes as $n) {
        $ret .= $n->outertext();
    }
}
```

#### DEFECT-2: `text()` dereferences null `$this->dom` for span nodes

**File:** `src/SimpleHtmlDom/Node.php`, line 395
**Severity:** Medium (potential fatal error on cleared span nodes)

```php
if ($this->tag == "span") {
    $ret .= $this->dom->default_span_text;  // ← $this->dom can be null
}
```

After `clear()`, `$this->dom` is null. If `text()` is called on a cleared span node with non-null `$this->nodes`, this line will cause a fatal "null dereference" error.

**Fix:** Guard with null check:
```php
if ($this->tag == "span" && $this->dom !== null) {
    $ret .= $this->dom->default_span_text;
}
```

#### DEFECT-3: `makeup()` dereferences null `$this->dom` in HDOM_INFO_TEXT branch

**File:** `src/SimpleHtmlDom/Node.php`, line 419
**Severity:** Medium (potential fatal error on cleared text/comment nodes)

```php
if (isset($this->_[HDOM_INFO_TEXT])) {
    return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);  // ← no null guard
}
```

The method has a null guard at line 447 for the attribute-reconstruction path, but the early-return HDOM_INFO_TEXT branch at line 419 lacks one. A cleared node with `HDOM_INFO_TEXT` set will fatal error here.

**Fix:** Add null guard matching the existing pattern at line 447:
```php
if (isset($this->_[HDOM_INFO_TEXT])) {
    if ($this->dom === null) {
        return $this->_[HDOM_INFO_TEXT];
    }
    return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
}
```

#### DEFECT-4: Stale code artifact in docblock before `makeup()`

**File:** `src/SimpleHtmlDom/Node.php`, lines 409-412
**Severity:** Low (cosmetic, but confusing for maintainers)

```php
/**if ($this->dom === null) {
            return $this->_[HDOM_INFO_TEXT];
        }
        
 * Build node's text with tag.
 */
```

This appears to be a failed edit where code was accidentally placed inside a docblock comment. It should be removed, leaving a clean docblock:

```php
/**
 * Build node's text with tag.
 */
```

### Non-Blocking Observations

| # | Observation | Priority | Recommendation |
|---|---|---|---|
| A | 23 `missingType.iterableValue` PHPStan warnings | Low | Address in a future sprint targeting Level 7 |
| B | 2 `foreach.emptyArray` + 1 `parameterByRef.type` + 1 `isset.offset` PHPStan items | Low | Address alongside Level 7 upgrade |
| C | `Node::parent()` setter doesn't remove node from old parent (code comment acknowledges this) | Low | Pre-existing design limitation; low-risk since `parent()` is rarely used as a setter. Document in `constraints.md` if desired |
| D | `file_get_html()` follows redirect `Location` URLs without scheme restriction (`file://`, `gopher://` etc.) | Medium | Addressed in constraints.md as consumer responsibility; add scheme guard in future sprint |
| E | `changelog.md` has no v2.2 entry for the 2026-03-24 security fixes | Medium | Add changelog entry |

## Approach / Architecture

All fixes are localized edits within `src/SimpleHtmlDom/Node.php`. No architectural changes are required. The existing `testPostClearAccess` test in `tests/Unit/NodeTest.php` already exercises the affected code paths and currently produces a PHP warning — after the fixes, this test should pass cleanly without warnings.

## Rationale

- **Null guard pattern:** The codebase already uses `if ($this->dom === null)` guards (lines 297, 328, 372, 447) and `if (!is_null($this->nodes))` guards (line 387). The proposed fixes follow these established patterns for consistency.
- **Return raw text on null `$dom`:** When the parser reference is gone, returning raw text without noise restoration is the best available fallback and matches the existing guard behavior throughout the file.
- **No exception throwing:** The library's error philosophy is to degrade gracefully rather than throw. Maintaining this pattern preserves backward compatibility.

## Detailed Steps

### Step 1: Fix DEFECT-1 — Guard `innertext()` foreach against null `$this->nodes`

**File:** `src/SimpleHtmlDom/Node.php`

In `innertext()` (around line 303-306), wrap the foreach in a null check:

```php
// Before:
$ret = '';
foreach ($this->nodes as $n) {
    $ret .= $n->outertext();
}
return $ret;

// After:
$ret = '';
if ($this->nodes !== null) {
    foreach ($this->nodes as $n) {
        $ret .= $n->outertext();
    }
}
return $ret;
```

### Step 2: Fix DEFECT-2 — Guard `text()` span text against null `$this->dom`

**File:** `src/SimpleHtmlDom/Node.php`

In `text()` (around line 394-396), add null check on `$this->dom`:

```php
// Before:
if ($this->tag == "span") {
    $ret .= $this->dom->default_span_text;
}

// After:
if ($this->tag == "span" && $this->dom !== null) {
    $ret .= $this->dom->default_span_text;
}
```

### Step 3: Fix DEFECT-3 — Guard `makeup()` HDOM_INFO_TEXT branch against null `$this->dom`

**File:** `src/SimpleHtmlDom/Node.php`

In `makeup()` (around line 418-420), add null guard:

```php
// Before:
if (isset($this->_[HDOM_INFO_TEXT])) {
    return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
}

// After:
if (isset($this->_[HDOM_INFO_TEXT])) {
    if ($this->dom === null) {
        return $this->_[HDOM_INFO_TEXT];
    }
    return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
}
```

### Step 4: Fix DEFECT-4 — Clean up stale docblock artifact

**File:** `src/SimpleHtmlDom/Node.php`

Replace the malformed docblock above `makeup()` (lines 409-414):

```php
// Before:
    /**if ($this->dom === null) {
                return $this->_[HDOM_INFO_TEXT];
            }
            
     * Build node's text with tag.
     */

// After:
    /**
     * Build node's text with tag.
     */
```

### Step 5: Add changelog entry for the production-readiness work

**File:** `changelog.md`

Add a `## v2.2` section at the top of the changelog documenting:
- Security fixes: regex injection in `*=` selector, redirect loop bound
- Bug fixes: post-clear null guards, `removeAttribute()` fix, `iconv()` failure handling
- Documentation: SSRF warning, redirect limit documentation
- Quality: PHPStan Level 6 compliance

### Step 6: Update manifest — Add post-clear behavior note to constraints.md

**File:** `docs/agents/project-manifest/constraints.md`

The `constraints.md` already has some post-clear notes (Memory Management section). Verify that it explicitly documents:
- After `clear()`, calling `innertext()`, `outertext()`, `text()`, or `makeup()` returns raw text without noise restoration
- Consumers should not rely on node output after calling `clear()`

### Step 7: Verification

1. Run `composer test` — Verify **278 tests, 0 failures, 0 warnings** (the existing `testPostClearAccess` should now pass without PHP warning)
2. Run `composer analyze` — Verify **0 errors** at Level 6

## Dependencies

- Steps 1-4 are independent and can be implemented in any order (all in `Node.php`)
- Step 5 is independent (changelog only)
- Step 6 is independent (manifest only)
- Step 7 depends on Steps 1-4 being complete

## Required Components

- `src/SimpleHtmlDom/Node.php` — Steps 1, 2, 3, 4
- `changelog.md` — Step 5
- `docs/agents/project-manifest/constraints.md` — Step 6

## Assumptions

- The `testPostClearAccess` test in `tests/Unit/NodeTest.php` adequately covers the post-clear code paths. No additional tests are needed beyond making this test pass warning-free.
- The 2026-03-24 sprint changes are correct and do not need re-verification (they were validated with their own acceptance criteria).
- PHP 8.4+ minimum version requirement is maintained.

## Constraints

- All changes must preserve backward compatibility with the legacy procedural API
- All 278 existing tests must continue to pass
- PHPStan Level 6 must remain at 0 errors
- No new dependencies may be introduced

## Out of Scope

- PHPStan Level 7 upgrade or resolving `missingType.iterableValue` warnings
- Redirect URL scheme restriction in `file_get_html()`
- `Node::parent()` setter re-parenting logic fix
- Code coverage tooling setup
- Performance optimization

## Acceptance Criteria

- `composer test` reports **278 tests, 0 failures, 0 warnings** (no PHP warnings)
- `composer analyze` reports **0 errors** at PHPStan Level 6
- `Node::innertext()` does not emit a PHP warning when called on a cleared node
- `Node::text()` does not fatal error when called on a cleared span node
- `Node::makeup()` does not fatal error when called on a cleared node with `HDOM_INFO_TEXT`
- The docblock above `makeup()` is clean (no stale code artifact)
- `changelog.md` contains a v2.2 section documenting the production-readiness changes
- `constraints.md` documents post-clear behavior

## Testing Strategy

No new tests are required. The existing `testPostClearAccess` test in `tests/Unit/NodeTest.php` already exercises all 3 affected code paths (`innertext()`, `outertext()`, `text()`, `makeup()` on a cleared node). After the fixes:

- The PHP warning at Node.php:304 will be eliminated (DEFECT-1)
- The test will pass without any warnings, confirming all null guards are effective
- Full regression suite (`composer test`) validates no existing behavior is broken

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **Null guard changes subtly alter output for cleared nodes** | The guards only activate when `$dom` is null (post-`clear()`). Before these fixes, the same scenarios caused a PHP warning or fatal error. Returning raw text or empty string is strictly better than crashing. |
| **`innertext()` foreach guard changes return value for cleared nodes** | Previously returned empty string (after the warning) because `foreach(null)` produces no iterations. The guarded version also returns empty string — behavior is identical, just warning-free. |
| **Changelog entry may conflict with future version planning** | The v2.2 designation can be adjusted by the release engineer. The content accurately reflects what was changed. |

## Production Readiness Verdict

After implementing the 4 defect fixes in this plan, the library will be **production ready**:

- **Architecture:** Sound PSR-4 class structure with complete backward compatibility via bridge file
- **Security:** Regex injection fixed, redirect loops bounded, SSRF documented as consumer responsibility
- **Quality:** PHPStan Level 6 clean, 278 tests with 1194 assertions, no warnings
- **Packaging:** Proper `composer.json` metadata, MIT license declared and filed
- **Documentation:** Manifest documents accurate and up-to-date
- **PHP 8.4 modern:** Property hooks, backed enums, readonly promotion, match expressions, typed parameters

---

## Implementation Summary

**Status: COMPLETED** — 2026-03-26

### Changes Made

#### `src/SimpleHtmlDom/Node.php`

- **DEFECT-1 (innertext null guard):** Wrapped the `foreach ($this->nodes as $n)` loop inside `if ($this->nodes !== null)`. This eliminates the PHP warning that was triggered when `innertext()` was called on a cleared node (confirmed previously failing in `testPostClearAccess`).
- **DEFECT-2 (text() span null guard):** Changed `if ($this->tag == "span")` to `if ($this->tag == "span" && $this->dom !== null)`. This guard is already inside the `!is_null($this->nodes)` block; the additional `$this->dom` check prevents a fatal null dereference on cleared span nodes.
- **DEFECT-3 (makeup() HDOM_INFO_TEXT null guard):** Added a `if ($this->dom === null) { return $this->_[HDOM_INFO_TEXT]; }` null guard before the `restore_noise()` call in the `HDOM_INFO_TEXT` early-return branch. Returns raw text as fallback, matching the established pattern used elsewhere in the file.
- **DEFECT-4 (stale docblock):** Removed the malformed docblock that contained stale code (`/**if ($this->dom === null) { … }`) above `makeup()`. Replaced with a clean `/** * Build node's text with tag. */` docblock.

#### `changelog.md`

Added a `## v2.2` section at the top documenting all production-readiness changes from both the 2026-03-24 sprint and this sprint: security fixes (S-001, S-002), bug fixes (B-005 through B-009), documentation updates (D-001 through D-003), and quality gate results.

#### `docs/agents/project-manifest/constraints.md`

No changes required. The Post-Clear Behavior section was already present and accurate, covering all three affected methods and the `$dom === null` fallback contract.

### Verification Results

| Gate | Result |
|------|--------|
| `composer test` | **278 tests, 1194 assertions, 0 failures, 0 warnings** ✅ |
| `composer analyze` | **0 errors** at PHPStan Level 6 ✅ |

### Comments

- All four Node.php fixes were localized, one-line or two-line changes with zero behavior change for non-cleared nodes.
- The `innertext()` fix produces identical output to the pre-fix behavior for cleared nodes (empty string), but now does so without a PHP warning.
- The `makeup()` fix and `text()` fix are strictly defensive — the cleared-node code paths were previously unreachable in practice but became reachable after `clear()` is called. The fallback of returning raw text (without noise restoration) is the same pattern the rest of the file uses.
- The `constraints.md` Post-Clear Behavior section was already accurate, documenting exactly the contract these fixes implement. No manifest update was needed.
- The v2.2 changelog groups the S-/B-/D- entries from both sprints, giving release engineers a complete picture of what changed between v2.1 and this release.
