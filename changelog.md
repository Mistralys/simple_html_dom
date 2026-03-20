# Simple HTMLDom Changelog

## v2.1

### Minor Improvements
- **L-001**: `Error::__toString()` added; returns `"[{code}] {message}"` for easy string casting and logging
- **L-002**: `Settings::reset()` static method added; clears all settings — useful for test isolation in `tearDown()`; `SettingsTest::tearDown()` updated to use it
- **L-003**: `Parser::parse_attr()` IIFE closures extracted into three named `private` methods — `parseDoubleQuotedAttr`, `parseSingleQuotedAttr`, `parseUnquotedAttr` — each accepting `(Node $node, string $name): void`; the `match` expression in `parse_attr()` now calls these methods directly; pure internal refactor with no behaviour or API change

### PHP 8.4 Modernization Completions
- **M-001**: `Parser` now declares `private ?array $optionalClosingArray = null`; eliminates PHP 8.4 dynamic-property deprecation notice when `forceTagsClosed: false`
- **M-002**: All four procedural bridge functions (`file_get_html`, `str_get_html`, `simple_html_dom_get_error`, `dump_html_tree`) now carry typed parameter and return type declarations
- **M-003**: `Parser::parse_attr()` converted from `switch` to `match` expression; consistent with `match` usage elsewhere in the modernized codebase
- **M-004**: `Parser::$callback` annotated with `@var callable|null` PHPDoc (PHP does not support `callable` as a standalone property type)
- **M-005**: `Node::find()` creates a single `SelectorParser` instance for `parseSelector()`; `seek()`, `match()`, and `parse_selector()` accept an optional `?SelectorParser` parameter for future sharing
- **M-006**: `Parser::createElement()` and `Parser::createTextNode()` now instantiate `Parser` directly; `@str_get_html()` calls with error suppression removed; return types narrowed from `mixed` to `Node|false`
- **M-007**: `Node::first_child()`, `last_child()`, `next_sibling()`, `prev_sibling()` guard against null `$children` with `?? []`; safe to call on cleared nodes

### New Unit Tests
- `tests/Unit/ParserTest.php` added: 8 test methods covering `Parser` load/find/save API and the M-001 deprecation regression
- `tests/Unit/NodeTest.php` added: 12 test methods covering `Node` navigation, text, and makeup methods; includes regression guards for B-001/B-002 (`testDumpNodeRegressionB001B002`), B-003 (`testDumpHtmlTree`), and M-007 (`testNullChildrenAfterClear`)
- `tests/Unit/DumpTest.php` added: 10 test methods dedicated to the dump/debug output surface — `Node::dump()` (attrs on/off, recursive tree depth), `Node::dump_node()` (return mode, echo mode, with/without attributes, `HDOM_INFO_INNER` populated vs absent), and `dump_html_tree()` (delegation equivalence and depth offset)

### Bug Fixes
- **B-001**: Fixed `Node::dump_node()` — corrected `$node->_[HDOM_INFO_INNER]` to `$this->_[HDOM_INFO_INNER]`; `$node` was undefined in that method (copy-paste residue from single-file source)
- **B-002**: Fixed `Node::dump_node()` — removed dead `isset($this->text)` block; `$text` is not a declared property on `Node` and the block was never reachable under PHP 8.4
- **B-003**: Fixed `dump_html_tree()` in bridge file — corrected `$node->dump($node)` to `$node->dump($show_attr, $deep)` to match `Node::dump(bool, int)` signature
- **B-004**: Fixed operator-precedence bug in `Parser::read_tag()` — added explicit parentheses to `($pos = strpos($tag, '<')) !== false` so `$pos` receives the byte offset rather than a boolean

## v2.0
- Refactored the library into a multi-file PSR-4 namespace under `SimpleHtmlDom\`
- New class files: `NodeType` (enum), `QuoteStyle` (enum), `NodeInfo` (enum), `Node`, `Parser`, `Settings`, `Error`, `SelectorParser`, `TextConverter`
- `src/simple_html_dom.php` converted to a bridge file: defines all `HDOM_*` constants pointing to enum values and registers `class_alias()` entries for all legacy class names (`simple_html_dom`, `simple_html_dom_node`, `simple_html_dom_settings`, `simple_html_dom_error`)
- Full backward compatibility preserved: all legacy class names, constants, and procedural functions continue to work without modification
- PHP 8.4 idioms applied throughout: property hooks on `Node::$outertext` and `Node::$innertext`, `readonly` constructor promotion in `Error` and `SelectorParser`, `private const string/array` token constants in `Parser`, `match` expression in `SelectorParser::match()`
- `SelectorParser` extracted from `Node` to handle CSS selector parsing, seeking, and matching as a dedicated class
- `TextConverter` extracted from `Node` as a stateless charset-conversion helper
- `Parser::load_file()` refactored from `func_get_args()` to a typed variadic `string ...$args` signature
- **Bug fix**: tokeniser no longer treats `<` followed by a digit as a tag opener (HTML5 compliant fix); content like `<1 mol%` and `<2NaCl` now round-trips correctly
- `$http_response_header` superglobal replaced with `http_get_last_response_headers()` (PHP 8.4 API; eliminates deprecation notices)
- `composer.json` autoload updated to PSR-4 for `SimpleHtmlDom\` namespace
- `phpunit.xml` updated with `<source>` element for coverage reports and a new `unit` testsuite pointing at `tests/Unit/`
- New unit test suite added under `tests/Unit/`: `SettingsTest` (6 tests), `ErrorTest` (5 tests), `TextConverterTest` (8 tests), `SelectorParserTest` (12 tests) — covering the new namespaced classes directly
- All 209 existing PHPUnit tests pass without modification (except `StandardTest::testChemistryFormula` which is now a round-trip equality assertion after the tokeniser fix)

## v1.7
- Migrated test suite from manual `assert()`-based files under `testcase/` to PHPUnit 12.x
- New `tests/` directory with 10 test classes organised into three named suites: `parsing`, `selectors`, `dom`
- 209 tests / 1014 assertions; all suites pass independently and in random order
- Original `testcase/` directory retained; `testcase/all_test.php` still passes
- Added `phpunit.xml` configuration and `phpunit/phpunit ^12.0` dev dependency
- Identified known edge case: `<1 mol% …` content after a broken tag opener is silently discarded by the parser (documented in `StandardTest::testChemistryFormula`)

## v1.6
- Added error messages, retrievable with `simple_html_dom_get_error()`
- Made it possible to set the max file size anytime using `simple_html_dom_settings::setMaxFilesize()`
