# Synthesis Report — Naming Convention Sweep
**Plan:** `2026-03-20-naming-convention-sweep`
**Date:** 2026-03-20
**Status:** COMPLETE

---

## Executive Summary

This was a pure refactoring sprint with zero behavioural changes. Three `camelCase` private methods
introduced during the earlier `maintenance-modernization` sprint were misaligned with the prevailing
`snake_case` convention used throughout the `simple_html_dom` library. The audit pass triggered by
WP-001 also surfaced two additional `camelCase` violations in sibling classes (`SelectorParser` and
`TextConverter`), which were resolved in WP-002. WP-003 delivered a full regression gate confirming
that every code path touched by the renaming sweep is clean.

**All 5 renamed methods are now `snake_case`. The full test suite passes with 0 regressions. No
public API surface was altered.**

---

## Outcomes Achieved

| # | Outcome | Status |
|---|---------|--------|
| 1 | `Parser::parseDoubleQuotedAttr` → `parse_double_quoted_attr` | ✅ Complete |
| 2 | `Parser::parseSingleQuotedAttr` → `parse_single_quoted_attr` | ✅ Complete |
| 3 | `Parser::parseUnquotedAttr` → `parse_unquoted_attr` | ✅ Complete |
| 4 | `SelectorParser::parseSelector` → `parse_selector` | ✅ Complete |
| 5 | `TextConverter::isUtf8` → `is_utf8` | ✅ Complete |
| 6 | All `// next` comments removed from the three `Parser` attr-parse methods | ✅ Complete |
| 7 | All call-sites updated across `Parser.php`, `Node.php`, `SelectorParser.php`, `TextConverter.php` | ✅ Complete |
| 8 | All matching test call-sites updated in `SelectorParserTest.php`, `TextConverterTest.php` | ✅ Complete |
| 9 | DOM delegate block (camelCase W3C API methods) confirmed untouched | ✅ Complete |

---

## Files Modified

| File | Change |
|------|--------|
| `src/SimpleHtmlDom/Parser.php` | 3 private method renames + `// next` comment removal (WP-001) |
| `src/SimpleHtmlDom/SelectorParser.php` | `parseSelector` → `parse_selector` (WP-002) |
| `src/SimpleHtmlDom/Node.php` | Updated call-sites for `parse_selector` and `is_utf8` (WP-002) |
| `src/SimpleHtmlDom/TextConverter.php` | `isUtf8` → `is_utf8` + internal call-site update (WP-002) |
| `tests/Unit/SelectorParserTest.php` | 7 call-sites + section comment updated (WP-002 rework) |
| `tests/Unit/TextConverterTest.php` | 4 call-sites + section comment updated (WP-002 rework) |

---

## Metrics Summary

| Metric | Value |
|--------|-------|
| Work packages | 3 |
| Work packages COMPLETE | 3 |
| Work packages BLOCKED / FAILED | 0 |
| Source files modified | 4 |
| Test files modified | 2 |
| Methods renamed | 5 |
| `// next` comments removed | 3 (2 in `parse_double_quoted_attr`, 1 in `parse_single_quoted_attr`) |
| Full test suite (PHPUnit) | **274 tests, 1174 assertions — 0 failures, 0 errors, 0 risky** |
| PHP syntax check (`php -l`) | PASS on all `src/` files |
| Static analysis (phpstan/psalm) | N/A — not configured in this project |
| Pipeline rework cycles | 1 (WP-002 implementation + code-review each cycled once) |

---

## Work Package Summaries

### WP-001 — Parser.php Private Method Renames (Reviewer)
**Result: PASS — 0 rework cycles**

Renamed the three private attribute-parsing methods in `Parser.php` and updated the dispatch block
in `parse_attr()`. The `// next` inline comments (cursor-advance documentation artefacts from the
original inline code) were also removed from the three methods. A subsequent full audit confirmed
that no other `camelCase` private or protected methods exist in `Parser.php` outside the intentional
W3C DOM delegate block.

Both the implementation and the code-review pipeline passed first time. The Reviewer confirmed via
direct `grep` inspection that all old identifiers are gone and the DOM delegate block is untouched.

### WP-002 — SelectorParser & TextConverter Renames (Reviewer)
**Result: PASS — 1 rework cycle (implementation + code-review each cycled once)**

The audit pass from WP-001 surfaced two additional `camelCase` violations:
- `SelectorParser::parseSelector` (called from `Node::find()` and the `Node::parse_selector()` delegate)
- `TextConverter::isUtf8` (called internally and from `Node::is_utf8()` delegate)

The initial implementation correctly renamed both methods and updated all `src/` call-sites — but
missed the corresponding test call-sites in `tests/Unit/SelectorParserTest.php` (7 occurrences) and
`tests/Unit/TextConverterTest.php` (4 occurrences). The code-review pipeline caught this as a
blocking failure (11 fatal `Call to undefined method` errors). The Developer resolved both files in
a second implementation pass; the subsequent code-review confirmed all 274 tests pass.

### WP-003 — Full Regression Gate (QA)
**Result: PASS — 0 rework cycles**

End-to-end validation after both rename WPs were complete:
- `php -l` clean on every `src/SimpleHtmlDom/*.php` file
- Full PHPUnit run: **274 tests, 1174 assertions, 0 failures, 0 errors, 0 risky**
- Public API surface confirmed unchanged (grep sweep — zero occurrences of any old camelCase name in `src/` or `tests/`)

---

## Key Technical Decisions

### 1. Scope boundary: private/protected only, W3C DOM delegates excluded
The plan correctly excluded all public DOM API delegates (`getAttribute`, `getElementsByTagName`, etc.)
from renaming. These methods intentionally mirror the W3C DOM interface in camelCase and any rename
would constitute a public API break. This boundary was verified explicitly during both code-review
and QA pipelines.

### 2. Test files were in scope despite not being listed in the plan
The original plan scope noted that test updates were "unlikely given they are private." This held for
the `Parser.php` renames (private methods not directly testable), but `SelectorParser::parseSelector`
and `TextConverter::isUtf8` were not private — they were package-internal methods called from tests.
The rework cycle in WP-002 stemmed directly from this incorrect assumption. The correct policy is:
**always grep `tests/` in the same pass as any source rename.**

### 3. Extra call-site caught by Developer (not listed in WP scope)
During WP-002 implementation, the Developer noticed that `Node::is_utf8()` internally called
`TextConverter::isUtf8()` — a call-site not explicitly listed in the WP spec. It was fixed in the
same pass rather than deferring to a follow-up. This was the correct judgment; leaving a stale
call-site would have caused a runtime error.

---

## Lessons Learned & Recurring Patterns

### Pattern 1 — Always grep `tests/` alongside `src/` on any rename
**Observed in:** WP-002 rework cycle
The single rework cycle in this project was entirely caused by the implementation pass not checking
test files for method call-sites before declaring the rename complete. For private methods this is a
non-issue, but for package-internal methods called directly from unit tests it creates silent
breakage caught only at code-review. Future rename WPs should mandate a `grep -r 'oldName' tests/`
as part of the implementation checklist.

### Pattern 2 — Inherited inline comments leave residual noise
**Observed in:** WP-001 (implementation + code-review)
The `// next` comments in the three attr-parse methods were copied verbatim from the original inline
parsing loop during the `maintenance-modernization` sprint. They described a cursor-advance idiom
that is self-evident from the code and added no value in the extracted method context. A general
principle: when extracting methods, review any inline comments for continued relevance and remove
those that merely restate the code.

### Pattern 3 — Cursor-advance pattern is repeated rather than delegated
**Observed in:** WP-001 code-review
`parse_double_quoted_attr` and `parse_single_quoted_attr` both contain direct inline
`$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null` assignments rather
than calling the existing `$this->skip()` or `$this->copy_skip()` helper methods. This is a
pre-existing pattern inherited from the original inline loop — not introduced by this sprint — but
it represents a future consolidation opportunity.

### Pattern 4 — camelCase parameter names are a residual concern
**Observed in:** WP-002 code-review, WP-003 QA
`SelectorParser::parse_selector()` retains the parameter name `$selectorString` (camelCase).
PHP positional call-sites are unaffected, so this causes no runtime issue. However it is mildly
inconsistent with the project's snake_case convention. Noted as a non-blocking item for a future
parameter-name sweep.

---

## Outstanding Technical Debt & Follow-up Items

| ID | Priority | Item | Source |
|----|----------|------|--------|
| FU-1 | Low | Sweep remaining `// next` comments in `Parser.php` — `read_tag()`, `as_text_node()`, `skip()`, `copy_skip()`, `copy_until()` still contain the same cursor-advance pattern comments removed from the three attr-parse methods. | WP-001 implementation + code-review |
| FU-2 | Low | Consolidate cursor-advance inline assignments in `parse_double_quoted_attr` and `parse_single_quoted_attr` into calls to `$this->skip()` / `$this->copy_skip()` helper methods. | WP-001 code-review |
| FU-3 | Low | Rename parameter `$selectorString` → `$selector_string` in `SelectorParser::parse_selector()` as part of a future parameter-name sweep. | WP-002 code-review + WP-003 QA |
| FU-4 | Low | Add `phpstan` (or `psalm`) to `composer.json` dev dependencies to provide ongoing type-safety guarantees for future refactoring passes. Currently no static analyser is configured; AC2 of WP-003 passed vacuously. | WP-003 QA |

---

## Strategic Recommendations

1. **Establish a naming convention linter in CI.** The root cause of this entire sprint was camelCase
   method names slipping through during the `maintenance-modernization` sprint. A `phpcs` rule
   enforcing snake_case for non-W3C-delegate methods would catch this class of inconsistency
   automatically at PR time, eliminating the need for periodic manual sweeps.

2. **Codify the W3C DOM delegate boundary.** The line between intentionally-camelCase DOM API
   methods and accidentally-camelCase internal methods is currently maintained by convention and
   human review. A `@api` or `@dom-delegate` doc-comment tag on the relevant methods, combined with
   a phpcs custom sniff that treats tagged methods as exempt from the snake_case rule, would make
   this boundary machine-checkable.

3. **Add a pre-rename checklist to WP templates for rename operations.** Include mandatory steps:
   (a) `grep -r 'oldName' src/` — confirm all definition and call-sites found,
   (b) `grep -r 'oldName' tests/` — confirm any test call-sites found and updated,
   (c) `php -l` sweep, (d) full test run. WP-002's rework cycle is the direct evidence that step (b)
   was absent from the workflow.

---

## Project Health at Close

- **Pipeline health:** 3/3 WPs with all pipeline stages PASS
- **Test suite:** Green (274/274, 1174 assertions)
- **Public API:** Unchanged
- **Rework cycles:** 1 (WP-002 — test call-sites missed in first implementation pass)
- **Blocking issues at close:** None
- **Open observations:** 4 low-priority follow-up items (FU-1 through FU-4), all non-blocking
