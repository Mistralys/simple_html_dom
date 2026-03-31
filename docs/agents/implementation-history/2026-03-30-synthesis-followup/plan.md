# Plan — Synthesis Follow-Up Items

## Summary

Address the actionable strategic recommendations from the `2026-03-26-example-hub-rework-1` synthesis report. This covers four items: establishing a test-file docblock convention (Gold Nugget 3), documenting `HDOM_TYPE_*` / `HDOM_INFO_*` / `HDOM_QUOTE_*` constants in the API reference (Gold Nugget 4), investigating the `find('*')` direct-children-only behaviour to determine if it is a bug or intentional (Next Step 4), and verifying CI compatibility of PHPStan parallel mode (Gold Nugget 5).

**Out of scope:** Gold Nugget 1 (confirmed as expected behaviour — no action), Gold Nugget 2 (`appendChild()` fix — tracked in a dedicated plan: `2026-03-30-appendchild-fix`).

## Architectural Context

- **Test files:** All test files live under `tests/` with PSR-4 namespace `Tests\`. Only `tests/Unit/NodeBehaviorTest.php` currently carries a class-level docblock listing covered scenarios. All other Unit test files (`DumpTest.php`, `ErrorTest.php`, `NodeTest.php`, `ParserTest.php`, `SelectorParserTest.php`, `SettingsTest.php`, `TextConverterTest.php`) open directly with `class ... extends TestCase` without a summary docblock.
- **Constants:** All `HDOM_*` constants are defined in the bridge file `src/simple_html_dom.php` via `define()` calls that delegate to backed enum `->value`. The three enums are `NodeType` (6 cases), `QuoteStyle` (3 cases), and `NodeInfo` (8 cases). The enums are already documented in `docs/agents/project-manifest/api-surface.md` under "Enums", but the legacy `HDOM_*` constant names that most consumers actually use are only listed as a one-line note at the very end under "Legacy Constants" — no reference table mapping constant names to values/enums exists.
- **`find('*')` behaviour:** In `src/SimpleHtmlDom/SelectorParser.php` lines 133–139, the `seek()` method has an explicit guard: when `$tag === '*' && !$key`, it checks `in_array($node, $this->node->children, true)` and `continue`s — meaning only direct children of the context node are returned. This applies to both `Node::find('*')` and `Parser::find('*')` (Parser delegates to root node). The limitation is already documented in `constraints.md` and `api-surface.md`, but whether it is a bug (should traverse all descendants) or intentional (design decision from the original author) has never been determined.
- **PHPStan configuration:** `phpstan.neon` configures level 6 analysis. The `composer analyze` script runs `vendor/bin/phpstan analyze`. In sandboxed environments, parallel worker mode fails with exit code 127. Single-process mode (`--no-progress`) works correctly.
- **No `CONTRIBUTING.md`** exists. The `README.md` has a Development section with test instructions but no contributing guidelines or test-file conventions.

## Approach / Architecture

Four independent, parallelisable work packages:

1. **Constants reference table** — Add a "Legacy Constants Reference" section to `api-surface.md` mapping every `HDOM_*` constant to its enum equivalent and integer value.
2. **Test docblock convention** — Create a `CONTRIBUTING.md` file documenting the class-level docblock convention for behaviour-documentation test files, referencing `NodeBehaviorTest.php` as the exemplar.
3. **`find('*')` investigation** — Analyse the `SelectorParser::seek()` implementation, trace the original Git history of the `in_array` guard, write a conclusive determination (bug vs design), and document the finding. If it is a bug, propose a fix approach; if intentional, strengthen the existing documentation.
4. **PHPStan parallel mode** — Verify whether `composer analyze` works in the project's actual CI environment (not sandbox). Document the finding and, if needed, add a `--no-progress` fallback to the composer script.

## Rationale

- Constants reference fills a real gap: consumers using the legacy API see `HDOM_TYPE_ELEMENT` everywhere but the manifest only documents the namespaced enums.
- A `CONTRIBUTING.md` is the conventional location for contributor guidelines in PHP projects; preferable to embedding this in `README.md` which is already focused on installation and usage.
- The `find('*')` behaviour is the last open question from the synthesis. Resolving it prevents future confusion when users expect CSS-standard universal selector behaviour.
- PHPStan parallel mode verification is a low-effort confirmation that avoids false negatives in CI.

## Detailed Steps

### Step 1 — Legacy Constants Reference Table (`api-surface.md`)

1. At the bottom of `docs/agents/project-manifest/api-surface.md`, replace the existing one-line "Legacy Constants" section with a full reference table.
2. The table must include three sub-sections corresponding to the three enums:
   - **Node Type Constants** (`HDOM_TYPE_*`) — 6 entries mapping to `NodeType` enum cases
   - **Quote Style Constants** (`HDOM_QUOTE_*`) — 3 entries mapping to `QuoteStyle` enum cases
   - **Node Info Constants** (`HDOM_INFO_*`) — 8 entries mapping to `NodeInfo` enum cases
   - **Miscellaneous Constants** (`DEFAULT_TARGET_CHARSET`, `DEFAULT_BR_TEXT`, `DEFAULT_SPAN_TEXT`, `MAX_FILE_SIZE`)
3. Each row: Constant Name | Value | Enum Equivalent.
4. Source of truth: the `define()` calls in `src/simple_html_dom.php` (lines 32–55).

### Step 2 — CONTRIBUTING.md

1. Create `CONTRIBUTING.md` at the project root.
2. Contents:
   - **Running Tests** — brief pointer to `composer test` and test suite names (keep DRY by referencing `README.md` for details).
   - **Test File Conventions** — document the class-level docblock convention: when a test file verifies specific behavioural scenarios, it must include a class-level `/** ... */` docblock listing all scenarios covered, following the pattern established in `tests/Unit/NodeBehaviorTest.php`.
   - **Code Style** — reference the dual snake_case/camelCase convention already in `constraints.md`.
   - **Static Analysis** — note that `composer analyze` must pass at PHPStan level 6 before submitting changes.
3. Keep it concise — under 80 lines.

### Step 3 — `find('*')` Investigation

1. Read `src/SimpleHtmlDom/SelectorParser.php` lines 100–145 carefully. The key code is:
   ```php
   if ($tag === '*' && !$key) {
       if (in_array($node, $this->node->children, true)) {
           $ret[$i] = 1;
       }
       continue;
   }
   ```
   The `for` loop iterates from `$this->node->_[HDOM_INFO_BEGIN] + 1` to `$end` (all descendants), but the `in_array` check against `$this->node->children` restricts results to direct children only.
2. Check Git history of `SelectorParser.php` (and the original `Node.php` before extraction) for commit messages explaining why this guard was added.
3. Write a test that demonstrates the current behaviour: `find('*')` on a `<div><p><span>X</span></p></div>` should return only `<p>`, not `<p>` + `<span>`.
4. Compare with the tagged-selector path: `find('span')` on the same HTML does return nested descendants, confirming that `*` is the anomaly.
5. Document the finding:
   - If **intentional**: add a brief note to `constraints.md` under CSS Selector Limitations explaining the design rationale.
   - If **bug**: write a fix that removes the `in_array` guard and instead adds `$ret[$i] = 1` unconditionally (for nodes matching element type), then verify the full test suite still passes. Add a test for the expected behaviour.
6. Update `api-surface.md` annotations for `find('*')` based on the conclusion.

### Step 4 — PHPStan Parallel Mode Verification

1. Run `composer analyze` in the actual project environment (not a sandbox).
2. If it succeeds: document as "sandbox-only issue, no action needed" and close.
3. If it fails with exit code 127:
   - Add an alternative composer script `analyze-safe` that passes `--no-progress` to PHPStan.
   - Document the workaround in `CONTRIBUTING.md`.
4. Update `AGENTS.md` Project Stats table if any composer script is added.

### Step 5 — Manifest Updates

1. Update `docs/agents/project-manifest/file-tree.md` to add `CONTRIBUTING.md` entry.
2. Update `docs/agents/project-manifest/constraints.md` if the `find('*')` investigation produces new findings.
3. No changes needed to `tech-stack.md` or `data-flows.md`.

## Dependencies

- Step 1 (constants table) — independent, can proceed immediately.
- Step 2 (CONTRIBUTING.md) — independent, can proceed immediately.
- Step 3 (`find('*')` investigation) — independent, but may produce changes to Steps 1/2 outputs.
- Step 4 (PHPStan verification) — independent, but may add content to Step 2 output.
- Step 5 (manifest updates) — depends on Steps 1–4 completion.

## Required Components

- `docs/agents/project-manifest/api-surface.md` — existing, to be modified (Step 1)
- `CONTRIBUTING.md` — **new file**, project root (Step 2)
- `src/SimpleHtmlDom/SelectorParser.php` — existing, read for investigation; possibly modified (Step 3)
- `tests/Unit/SelectorParserTest.php` or new test file — for `find('*')` verification test (Step 3)
- `docs/agents/project-manifest/constraints.md` — existing, possibly modified (Step 3)
- `docs/agents/project-manifest/file-tree.md` — existing, to be modified (Step 5)
- `AGENTS.md` — existing, possibly modified if new composer script added (Step 4)

## Assumptions

- The `find('*')` direct-children-only behaviour is deterministic and reproducible via a simple test case.
- Git history is available and contains meaningful commit messages for the SelectorParser extraction.
- The project's CI environment is the local development environment (macOS with PHP 8.4+).

## Constraints

- All changes must pass the full test suite (`composer test` — 284+ tests, 0 failures).
- All changes must pass PHPStan level 6 (`composer analyze`).
- Backward compatibility must be preserved — if `find('*')` behaviour changes, existing consumers relying on the current (direct-children-only) behaviour may break. This must be assessed before any fix.
- `CONTRIBUTING.md` must stay concise (under 80 lines).

## Out of Scope

- Gold Nugget 1 (`next_sibling()`/`prev_sibling()` element-only traversal) — confirmed as expected behaviour by the user. No action.
- Gold Nugget 2 (`appendChild()` fix) — tracked in a dedicated plan: `2026-03-30-appendchild-fix/plan.md`.
- Retrofitting class-level docblocks to existing test files — this plan establishes the convention; applying it retrospectively is a separate effort.
- Changes to the example files or `examples/` directory.

## Acceptance Criteria

1. `api-surface.md` contains a complete legacy constants reference table with all `HDOM_*` constants, their integer values, and enum equivalents.
2. `CONTRIBUTING.md` exists at the project root, documents the test docblock convention, and is under 80 lines.
3. The `find('*')` behaviour is conclusively determined as either a bug or intentional design, with supporting evidence (test + Git history or code analysis). The finding is documented in the appropriate manifest file(s).
4. PHPStan parallel mode status is verified and documented.
5. `file-tree.md` reflects any new files.
6. Full test suite passes. PHPStan level 6 passes.

## Testing Strategy

- **Constants table:** Manual review — verify every `define()` in `src/simple_html_dom.php` has a corresponding row in the table.
- **CONTRIBUTING.md:** Manual review for completeness and accuracy.
- **`find('*')` investigation:** Write at least one PHPUnit test demonstrating the behaviour. If a fix is applied, run the full test suite to check for regressions.
- **PHPStan:** Run `composer analyze` and confirm 0 errors.
- **Full suite:** `composer test` must report 0 failures.

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| **`find('*')` fix breaks existing consumers** | Assess backward-compatibility impact before applying any code change. If high risk, document the behaviour as intentional and defer the fix to a separate plan with a deprecation strategy. |
| **Git history lacks context for `find('*')` guard** | Fall back to code-analysis-only determination. The implementation semantics (loop range vs filter) provide sufficient evidence. |
| **PHPStan parallel mode is environment-specific** | If it fails locally too, add the `analyze-safe` script as a workaround rather than changing the primary `analyze` command. |
