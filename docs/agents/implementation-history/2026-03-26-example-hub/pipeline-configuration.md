# Pipeline Configuration

> **Plan:** `docs/agents/plans/2026-03-26-example-hub/plan.md`
> **Work Packages:** `docs/agents/plans/2026-03-26-example-hub/work-packages-draft.md`
> **Generated:** 2026-03-26

---

## Per-WP Stage Configuration

| WP | active_pipeline_stages | Rationale |
|----|------------------------|-----------|
| WP-001 | `["implementation", "verification", "code_review"]` | Standard PHP file creation (bootstrap + directories). Verification: smoke test `_bootstrap.php` exit code + `php -l` lint. No documentation files, no release artifacts, no security surface. |
| WP-002 | `["implementation", "verification", "code_review"]` | PHP migration (3 files). Verification: smoke tests + lint per acceptance criteria. No security surface — removes external HTTP calls, uses only inline HTML fixtures. |
| WP-003 | `["implementation", "verification", "code_review"]` | New PHP examples (3 files). Verification: smoke tests + lint. No security surface — demonstrates selectors on static inline HTML. |
| WP-004 | `["implementation", "verification", "code_review"]` | New PHP examples (2 files). Verification: smoke tests + lint. No security surface — read-only DOM traversal on inline fixtures. |
| WP-005 | `["implementation", "verification", "code_review"]` | Mixed PHP examples (3 files). Verification: smoke tests + lint. No security surface — `save_to_file.php` uses `sys_get_temp_dir()` with immediate cleanup; no user-supplied paths or sensitive data. |
| WP-006 | `["implementation", "verification", "security_review", "code_review"]` | Mixed PHP examples (4 files) including `html_sanitization.php`. **Security review required:** the sanitization example demonstrates stripping `<script>`, `<style>`, `<iframe>`, and event-handler attributes — incorrect implementation could teach users unsafe patterns. Must verify completeness of dangerous-attribute list and that the example does not overstate the library's sanitization guarantees. |
| WP-007 | `["implementation", "verification", "code_review"]` | New PHP examples (2 files). Verification: smoke tests + lint. No security surface — demonstrates error objects and settings, no external input or sensitive data. |
| WP-008 | `["documentation", "code_review"]` | Documentation-only (2 Markdown READMEs). No code to execute or lint — verification excluded. Code review ensures the index accurately lists all 18 example files and descriptions match actual content. |
| WP-009 | `["documentation", "verification", "code_review"]` | Documentation-only manifest update (1 Markdown file). Verification included: acceptance criteria explicitly require `composer analyze` and `composer test` regression checks. Code review ensures tree structure matches actual filesystem. |

---

## Stage Exclusion Summary

| Stage | Excluded From | Reason |
|-------|---------------|--------|
| `documentation` | WP-001 through WP-007 | These WPs create PHP example files only — no README, manifest, or documentation file changes |
| `security_review` | WP-001 through WP-005, WP-007 through WP-009 | No security-sensitive operations: no auth, no user input handling, no external API calls, no dangerous-pattern teaching. WP-005's temp file usage is a standard idiom with no user-supplied paths |
| `release` | All WPs | Project creates example files and documentation only — no version bump, no changelog entry, no publishable artifact, no breaking API change. Constraints explicitly state no modifications to `src/`, `tests/`, or `composer.json` |
| `implementation` | WP-008, WP-009 | These WPs produce only Markdown documentation — no PHP code or config files |
| `verification` | WP-008 | Creates 2 Markdown files with no testable behavior; "valid Markdown" is assessed during code review |

---

## Guardrail Notes

1. **WP-006 non-standard chain:** Includes `security_review` between `verification` and `code_review`. This follows canonical ordering (`implementation → verification → security_review → code_review`). The security review scope is narrow: validate that `html_sanitization.php` does not present an incomplete sanitization approach as safe, and that the dangerous-attribute/element lists are reasonable for a teaching example.

2. **WP-009 includes `verification` despite being documentation-only:** This is because WP-009's acceptance criteria explicitly require running `composer analyze` and `composer test` as regression checks. While these should trivially pass (no code is modified), the verification stage formalizes this gate.

3. **No `release` stage in any WP:** Confirmed correct — this plan creates only example files and documentation under `examples/` and `example/`. No library source, test, or `composer.json` changes. No version bump or changelog is warranted for example additions.

4. **WP-008 excludes `verification`:** Unlike WP-009, WP-008's acceptance criteria do not specify `composer analyze` or `composer test` checks. Markdown validity is covered by code review.

---

## JSON Output

```json
{
  "WP-001": {
    "active_pipeline_stages": ["implementation", "verification", "code_review"],
    "rationale": "PHP bootstrap creation; smoke test + lint verification; no docs, release, or security surface"
  },
  "WP-002": {
    "active_pipeline_stages": ["implementation", "verification", "code_review"],
    "rationale": "PHP migration (3 files); smoke tests + lint; no security surface (external HTTP calls removed)"
  },
  "WP-003": {
    "active_pipeline_stages": ["implementation", "verification", "code_review"],
    "rationale": "New PHP examples (3 files); smoke tests + lint; no security surface"
  },
  "WP-004": {
    "active_pipeline_stages": ["implementation", "verification", "code_review"],
    "rationale": "New PHP examples (2 files); smoke tests + lint; no security surface"
  },
  "WP-005": {
    "active_pipeline_stages": ["implementation", "verification", "code_review"],
    "rationale": "Mixed PHP examples (3 files); smoke tests + lint; temp file usage is standard pattern, no security surface"
  },
  "WP-006": {
    "active_pipeline_stages": ["implementation", "verification", "security_review", "code_review"],
    "rationale": "html_sanitization.php teaches security patterns — security review ensures correctness and avoids misleading users"
  },
  "WP-007": {
    "active_pipeline_stages": ["implementation", "verification", "code_review"],
    "rationale": "New PHP examples (2 files); smoke tests + lint; no security surface"
  },
  "WP-008": {
    "active_pipeline_stages": ["documentation", "code_review"],
    "rationale": "Documentation-only (2 Markdown READMEs); no code to verify"
  },
  "WP-009": {
    "active_pipeline_stages": ["documentation", "verification", "code_review"],
    "rationale": "Manifest update; verification for regression checks (composer analyze + test) per acceptance criteria"
  }
}
```
