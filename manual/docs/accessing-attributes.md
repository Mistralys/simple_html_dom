# Accessing Attributes

## Get, Set, and Remove Attributes

```php
// Get an attribute
$value = $e->href;

// Set an attribute
$e->href = 'https://example.com';

// Remove an attribute (set to null)
$e->href = null;

// Check if an attribute exists
if (isset($e->href)) {
    echo 'href exists!';
}
```

> **Non-value attributes** (e.g. `checked`, `selected`, `disabled`) return `true` or `false` when read. Set them to `true` or `false` to toggle.

## Magic Attributes

These virtual properties provide convenient access to a node's content:

```php
$html = str_get_html('<div>foo <b>bar</b></div>');
$e = $html->find('div', 0);

echo $e->tag;       // "div"
echo $e->outertext; // "<div>foo <b>bar</b></div>"
echo $e->innertext; // "foo <b>bar</b>"
echo $e->plaintext; // "foo bar"
```

| Property | Read | Write | Description |
|---|---|---|---|
| `$e->tag` | Yes | Yes | The tag name of the element |
| `$e->outertext` | Yes | Yes | The outer HTML (element + contents) |
| `$e->innertext` | Yes | Yes | The inner HTML (contents only) |
| `$e->plaintext` | Yes | — | Plain text content (tags stripped) |

## DOM-Style Attribute Methods

These methods provide a W3C DOM-like interface:

```php
// Get all attributes as an array
$attrs = $e->getAllAttributes();

// Get a single attribute
$value = $e->getAttribute('href');

// Set an attribute
$e->setAttribute('href', 'https://example.com');

// Check if an attribute exists
if ($e->hasAttribute('href')) { ... }

// Remove an attribute
$e->removeAttribute('href');
```

---

[← Finding Elements](finding-elements.md) | [Back to Manual](../README.md) | [Next: Modifying HTML →](modifying-html.md)
