# Synthesis Report — Example Hub

**Project:** `2026-03-26-example-hub`
**Date:** 2026-03-26
**Status:** COMPLETE
**Prepared by:** Head of Operations (Synthesis)

---

## Executive Summary

This session delivered a complete `examples/` hub for the `simple_html_dom` library — 17 runnable PHP CLI scripts organised across six topic categories, a shared bootstrap file, and companion README documentation. The work supersedes the legacy `example/` directory (which is preserved for backward compatibility but now clearly marked as legacy). The manifest's `file-tree.md` was updated to reflect the new structure, and one high-priority API constraint (`find('*')` top-level-only behaviour) was documented in `constraints.md` as a direct outcome of discovering the gap during example authoring.

All 9 work packages are **COMPLETE**. All 44 acceptance criteria are **met**.

---

## Deliverables

### New Files Created

| File | Description |
|---|---|
| `examples/_bootstrap.php` | Shared autoloader + `section()` CLI helper for all examples |
| `examples/README.md` | Index of all 17 examples across 6 categories, run instructions, prerequisites |
| `examples/01-getting-started/basic_selectors.php` | Tag, `#id`, `.class`, attribute selectors |
| `examples/01-getting-started/advanced_selectors.php` | Descendant, comma-group, nested `find()`, index access |
| `examples/01-getting-started/extract_text.php` | Full-document, element, per-paragraph, list-item plaintext extraction |
| `examples/02-selectors/attribute_selectors.php` | `^=`, `$=`, `*=`, `[!attr]`, comma-group selectors |
| `examples/02-selectors/negative_index.php` | First/last/negative/out-of-bounds index access, null-guard pattern |
| `examples/02-selectors/text_nodes.php` | `find('text')`, whitespace-node filtering, text node manipulation |
| `examples/03-dom-navigation/tree_traversal.php` | All 9 navigation methods; legacy + camelCase pairs |
| `examples/03-dom-navigation/dom_api.php` | 15 camelCase DOM methods |
| `examples/04-modifying-html/modify_content.php` | `outertext`/`innertext` modification; `save()`-based verification |
| `examples/04-modifying-html/attribute_manipulation.php` | Full legacy (`__get`/`__set`/`__isset`/`__unset`) + camelCase attribute API |
| `examples/04-modifying-html/save_to_file.php` | Write/read-back/verify/cleanup cycle using `sys_get_temp_dir()` |
| `examples/05-practical-patterns/callbacks.php` | `set_callback()`, `remove_callback()`, closure counters |
| `examples/05-practical-patterns/table_extraction.php` | Structured PHP array from multi-row table; filter + sort demo |
| `examples/05-practical-patterns/html_sanitization.php` | Script/style/iframe removal; `on*` attribute stripping; educational disclaimer |
| `examples/05-practical-patterns/form_extraction.php` | 9 form field types (text, email, password, number, select, textarea, radio, checkbox, hidden) |
| `examples/06-configuration/error_handling.php` | Error 1001/1002 triggers; `Error` value-object API; `Settings::reset()` discipline |
| `examples/06-configuration/settings.php` | `getMaxFilesize`/`setMaxFilesize`/`reset`; dot-notation custom keys; fallback values |

### Modified Files

| File | Change |
|---|---|
| `example/README.md` | Legacy redirect notice pointing to `examples/README.md` |
| `docs/agents/project-manifest/file-tree.md` | Full `examples/` subtree added; `example/` annotated as legacy |
| `docs/agents/project-manifest/constraints.md` | `find('*')` top-level-only limitation added to CSS Selector Limitations |
| `examples/05-practical-patterns/html_sanitization.php` | `WARNING — EDUCATIONAL DEMO ONLY` docblock on `sanitize_html()` |
| `examples/05-practical-patterns/form_extraction.php` | CSRF token placeholder comment added |

---

## Metrics

| Metric | Value |
|---|---|
| Work packages | 9 / 9 COMPLETE |
| Acceptance criteria met | 44 / 44 |
| PHPUnit tests (final) | **278 / 278 PASS** |
| PHPUnit assertions | 1,194 |
| PHPStan (level 6) | **No errors** |
| Security issues (Critical/High) | **0** |
| Security issues (Medium) | 1 — resolved (educational disclaimer added) |
| Security issues (Info) | 1 — resolved (CSRF token comment added) |
| Code-review reworks | 1 (WP-006: `callbacks.php` internal-state anti-pattern) |
| QA reworks | 1 (WP-009: `file-tree.md` edit not applied in first pass) |

---

## Strategic Findings — Gold Nuggets

These are the most consequential discoveries made during the session. They have direct implications for the library's documentation and potentially its implementation.

### 🔴 High Priority

**1. `find('*')` is not a universal selector**
`find('*')` returns only direct root children of the document, not all descendants as the CSS spec demands. Independently confirmed by Developer and QA. Workaround: iterate `$parser->nodes` and filter by `HDOM_TYPE_ELEMENT`. **`constraints.md` has been updated.** `api-surface.md` should reference the constraint.

**2. `appendChild()` is an incomplete implementation**
`Node::appendChild(Node $node)` sets only the parent pointer (`$node->parent($this)`) and does not insert the node into the children array or rebuild the subtree. Including it in examples would mislead developers. **Action: document this in `api-surface.md` as partial/unsupported, or complete the implementation.**

**3. `outertext = ''` does not remove nodes from `$nodes`**
Setting `$node->outertext = ''` suppresses serialisation output but the node remains in the Parser's internal `$nodes` array. `find('tag')` still returns it. The only truthful removal check is via `save()` string inspection. **`api-surface.md`'s `outertext` entry must document this trap.** The `modify_content.php` example already demonstrates the correct `save()`-based pattern.

### 🟡 Medium Priority

**4. `tbody` selector is transparent to the parser**
`find('tbody tr')` fails silently — `tbody` is treated as transparent per `constraints.md`. The workaround (`find('tr')` with a `th`-child guard to skip header rows) should be explicitly added as a named anti-pattern / workaround to `constraints.md`.

**5. Sibling traversal returns whitespace text nodes**
`next_sibling()` / `prev_sibling()` in a whitespace-preserving parse tree return whitespace text nodes between elements. A `while` loop guarding on `$node->nodetype !== HDOM_TYPE_ELEMENT` is the correct workaround. This should be documented in `api-surface.md` against `next_sibling()`/`prev_sibling()`.

**6. `callbacks.php` internal-state anti-pattern caught in review**
The initial `callbacks.php` demo used `unset($el->_[HDOM_INFO_OUTER])` to reset internal Node state — a pattern that bypasses the library's abstraction layer. Code review caught and rejected it. The rework correctly uses fresh `str_get_html()` instances per section, making each demo self-contained. This pattern (fresh fixture per demo section) is the canonical teaching approach.

### 🔵 Low Priority

**7. `plaintext` / `text()` preserves inter-node whitespace**
`$node->plaintext` preserves whitespace between inline child nodes. Developers working with indented heredoc fixtures or real-world HTML will encounter unexpectedly padded strings. `api-surface.md` should document this on the `plaintext` property entry.

**8. `children()` / `childNodes()` include text/whitespace nodes**
It is unconfirmed whether `children()` / `childNodes()` return all children (including whitespace nodes) or only element children. The `tree_traversal.php` example adds a `nodetype` filter defensively. `api-surface.md` should clarify the return contract unambiguously.

---

## Open Documentation Gaps

These items were flagged as `documentation-forward` by Reviewers and have **not yet been actioned**:

| Priority | Document | Change Needed |
|---|---|---|
| High | `api-surface.md` | `outertext` — document the `$nodes` array retention trap; recommend `save()`-based verification |
| High | `api-surface.md` | `appendChild()` — mark as partial/unsupported; document that only parent pointer is set |
| High | `constraints.md` | `tbody` selector workaround — add as a named pattern (currently undocumented) |
| Medium | `api-surface.md` | `plaintext` / `text()` — document whitespace-preservation behaviour |
| Medium | `api-surface.md` | `children()` / `childNodes()` — clarify whether all nodes or element-only children are returned |
| Medium | `api-surface.md` | `next_sibling()` / `prev_sibling()` — document whitespace text node interleaving; include guard pattern |
| Medium | `data-flows.md` | Add canonical error-handling flow: `str_get_html()` returns `false` → `Settings::get('__error')` → cast to `Error` → `getMessage()` / `getCode()` |

---

## Next Steps

**Immediate — manifest updates (high value, low risk):**
1. Update `api-surface.md` with the seven documentation gaps listed above. The examples already demonstrate the correct patterns; the manifest just needs to match.
2. Add the `tbody` workaround to `constraints.md` CSS Selector Limitations (alongside the `find('*')` entry that was already added this session).

**Near-term — implementation decisions required:**
3. **Decide the fate of `appendChild()`:** either complete the implementation (insert node into children array + rebuild subtree) or explicitly mark it as unsupported/deprecated in the public API surface.
4. **Investigate `find('*')` behaviour** to determine whether it is an intentional design choice or a bug. If it is a bug, a fix in `SelectorParser.php` or `Parser.php` would unlock the universal selector pattern that `html_sanitization.php` currently works around.

**Housekeeping:**
5. Consider removing or archiving the `example/` (legacy) directory in a future breaking-release cycle. The new `examples/` hub fully supersedes it; the redirect notice in `example/README.md` provides a transition path.

---

*All 9 WPs completed. Pipeline health: 9/9 stages pass. Synthesis generated 2026-03-26.*
