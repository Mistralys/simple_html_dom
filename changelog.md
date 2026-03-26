# Simple HTMLDom Changelog

## v2.2 - Security & Null Safety (Breaking-XS)

- SelectorParser: Fixed `[attr*=val]` selectors to escape undelimited values as plain substrings.
- FileHelper: Capped HTTP redirects at 5 hops in `file_get_html()` to prevent infinite loops.
- Node: Fixed `removeAttribute()` to correctly remove the attribute entry instead of writing null.
- TextConverter: Fixed `convert()` to return the original string unchanged when `iconv()` fails.
- Node: Fixed `innertext()`, `outertext()`, `text()`, and `makeup()` to not crash on cleared nodes.
- Docs: Added SSRF warning, post-clear behaviour contract, and redirect limit to constraints.
- Tests: Added regression tests for the security fixes, `removeAttribute()`, and post-clear access.

### Breaking Changes

The `[attr*=val]` selector previously passed undelimited values directly to `preg_match()`, allowing
regex metacharacters to be interpreted as patterns. Values are now escaped via `preg_quote()` and
treated as plain substrings. To retain regex matching, use the explicit delimiter form: `[attr*=/pattern/]`.

## v2.1 - PHP 8.4 Modernization

- Error: Added `__toString()` returning `"[{code}] {message}"` for string casting and logging.
- Settings: Added static `reset()` method for test isolation in `tearDown()`.
- Parser: Extracted `parse_attr()` inline closures into three named private methods.
- Parser: Converted `parse_attr()` from `switch` to a `match` expression.
- Parser: Declared `$optionalClosingArray` explicitly — removes PHP 8.4 dynamic-property notices.
- Parser: Annotated `$callback` with `@var callable|null` PHPDoc.
- Parser: `createElement()` and `createTextNode()` return type narrowed to `Node|false`.
- Node: `find()` now creates a single shared `SelectorParser` for all selector processing.
- Node: Child-navigation methods now safe to call on cleared nodes.
- Bridge: Added parameter and return type declarations to all procedural bridge functions.
- Node: Fixed `dump_node()` — removed an undefined variable reference and unreachable dead code.
- Bridge: Fixed `dump_html_tree()` — corrected argument mismatch when delegating to `Node::dump()`.
- Parser: Fixed operator-precedence bug in tag reading — corrected how `strpos()` result is stored.
- Tests: Added `ParserTest` (8 tests), `NodeTest` (12 tests), and `DumpTest` (10 tests).

## v2.0 - PSR-4 Namespace Restructure

- Library: Refactored into PSR-4 namespace `SimpleHtmlDom\` with dedicated class files.
- Library: Added `NodeType`, `NodeInfo`, and `QuoteStyle` enums replacing `HDOM_*` constants.
- Bridge: Converted to a compatibility shim — all legacy class names and functions work unchanged.
- Library: Applied `readonly` promotion, `private const` constants, and `match` expressions.
- Node: Added PHP 8.4 property hooks for `$outertext` and `$innertext`.
- SelectorParser: Extracted CSS selector parsing, seeking, and matching into a dedicated class.
- TextConverter: Extracted charset-conversion logic into a stateless helper.
- Parser: Refactored `load_file()` from `func_get_args()` to a typed variadic signature.
- Parser: Fixed tokeniser treating `<digit` as a tag opener — content like `<1 mol%` now preserved.
- Parser: Replaced `$http_response_header` with `http_get_last_response_headers()`.
- Composer: Updated autoload to PSR-4 for the `SimpleHtmlDom\` namespace.
- Tests: Added `unit` suite for `Settings`, `Error`, `TextConverter`, and `SelectorParser`.

## v1.7 - PHPUnit Migration

- Tests: Migrated from manual `assert()` test files to PHPUnit 12.x with 209 tests.
- Tests: Organized into three named suites: `parsing`, `selectors`, `dom`.
- Composer: Added PHPUnit 12.x as a dev dependency.

## v1.6 - Error Reporting & Configurable Limits

- Library: Added error messages retrievable with `simple_html_dom_get_error()`.
- Settings: Added `simple_html_dom_settings::setMaxFilesize()` for runtime file size configuration.
