# PHP Simple HTML DOM Parser

A fast, forgiving HTML parser for PHP 8.4+ that lets you find and manipulate HTML elements using CSS-like selectors — no ext-dom required.

## ✨ Features

- **CSS-like selectors** — find elements by tag, `#id`, `.class`, `[attribute]`, and more
- **Two APIs, one engine** — use procedural functions (`str_get_html`, `file_get_html`) or the namespaced OOP interface (`Parser`, `Node`)
- **Forgiving parser** — handles messy, real-world HTML without choking
- **Read and modify** — change attributes, inner/outer HTML, and serialize back to string or file
- **Tree traversal** — navigate parents, children, and siblings
- **Callbacks** — hook into serialization with per-node callback functions
- **Charset conversion** — automatic detection and conversion via `ext-mbstring`
- **Configurable** — adjust max file size, encoding, and parser behavior at runtime
- **Fully backward compatible** — legacy `simple_html_dom` code works without changes

## 📋 Requirements

- PHP 8.4+
- `ext-mbstring`

## 🚀 Quick Start

Install via Composer:

```bash
composer require shark/simple_html_dom
```

> **Note:** If the package is not available on Packagist, add the repository to your `composer.json`:
> ```json
> "repositories": [{ "type": "vcs", "url": "https://github.com/Mistralys/simple_html_dom.git" }]
> ```

Parse HTML and find elements:

```php
use SimpleHtmlDom\Parser;

$html = new Parser('<ul><li class="active">One</li><li>Two</li></ul>');

// Find by CSS selector
$active = $html->find('li.active', 0);
echo $active->plaintext; // "One"

// Modify and output
$active->class = 'done';
echo $html->save(); // <ul><li class="done">One</li><li>Two</li></ul>
```

Or use the procedural API:

```php
$html = str_get_html('<p>Hello <b>World</b></p>');
echo $html->find('b', 0)->plaintext; // "World"
```

## 📖 Learn More

| Resource | Description |
|---|---|
| **[Manual](manual/README.md)** | Full documentation — 14 guides covering selectors, DOM traversal, modification, configuration, and more |
| **[Examples](examples/README.md)** | 16 runnable PHP scripts organized by topic |
| **[Changelog](changelog.md)** | Version history and recent changes |

### Development

```bash
composer install       # Install dev dependencies
composer test          # Run the full test suite (PHPUnit 12.x)
composer analyze       # Run static analysis (PHPStan Level 6)
```

## 📜 License

[MIT](LICENSE)

## 🔗 Origin

Fork of [samacs/simple_html_dom](https://github.com/samacs/simple_html_dom), originally by S.C. Chen ([SourceForge](http://simplehtmldom.sourceforge.net/)).
