# File Tree

```
simple_html_dom/
├── composer.json              # Package metadata & autoload config
├── phpunit.xml                # PHPUnit configuration (4 test suites)
├── changelog.md               # Version history
├── README.md                  # Project overview & usage instructions
│
├── src/
│   ├── simple_html_dom.php    # Bridge file: HDOM_* constants, class_alias(), procedural API
│   └── SimpleHtmlDom/         # PSR-4 namespaced source
│       ├── Error.php          # Error value object
│       ├── Node.php           # Single DOM node in the parsed tree
│       ├── NodeInfo.php       # Backed enum: HDOM_INFO_* constants
│       ├── NodeType.php       # Backed enum: HDOM_TYPE_* constants
│       ├── Parser.php         # HTML tokeniser / document root
│       ├── QuoteStyle.php     # Backed enum: HDOM_QUOTE_* constants
│       ├── SelectorParser.php # CSS selector parsing, seeking, matching
│       ├── Settings.php       # Static key/value store for global settings
│       └── TextConverter.php  # Stateless charset conversion helper
│
├── tests/
│   ├── DOM/                   # DOM-level integration tests (suite: dom)
│   │   ├── CallbackTest.php
│   │   ├── DomTreeTest.php
│   │   ├── ElementTest.php
│   │   ├── MiscTest.php
│   │   └── ReaderElementTest.php
│   ├── Parsing/               # Parsing fidelity tests (suite: parsing)
│   │   ├── InvalidHtmlTest.php
│   │   ├── StandardTest.php
│   │   └── StripTest.php
│   ├── Selectors/             # CSS selector engine tests (suite: selectors)
│   │   ├── ReaderSelectorTest.php
│   │   └── SelectorTest.php
│   └── Unit/                  # Pure unit tests for namespaced classes (suite: unit)
│       ├── DumpTest.php
│       ├── ErrorTest.php
│       ├── NodeTest.php
│       ├── ParserTest.php
│       ├── SelectorParserTest.php
│       ├── SettingsTest.php
│       └── TextConverterTest.php
│
├── example/                   # Usage examples
│   ├── example_advanced_selector.php
│   ├── example_basic_selector.php
│   ├── example_callback.php
│   ├── example_extract_html.php
│   ├── example_modify_contents.php
│   ├── simple_html_dom_utility.php
│   └── scraping/
│       ├── example_scraping_digg.php
│       ├── example_scraping_imdb.php
│       └── example_scraping_slashdot.php
│
├── app/                       # Demo web app (visual DOM tree viewer)
│   ├── index.php
│   ├── google.htm
│   └── js/                    # jQuery tree-view assets
│
├── manual/                    # HTML manual / API docs
│   ├── manual.htm
│   ├── manual_api.htm
│   ├── manual_faq.htm
│   ├── css/
│   ├── img/
│   └── js/
│
├── docs/agents/               # AI agent documentation
│
└── vendor/                    # Composer dependencies (auto-generated)
```
