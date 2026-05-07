# Synthesis Report — Example Hub Rework (2026-03-26)

**Plan:** `2026-03-26-example-hub-rework-1`
**Status:** COMPLETE — all 6 work packages delivered
**Generated:** 2026-03-26

---

## Executive Summary

This session closed every documentation gap and corrected every inaccuracy identified in the preceding Example Hub synthesis report. The work comprised six work packages:

- **WP-001** — Created `tests/Unit/NodeBehaviorTest.php` with 6 targeted verification tests, establishing code-grounded anchors for all new API documentation.
- **WP-002** — Added the `tbody` workaround pattern to `constraints.md`.
- **WP-003** — Annotated `outertext`, `appendChild()`, and `plaintext`/`text()` in `api-surface.md`.
- **WP-004** — Annotated `children()`/`childNodes()`, `next_sibling()`/`prev_sibling()`/`nextSibling()`/`previousSibling()`, and `find('*')` (both Node and Parser contexts) in `api-surface.md`.
- **WP-005** — Added `## 9. Error Handling` to `data-flows.md`.
- **WP-006** — Updated `file-tree.md`, ran the full test suite (284 tests, 0 failures), and ran PHPStan static analysis (0 errors).

All acceptance criteria were met. No rework cycles were needed on any work package.

---

## Metrics

| Metric | Value |
|---|---|
| Work packages | 6 / 6 COMPLETE |
| Rework cycles | 0 |
| Tests — full suite | 284 passed, 0 failed |
| Test assertions | 1223 |
| New tests added | 6 (in `NodeBehaviorTest.php`) |
| PHPStan errors | 0 |
| Files modified (new) | `tests/Unit/NodeBehaviorTest.php` |
| Files modified (existing) | `api-surface.md`, `constraints.md`, `data-flows.md`, `file-tree.md` |
| Pipeline health | 6 / 6 WPs — all stages PASS |

---

## What Was Built

### Verification Tests (`tests/Unit/NodeBehaviorTest.php`)

Six PHPUnit tests were written to empirically confirm node-level behaviours before documenting them — the "verify before you document" discipline explicitly called for in the plan:

| Test | Confirms |
|---|---|
| `test_children_array_contains_only_elements` | `children[]` holds `HDOM_TYPE_ELEMENT` nodes only |
| `test_nodes_array_contains_all_node_types` | `nodes[]` holds all node types (elements + text) |
| `test_next_sibling_returns_element_not_text` | `next_sibling()` skips text nodes, returns element |
| `test_prev_sibling_returns_element_not_text` | `prev_sibling()` skips text nodes, returns element |
| `test_outertext_empty_retains_node_in_nodes_array` | `outertext=''` suppresses render but keeps node in DOM |
| `test_plaintext_preserves_inter_node_whitespace` | `text()` output preserves whitespace between inline children |

The test file follows all project conventions: `declare(strict_types=1)`, correct namespace (`Tests\Unit`), `tearDown()` calls `Settings::reset()`, and each test is self-contained with its own `str_get_html()` call.

These tests confirmed that the previous synthesis claim — "next_sibling()/prev_sibling() return whitespace text nodes" — was **inaccurate for normally parsed trees**. The documentation was written to match verified behaviour.

### `constraints.md` — tbody Workaround Pattern (WP-002)

Added `### tbody Workaround Pattern` inside the CSS Selector Limitations section. Documents why `find('tbody tr')` fails (parser treats `<tbody>` as transparent) and provides the correct `find('tr')` approach with a `th`-guard code example to skip header rows.

### `api-surface.md` — Six Behavioural Annotations (WP-003, WP-004)

| Entry | Annotation added |
|---|---|
| `outertext` (Property Hook) | `> **Caveat**` — render suppression vs DOM removal distinction |
| `plaintext` / `text()` (Magic Read-Only) | `> **Note**` — inter-node whitespace is preserved, no normalisation |
| `appendChild()` (camelCase DOM Delegate) | `> **Warning**` — four known defects listed: no parent-removal, no `$dom` re-link, no position recalculation, no global index rebuild |
| `children()` / `childNodes()` (Tree Navigation + Delegates) | Element-only return contract (`HDOM_TYPE_ELEMENT` via `children[]`; use `$node->nodes` for all types) |
| `next_sibling()` / `prev_sibling()` / `nextSibling()` / `previousSibling()` | Element-sibling-only traversal; use `$node->parent->nodes` for all node types |
| `find()` (Node → Search and Parser → Searching) | `find('*')` limitation cross-reference to `constraints.md#css-selector-limitations` |

### `data-flows.md` — Section 9: Error Handling (WP-005)

Added the canonical error-check flow as the final numbered section, documenting the complete path: `str_get_html()` / `file_get_html()` failure → `Settings::set('__error', new Error(...))` → returns `false` → consumer calls `simple_html_dom_get_error()` → inspects error code (1001 empty / 1002 oversized / 1003 bad HTTP) and message. Follows the existing ASCII-flow diagram style.

### `file-tree.md` — NodeBehaviorTest.php Entry (WP-006)

`tests/Unit/NodeBehaviorTest.php` added in correct alphabetical position between `ErrorTest.php` and `NodeTest.php`, keeping the manifest in sync with the filesystem.

---

## Strategic Recommendations (Gold Nuggets)

### 1. Correction: `next_sibling()` / `prev_sibling()` Behaviour
**Origin:** WP-001 verification tests  
The previous synthesis incorrectly stated these methods return whitespace text nodes. They traverse `parent->children[]`, which contains only element nodes in a normally parsed tree. The new tests prove this. Any future documentation or example code that relies on the old (incorrect) claim should be reviewed.

### 2. `appendChild()` Is Functionally Unsupported — Document and Defer, Don't Fix
**Origin:** Plan architectural context + WP-003  
The source code itself contains the comment: *"I am SURE that this doesn't work properly."* The four known defects (no parent-removal, no `$dom` re-link, no position recalculation, no global index rebuild) make this unsafe for real use. A fix requires subtree index rebuilding across the entire DOM — a significant, breaking-risk change. The correct strategy (taken here) is to annotate it as unsupported and defer a fix to a dedicated plan.

### 3. Establish a `documentation-forward` Convention for Test Files
**Origin:** WP-001 code review  
`NodeBehaviorTest.php` introduces a class-level docblock listing all scenarios covered — a useful pattern that no other Unit test file currently follows. This convention should be documented in a `CONTRIBUTING.md` or `README.md` so future contributors know to follow it for behaviour-documentation test files.

### 4. Document `HDOM_TYPE_*` Constants in the API Reference
**Origin:** WP-001 code review  
The test file and new `api-surface.md` annotations reference `HDOM_TYPE_ELEMENT` and `HDOM_TYPE_TEXT` as bare constants. A brief constants reference section in `api-surface.md` or the project README would help contributors understand these values without having to grep the source.

### 5. PHPStan Parallel-Worker Mode Fails in Sandbox (Known Environment Constraint)
**Origin:** WP-006 implementation + QA  
`composer analyze` / `php vendor/bin/phpstan` in parallel mode exits with code 127 (`sh: command not found`) in the sandbox environment. Single-process mode (`--no-progress`) returns 0 errors cleanly. This is a sandbox constraint, not a code issue — but the CI/CD configuration should confirm that the standard `composer analyze` command works in the production environment.

---

## Next Steps

1. **Add `CONTRIBUTING.md`** (or extend `README.md`) to document the class-level docblock convention introduced by `NodeBehaviorTest.php`.
2. **Add a constants reference** to `api-surface.md` for `HDOM_TYPE_*` values.
3. **Scope an `appendChild()` fix plan** — this is the highest-risk unfixed defect in the Node API. The four-defect description added to `api-surface.md` provides the exact acceptance criteria for a fix.
4. **Consider a `find('*')` investigation plan** — the behaviour is now documented in `constraints.md`, but whether the direct-children-only restriction is a bug or intentional design remains an open question. Investigating `SelectorParser::seek()` at L138–142 would produce a definitive answer.
5. **Verify CI environment** — confirm `composer analyze` works correctly outside the sandbox (PHPStan parallel mode).
