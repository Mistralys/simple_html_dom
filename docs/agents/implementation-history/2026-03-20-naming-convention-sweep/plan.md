# Plan

## Summary

Perform a targeted naming-convention sweep across `Parser.php` (and optionally other classes) to
align the three `camelCase` private methods introduced in the `maintenance-modernization` sprint with
the prevailing `snake_case` naming convention used throughout the class. Also remove the now-
redundant `// next` comments that were copied verbatim from the inline parsing context.

This is a **refactoring-only** plan: no behaviour changes, no public-API changes, no new features.

---

## Background

During the `maintenance-modernization` sprint, three private methods were extracted from the
inline attribute-parsing loop inside `Parser`:

- `parseDoubleQuotedAttr()`
- `parseSingleQuotedAttr()`
- `parseUnquotedAttr()`

These names use `camelCase`, but every other method in `Parser` (and in the rest of the library)
uses `snake_case` (`load`, `load_file`, `prepare`, `parse_charset`, `remove_noise`,
`restore_noise`, etc.). The inconsistency was flagged as a Code Insight observation in the
synthesis report for the `maintenance-modernization` branch (item FU-3).

Each of those three methods also contains one or more `// next` comments that were copied from the
original inline code and are now redundant — they add noise without describing anything meaningful
in their new context.

---

## Scope

### In scope

1. **Rename the three private methods** in `src/SimpleHtmlDom/Parser.php`:
   - `parseDoubleQuotedAttr` → `parse_double_quoted_attr`
   - `parseSingleQuotedAttr` → `parse_single_quoted_attr`
   - `parseUnquotedAttr`     → `parse_unquoted_attr`

2. **Update all call-sites** of these methods within `Parser.php` (the methods are private, so no
   other files reference them).

3. **Remove `// next` comments** inside those three methods (and any other redundant inline
   comments introduced alongside them).

4. **Audit pass**: Scan the rest of `Parser.php`, `Node.php`, `SelectorParser.php`, and
   `TextConverter.php` for any other `camelCase` method names that conflict with the prevailing
   `snake_case` convention, and list them. If any are found, include them in the same rename batch
   or open a follow-up plan as appropriate.

### Out of scope

- Public API methods / camelCase *DOM API delegates* (`getAttribute`, `setAttribute`,
  `getElementByTagName`, etc.) — these are intentionally camelCase because they mirror the W3C DOM
  interface and must not be renamed.
- Any changes to test files beyond updating call-sites if the renamed methods happen to be called
  from tests (unlikely given they are private).
- Behavioural or algorithmic changes of any kind.

---

## Approach

All renames are mechanical search-and-replace within `Parser.php`. Because the methods are
`private`, PHP will catch any missed call-site at parse time, making the change safe to verify
with a single `composer dump-autoload && php -l src/SimpleHtmlDom/Parser.php` followed by the
full test suite.

---

## Detailed Steps

1. **Open `src/SimpleHtmlDom/Parser.php`.**

2. **Rename method definitions** (three occurrences):
   ```
   private function parseDoubleQuotedAttr(  →  private function parse_double_quoted_attr(
   private function parseSingleQuotedAttr(  →  private function parse_single_quoted_attr(
   private function parseUnquotedAttr(      →  private function parse_unquoted_attr(
   ```

3. **Rename all call-sites** within the same file (typically one call each inside the attribute-
   parsing dispatch block).

4. **Remove `// next` comments** inside the three renamed methods.

5. **Audit** the remainder of `Parser.php` and the other source files listed above for additional
   `camelCase` method names that are not part of the intentional DOM API. Document findings here
   or open a follow-up plan.

6. **Run static analysis** (`phpstan` / `psalm` if configured) to confirm no type errors were
   introduced.

7. **Run the full test suite** (`php vendor/bin/phpunit`) and confirm 0 failures, 0 errors, 0
   risky tests.

---

## Required Components

Existing files to be modified:

- `src/SimpleHtmlDom/Parser.php` (renames + comment removal)

New files:

- None.

---

## Acceptance Criteria

- All three methods in `Parser.php` use `snake_case` names.
- No `camelCase` method names remain in `Parser.php` outside the intentional DOM API delegate
  block.
- All `// next` comments removed from the renamed methods.
- Full test suite passes with 0 failures, 0 errors, 0 risky tests.
- No public API surface changed.

---

## Compatibility & Migration Notes

All three methods being renamed are declared `private`. This has the following implications:

- **No public API break.** The methods are invisible to any caller outside `Parser.php`. There is no
  BC break whatsoever.
- **No external migration required.** Users of the library do not need to update any call-sites.
- **Internal call-sites only.** All call-sites are within `Parser.php` itself (`parse_attr()` calls
  all three via a `match` expression). These must be updated atomically in the same commit.
- **PHP strict-types safety.** The class already uses `declare(strict_types=1)` at the top of the
  file. Renaming method identifiers does not affect type checking — argument and return types remain
  unchanged.
- **Test suite impact.** The renamed methods are `private` and therefore not directly reachable from
  unit tests. No test changes are expected; the test suite is purely a regression guard here.

---

## Broader camelCase Assessment

The following survey was performed across all four source files in `src/SimpleHtmlDom/`:

### `Parser.php`

| Method | Visibility | Convention | Rename? |
|---|---|---|---|
| `parseDoubleQuotedAttr` | `private` | camelCase ❌ | **Yes — primary target** |
| `parseSingleQuotedAttr` | `private` | camelCase ❌ | **Yes — primary target** |
| `parseUnquotedAttr` | `private` | camelCase ❌ | **Yes — primary target** |
| `childNodes`, `firstChild`, `lastChild`, `createElement`, `createTextNode`, `getElementById`, `getElementsById`, `getElementByTagName`, `getElementsByTagName`, `loadFile` | `public` | camelCase (intentional) | **No — DOM API delegates** |

All other methods in `Parser` use `snake_case` — no additional renames needed within this file
beyond the three primary targets.

### `Node.php`

| Method | Visibility | Convention | Rename? |
|---|---|---|---|
| `getAllAttributes`, `getAttribute`, `setAttribute`, `hasAttribute`, `removeAttribute`, `getElementById`, `getElementsById`, `getElementByTagName`, `getElementsByTagName`, `parentNode`, `childNodes`, `firstChild`, `lastChild`, `nextSibling`, `previousSibling`, `hasChildNodes`, `nodeName`, `appendChild` | `public` | camelCase (intentional) | **No — W3C DOM API delegates** |

All other methods in `Node` use `snake_case`. No renames needed.

### `SelectorParser.php`

| Method | Visibility | Convention | Rename? |
|---|---|---|---|
| `parseSelector` | `public` | camelCase ❌ | Candidate — see note |
| `seek` | `public` | snake_case ✅ | No |
| `match` | `public` | snake_case ✅ | No |

`parseSelector` is a public method on an internal helper class. It is called from `Node.php`
(`parse_selector`). While not part of the public library API, renaming it would require updating
`Node.php` as well. This is a low-risk follow-up that can be batched with this plan or deferred;
include it as an **optional stretch goal** in the same work package.

### `TextConverter.php`

| Method | Visibility | Convention | Rename? |
|---|---|---|---|
| `isUtf8` | `private static` | camelCase ❌ | Candidate — see note |
| `convert` | `public static` | snake_case ✅ | No |

`isUtf8` is a `private static` helper called only within `TextConverter::convert()`. Renaming it
to `is_utf8` is a clean, self-contained change that can be included in this sweep if desired, or
deferred to a follow-up.

**Conclusion:** Outside the three primary targets in `Parser.php`, only `SelectorParser::parseSelector`
and `TextConverter::isUtf8` are candidates for renaming. Both are low-risk and can be included in
the same atomic commit or deferred. The W3C DOM API delegates in `Node.php` and `Parser.php` are
intentionally camelCase and must **not** be renamed.

---

## Companion Fix — Redundant `// next` Comments

The three methods being renamed each contain one or two `// next` inline comments that were copied
verbatim from the original inline parsing context in `read_tag()`:

```php
private function parseDoubleQuotedAttr(Node $node, string $name): void
{
    $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
    $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next  ← remove
    $node->attr[$name] = $this->restore_noise($this->copy_until_char_escape('"'));
    $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next  ← remove
}
```

In the original inlined code these `// next` labels were useful landmarks between sequential cursor
advances. After extraction into named methods the comment adds noise without adding meaning — the
method name itself describes the operation. Both `// next` occurrences in
`parseDoubleQuotedAttr` and the one in `parseSingleQuotedAttr` **must be removed** as part of this
rename sweep. `parseUnquotedAttr` does not advance the cursor and therefore has no `// next`
comment to remove.

This is a **companion cleanup** that ships in the same commit as the renames — it keeps the diff
atomic and leaves no dead comments behind.

---

## Dependencies

- This plan has no dependencies on other open plans.
- It should be executed **after** the `2026-03-20-synthesis-followups` plan is merged, to avoid
  conflicts on `Parser.php`.
