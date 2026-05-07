# Simple HTML DOM — Examples

Runnable PHP examples organized by topic, demonstrating the most common use-cases of the Simple HTML DOM library.

## Prerequisites

Install Composer dependencies from the project root before running any example:

```bash
composer install
```

## How to run

Each example is a self-contained PHP CLI script. Run from the project root:

```bash
php examples/01-getting-started/basic_selectors.php
```

> **Note:** `_bootstrap.php` is shared infrastructure that every example loads automatically via `require`. It resolves the project root and initializes the Composer autoloader. It is not a runnable example on its own.

---

## 01 — Getting Started

| File | Description |
|---|---|
| `basic_selectors.php` | Tag, ID, class, and attribute selectors — the recommended starting point |
| `advanced_selectors.php` | Descendant, comma-separated, and other compound CSS selectors |
| `extract_text.php` | Extract plaintext content from the full document or individual elements |

## 02 — Selectors

| File | Description |
|---|---|
| `attribute_selectors.php` | Attribute substring matchers: `^=` (starts-with), `$=` (ends-with), `*=` (contains) |
| `negative_index.php` | Negative index idiom: `find("li", 0)` for first match, `find("li", -1)` for last |
| `text_nodes.php` | Find and manipulate raw text nodes using `find("text")` |

## 03 — DOM Navigation

| File | Description |
|---|---|
| `dom_api.php` | Node property methods: `nodeName()`, `hasAttribute()`, `getAttribute()`, `getAllAttributes()` |
| `tree_traversal.php` | Tree traversal: `has_child()`, `children()`, and parent/sibling navigation |

## 04 — Modifying HTML

| File | Description |
|---|---|
| `attribute_manipulation.php` | Read and write element attributes via `__get`/`__set` and explicit methods |
| `modify_content.php` | Remove or replace elements by setting `outertext` |
| `save_to_file.php` | Serialize modified HTML and write it to a file |

## 05 — Practical Patterns

| File | Description |
|---|---|
| `callbacks.php` | Register and remove per-node callbacks invoked during `save()` serialization |
| `form_extraction.php` | Extract all form field types: text inputs, selects, and textareas |
| `html_sanitization.php` | Strip dangerous tags and event attributes to sanitize untrusted HTML |
| `table_extraction.php` | Extract table headers and data rows from `<table>` elements |

## 06 — Configuration

| File | Description |
|---|---|
| `error_handling.php` | Use the `Error` class and handle parse error conditions |
| `settings.php` | Configure global settings: max file size, encoding, and parser options |
