# Tech Stack & Patterns

## Runtime & Language

| Item | Value |
|---|---|
| Language | PHP |
| Minimum Version | 8.4+ |
| Required Extensions | `ext-mbstring` |

## Package Identity

| Item | Value |
|---|---|
| Composer Name | `shark/simple_html_dom` |
| Type | Library |
| License | MIT (declared in `composer.json` and `LICENSE` file) |
| Origin | Fork of [samacs/simple_html_dom](https://github.com/samacs/simple_html_dom) (originally from SourceForge) |
| Repository | `https://github.com/Mistralys/simple_html_dom.git` |

## Dependencies

### Runtime

- PHP `^8.4`
- `ext-mbstring`

### Dev

- `phpunit/phpunit` `^12.0`
- `phpstan/phpstan` `^2.1`
- `phpstan/phpstan-phpunit` `^2.0`
- `roave/security-advisories` `dev-latest`

## Build & Package Management

| Tool | Details |
|---|---|
| Package Manager | Composer |
| Autoload | PSR-4: `SimpleHtmlDom\` → `src/SimpleHtmlDom/`; plus `files`: `src/simple_html_dom.php` (bridge) |
| Test Runner | PHPUnit 12.x (`composer test` or `vendor/bin/phpunit`) |
| Test Config | `phpunit.xml` at project root |

## Architectural Patterns

- **Namespace + Legacy Bridge**: Core logic lives in PSR-4 namespaced classes under `SimpleHtmlDom\`. A bridge file (`src/simple_html_dom.php`) defines `HDOM_*` global constants, `class_alias()` mappings, and procedural functions to maintain full backward compatibility with the legacy single-file API.
- **Tree-based DOM**: HTML is parsed into a tree of `Node` objects rooted at a `Parser` instance. The parser is a character-stream tokeniser; no external parsing library is used.
- **CSS Selector Engine**: `SelectorParser` implements a subset of CSS selectors (tag, id, class, attribute operators) and is invoked via `Node::find()`.
- **Backed Enums**: `NodeType`, `QuoteStyle`, and `NodeInfo` are PHP 8.1+ backed integer enums whose `->value` matches the legacy `HDOM_*` integer constants.
- **PHP 8.4 Idioms**: Property hooks (`Node::$outertext`, `Node::$innertext`), `readonly` constructor promotion (`Error`, `SelectorParser`), `match` expressions, typed variadic parameters.
- **Static Settings Store**: `Settings` is a static key/value store for library-global configuration (e.g., max file size).
