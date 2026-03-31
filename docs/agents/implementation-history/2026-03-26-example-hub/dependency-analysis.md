# Dependency & Sequencing Analysis

> **Plan:** `docs/agents/plans/2026-03-26-example-hub/plan.md`
> **Work Packages:** `docs/agents/plans/2026-03-26-example-hub/work-packages-draft.md`
> **Generated:** 2026-03-26

---

## Dependency Graph

| WP | Title | Dependencies | Shared Artifacts |
|----|-------|-------------|------------------|
| WP-001 | Infrastructure: Directory Structure and Bootstrap | none | Produces: `examples/` tree (7 dirs), `_bootstrap.php` |
| WP-002 | Getting Started Examples (Migration) | WP-001 | Consumes: `_bootstrap.php`, `examples/01-getting-started/` dir |
| WP-003 | Selector Examples (New) | WP-001 | Consumes: `_bootstrap.php`, `examples/02-selectors/` dir |
| WP-004 | DOM Navigation Examples (New) | WP-001 | Consumes: `_bootstrap.php`, `examples/03-dom-navigation/` dir |
| WP-005 | Modifying HTML Examples (Mixed) | WP-001 | Consumes: `_bootstrap.php`, `examples/04-modifying-html/` dir |
| WP-006 | Practical Patterns Examples (Mixed) | WP-001 | Consumes: `_bootstrap.php`, `examples/05-practical-patterns/` dir |
| WP-007 | Configuration Examples (New) | WP-001 | Consumes: `_bootstrap.php`, `examples/06-configuration/` dir |
| WP-008 | Documentation: Examples Hub README and Legacy Redirect | WP-002, WP-003, WP-004, WP-005, WP-006, WP-007 | Consumes: final file list from all example WPs; produces `examples/README.md`, `example/README.md` |
| WP-009 | Project Manifest: File Tree Update | WP-008 | Consumes: final directory structure from all WPs; modifies `docs/agents/project-manifest/file-tree.md` |

### Dependency Justifications

| Edge | Justification |
|------|---------------|
| WP-002..WP-007 → WP-001 | All example files require `_bootstrap.php` and the subdirectory created by WP-001 |
| WP-008 → WP-002..WP-007 | `examples/README.md` must list all 18 example files with accurate descriptions; while the file names are known from the plan, WP-008's acceptance criterion #1 requires listing all 18 files — review after example WPs complete ensures accuracy |
| WP-009 → WP-008 | `file-tree.md` must reflect the final structure including `examples/README.md` and `example/README.md` created by WP-008 |

### Implicit Transitive Dependencies

WP-008 implicitly depends on WP-001 (needs `examples/` directory for `README.md`), but this is already satisfied transitively through WP-002..WP-007.

WP-009 implicitly depends on WP-001..WP-007, but these are already satisfied transitively through WP-008.

---

## Execution Phases

### Phase 1 — Foundation
- **WP-001**: Infrastructure: Directory Structure and Bootstrap

> Single WP. No parallelism possible. Must complete before any other work begins.

### Phase 2 — Example Files (Full Parallelism)
- **WP-002**: Getting Started Examples — Migration *(depends on WP-001)*
- **WP-003**: Selector Examples — New *(depends on WP-001)*
- **WP-004**: DOM Navigation Examples — New *(depends on WP-001)*
- **WP-005**: Modifying HTML Examples — Mixed *(depends on WP-001)*
- **WP-006**: Practical Patterns Examples — Mixed *(depends on WP-001)*
- **WP-007**: Configuration Examples — New *(depends on WP-001)*

> All 6 WPs are fully independent — each writes to its own subdirectory under `examples/`. Maximum parallelism: 6 concurrent streams.

### Phase 3 — Documentation Index
- **WP-008**: Documentation: Examples Hub README and Legacy Redirect *(depends on WP-002..WP-007)*

> Single WP. Blocked until all Phase 2 WPs complete. Writes to `examples/README.md` and `example/README.md` — neither file is touched by any Phase 2 WP.

### Phase 4 — Manifest Sync
- **WP-009**: Project Manifest: File Tree Update *(depends on WP-008)*

> Single WP. Blocked until Phase 3 completes. Modifies `docs/agents/project-manifest/file-tree.md` — no other WP touches this file.

---

## Parallelization Notes

### Phase 2 — No Conflicts

All six Phase 2 WPs can run concurrently without coordination:

| WP Pair | Conflict? | Reason |
|---------|-----------|--------|
| WP-002 ↔ WP-003 | No | Different directories (`01-getting-started/` vs `02-selectors/`) |
| WP-002 ↔ WP-004 | No | Different directories (`01-getting-started/` vs `03-dom-navigation/`) |
| WP-002 ↔ WP-005 | No | Different directories (`01-getting-started/` vs `04-modifying-html/`) |
| WP-002 ↔ WP-006 | No | Different directories (`01-getting-started/` vs `05-practical-patterns/`) |
| WP-002 ↔ WP-007 | No | Different directories (`01-getting-started/` vs `06-configuration/`) |
| WP-003 ↔ WP-004 | No | Different directories (`02-selectors/` vs `03-dom-navigation/`) |
| WP-003 ↔ WP-005 | No | Different directories (`02-selectors/` vs `04-modifying-html/`) |
| WP-003 ↔ WP-006 | No | Different directories (`02-selectors/` vs `05-practical-patterns/`) |
| WP-003 ↔ WP-007 | No | Different directories (`02-selectors/` vs `06-configuration/`) |
| WP-004 ↔ WP-005 | No | Different directories (`03-dom-navigation/` vs `04-modifying-html/`) |
| WP-004 ↔ WP-006 | No | Different directories (`03-dom-navigation/` vs `05-practical-patterns/`) |
| WP-004 ↔ WP-007 | No | Different directories (`03-dom-navigation/` vs `06-configuration/`) |
| WP-005 ↔ WP-006 | No | Different directories (`04-modifying-html/` vs `05-practical-patterns/`) |
| WP-005 ↔ WP-007 | No | Different directories (`04-modifying-html/` vs `06-configuration/`) |
| WP-006 ↔ WP-007 | No | Different directories (`05-practical-patterns/` vs `06-configuration/`) |

**Shared read-only artifact:** All Phase 2 WPs read `_bootstrap.php` but none modify it. No write conflict.

### Phase 2 — Early-Start Opportunity for WP-008

The `example/README.md` (legacy redirect) portion of WP-008 has **no dependency** on any example WP — it could be created as early as Phase 1. However, since WP-008 is a single atomic work package, it is sequenced to Phase 3 as a unit. Splitting WP-008 would add coordination overhead for minimal benefit given the file is trivial.

Additionally, `examples/README.md` content is fully deterministic from the plan (all 18 file names and descriptions are specified). An implementor could draft it during Phase 2, but final validation should occur after Phase 2 completes to catch any file name changes.

---

## Critical Path

```
WP-001 → WP-006 → WP-008 → WP-009
  (1)      (2)       (3)      (4)     = 4 sequential stages
```

**WP-006** (Practical Patterns) is on the critical path as the most complex Phase 2 WP:
- **4 files** (highest file count of any WP)
- **High complexity** rating (only WP rated High)
- Includes both migration and new-creation work
- Contains the most diverse pattern set (callbacks, table extraction, sanitization, form extraction)

If WP-006 finishes last among Phase 2 WPs, it gates WP-008. All other Phase 2 WPs are Medium or lower complexity with fewer files.

### Alternate Critical Path (if WP-006 finishes early)

If WP-006 completes quickly, the critical path shifts to whichever Phase 2 WP finishes last. The next most likely bottleneck is **WP-005** (3 files, Medium complexity, mixed migration+new) or **WP-002** (3 files, Medium complexity, migration requiring reference to 3 legacy files).

---

## Summary Statistics

| Metric | Value |
|--------|-------|
| Total WPs | 9 |
| Total execution phases | 4 |
| Max parallelism (Phase 2) | 6 concurrent WPs |
| Critical path length | 4 sequential stages |
| Total files produced | 20 (18 PHP examples + 2 Markdown) + 1 modified (`file-tree.md`) |
| Circular dependencies | None |
