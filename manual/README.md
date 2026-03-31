# PHP Simple HTML DOM Parser — Manual

A fast, forgiving HTML parser for PHP 8.4+ that lets you find and manipulate HTML elements using CSS-like selectors.

## Table of Contents

| # | Topic | Description |
|---|---|---|
| 1 | [Quick Start](docs/quick-start.md) | Get up and running in minutes |
| 2 | [Creating a DOM Object](docs/creating-dom.md) | Load HTML from strings, files, or URLs |
| 3 | [Finding Elements](docs/finding-elements.md) | CSS selectors, attribute filters, text & comments |
| 4 | [Accessing Attributes](docs/accessing-attributes.md) | Get, set, remove, and inspect element attributes |
| 5 | [Modifying HTML](docs/modifying-html.md) | Change content, wrap, remove, and append elements |
| 6 | [Traversing the DOM](docs/traversing-dom.md) | Navigate parents, children, and siblings |
| 7 | [Outputting HTML](docs/outputting-html.md) | Save, dump, and serialise the DOM tree |
| 8 | [Callbacks](docs/callbacks.md) | Customise rendering with callback functions |
| 9 | [Configuration](docs/configuration.md) | Max filesize, runtime settings |
| 10 | [Error Handling](docs/error-handling.md) | Detect and inspect parse errors |
| 11 | [Charset Handling](docs/charset-handling.md) | Automatic charset detection and conversion |
| 12 | [Selector Limitations](docs/selector-limitations.md) | What CSS selectors are and aren't supported |
| 13 | [API Reference](docs/api-reference.md) | Complete method and property reference |
| 14 | [FAQ](docs/faq.md) | Common questions and solutions |

## Installation

```bash
composer require shark/simple_html_dom
```

**Requirements:** PHP 8.4+, `ext-mbstring`

## Two Ways to Use the Library

### Procedural (Legacy-compatible)

```php
// From Composer autoload — no require needed
$html = str_get_html('<div>Hello World</div>');
$html = file_get_html('https://example.com');
```

### Object-Oriented (Namespaced)

```php
use SimpleHtmlDom\Parser;

$html = new Parser('<div>Hello World</div>');
```

Both styles are fully supported and produce the same `Parser` instance.
