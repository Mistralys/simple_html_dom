# AGENTS.md — Simple HTML DOM

> **Operating manual for AI agents entering this codebase.**
> Read this file first. Follow its directives. Do not guess.

---

## 1. Project Manifest — Start Here!

The **Project Manifest** is the canonical documentation source for this library. It lives at:

```
docs/agents/project-manifest/
```

| Document | Description |
|---|---|
| [README.md](docs/agents/project-manifest/README.md) | Manifest index — lists all documents and their purpose |
| [tech-stack.md](docs/agents/project-manifest/tech-stack.md) | Runtime, language version, dependencies, autoloading, architectural patterns |
| [file-tree.md](docs/agents/project-manifest/file-tree.md) | Annotated directory structure with file-level descriptions |
| [api-surface.md](docs/agents/project-manifest/api-surface.md) | Public constructors, properties, method signatures for all classes and enums |
| [data-flows.md](docs/agents/project-manifest/data-flows.md) | Main interaction paths through the library (parse, find, modify, save) |
| [constraints.md](docs/agents/project-manifest/constraints.md) | Coding rules, backward-compatibility obligations, conventions, and gotchas |

### Quick Start Workflow

1. **Read** `README.md` — understand what this library does and how it's installed.
2. **Read** `tech-stack.md` — learn the runtime, dependencies, and architectural patterns.
3. **Internalize** `constraints.md` — know the rules before writing any code.
4. **Reference** `file-tree.md` — locate files without scanning the filesystem.
5. **Reference** `api-surface.md` — understand public APIs without reading source.
6. **Reference** `data-flows.md` — trace how data moves through the library.

**Only after consulting the manifest**, read source files.

---

## 2. Manifest Maintenance Rules

When you change code, you **must** update the corresponding manifest documents. Use this table to determine which documents are affected.

| Change Made | Documents to Update |
|---|---|
| Public method/property added, removed, or renamed | `api-surface.md` |
| Enum case added or removed | `api-surface.md` |
| File or directory added, removed, or renamed | `file-tree.md` |
| Dependency added or removed in `composer.json` | `tech-stack.md` |
| PHP version requirement changed | `tech-stack.md`, `constraints.md` |
| Architectural pattern introduced or changed | `tech-stack.md` |
| Autoload config changed | `tech-stack.md` |
| New coding convention or constraint established | `constraints.md` |
| Backward-compatibility contract changed | `constraints.md` |
| Bridge file (`src/simple_html_dom.php`) modified | `constraints.md`, `api-surface.md` |
| New test suite or test directory added | `file-tree.md`, `constraints.md` (Test Organisation table) |
| Data flow or parsing pipeline changed | `data-flows.md` |
| Selector engine capabilities changed | `data-flows.md`, `constraints.md` (CSS Selector Limitations) |

---

## 3. Efficiency Rules — Search Smart

Do not wastefully scan source files when the answer is already documented.

- **Finding files?** Check `file-tree.md` FIRST.
- **Understanding methods or class APIs?** Check `api-surface.md` FIRST.
- **Checking implementation patterns or dependencies?** Check `tech-stack.md` FIRST.
- **Understanding how data flows?** Check `data-flows.md` FIRST.
- **Checking rules, limitations, or conventions?** Check `constraints.md` FIRST.
- **Only then** read source files for implementation details.

---

## 4. Failure Protocol & Decision Matrix

| Scenario | Action | Priority |
|---|---|---|
| Ambiguous requirement | Use the most restrictive interpretation | MUST |
| Manifest/code conflict | Trust the manifest; flag the code for a fix | MUST |
| Missing manifest documentation | Flag the gap explicitly; do not invent facts | MUST |
| Untested code path | Proceed with caution; add a test recommendation | SHOULD |
| Legacy API touchpoint | Verify backward compatibility is preserved via the bridge file | MUST |
| Modifying `Node` or `Parser` | Call `Settings::reset()` in test `tearDown()` to avoid cross-test contamination | MUST |
| Adding a public method | Add both snake_case and camelCase variants to maintain dual naming convention | MUST |
| Modifying tag parsing | Verify against the self-closing, block, and optional-closing tag lists in `constraints.md` | MUST |
| Unclear selector behavior | Check the CSS Selector Limitations section in `constraints.md` before assuming support | SHOULD |
| Memory leak concern | Ensure `clear()` is called or the `Parser` goes out of scope (destructor calls `clear()`) | MUST |

---

## 5. Project Stats

| Attribute | Value |
|---|---|
| **Language / Runtime** | PHP 8.4+ |
| **Required Extensions** | `ext-mbstring` |
| **Architecture** | Tree-based DOM with character-stream tokeniser; PSR-4 namespace + legacy bridge |
| **Package Manager** | Composer |
| **Composer Name** | `shark/simple_html_dom` |
| **Test Framework** | PHPUnit 12.x |
| **Static Analysis** | PHPStan Level 6 |
| **Build / Test Commands** | See **Composer Scripts** table below |
| **Autoload** | PSR-4: `SimpleHtmlDom\` → `src/SimpleHtmlDom/`; files: `src/simple_html_dom.php` |
| **Source Directory** | `src/` (9 files: 3 enums, 6 classes) |
| **Test Suites** | `unit`, `parsing`, `selectors`, `dom` (under `tests/`) |

### Composer Scripts

| Command | Description |
|---|---|
| `composer analyze` | Run PHPStan static analysis |
| `composer analyze-save` | Run PHPStan and save output to `phpstan-result.txt` |
| `composer analyze-clear` | Clear PHPStan result cache |
| `composer test` | Run full PHPUnit test suite |
| `composer test-file` | Run tests for a specific file (append `-- path/to/Test.php`) |
| `composer test-suite` | Run a specific test suite (append `-- <suite>`) |
| `composer test-filter` | Run tests matching a filter (append `-- <pattern>`) |
| `composer test-group` | Run tests in a specific group (append `-- <group>`) |
