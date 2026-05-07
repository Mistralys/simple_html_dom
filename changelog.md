# Simple HTMLDom Changelog

## v2.0.0 - Maintenance, Modernization & Security (Breaking-XS)

- Security: Fixed regex-injection in `[attr*=val]` selectors via `preg_quote()`.
- Security: Capped HTTP redirects at 5 hops to prevent SSRF loops.
- Node: Fixed `removeAttribute()` writing null instead of removing the entry.
- Node: Fixed crashes on cleared nodes for `innertext()`, `outertext()`, `text()`, `makeup()`.
- Parser: Fixed tokeniser treating `<digit` as a tag opener.
- Parser: Fixed operator-precedence bug in tag reading.
- Library: Refactored into PSR-4 namespace `SimpleHtmlDom\` with dedicated class files.
- Library: Added `NodeType`, `NodeInfo`, and `QuoteStyle` enums replacing `HDOM_*` constants.
- Library: Applied PHP 8.4 features — `readonly`, property hooks, `match` expressions.
- SelectorParser: Extracted CSS selector logic into a dedicated class.
- TextConverter: Extracted charset-conversion into a stateless helper.
- Bridge: All legacy class names and functions preserved via compatibility shim.
- Tests: Migrated to PHPUnit 12.x with 209 tests across three named suites.
- Composer: Updated autoload to PSR-4; added PHPUnit 12.x dev dependency.

### Breaking Changes

The `[attr*=val]` selector previously passed undelimited values directly to `preg_match()`, allowing
regex metacharacters to be interpreted as patterns. Values are now escaped via `preg_quote()` and
treated as plain substrings. To retain regex matching, use the explicit delimiter form:
`[attr*=/pattern/]`.

## v1.6.0 - Error Reporting & Configurable Limits

- Library: Added error messages retrievable with `simple_html_dom_get_error()`.
- Settings: Added `simple_html_dom_settings::setMaxFilesize()` for runtime file size configuration.
