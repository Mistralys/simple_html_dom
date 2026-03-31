# Selector Limitations

The selector engine supports a useful subset of CSS selectors, but not the full CSS Selectors Level 3 specification.

## Supported Selectors

| Selector | Example | Description |
|---|---|---|
| Tag | `div` | Match by tag name |
| ID | `#myid` | Match by `id` attribute |
| Class | `.myclass` | Match by `class` attribute |
| Attribute presence | `[href]` | Element has the attribute |
| Attribute absence | `[!href]` | Element does **not** have the attribute |
| Attribute equals | `[href=val]` | Attribute equals value |
| Attribute not-equals | `[href!=val]` | Attribute does not equal value |
| Attribute starts-with | `[href^=val]` | Attribute starts with value |
| Attribute ends-with | `[href$=val]` | Attribute ends with value |
| Attribute contains | `[href*=val]` | Attribute contains value |
| Comma groups | `a, img` | Match any of the selectors |
| Descendant | `ul li` | Match `li` anywhere inside `ul` |
| Text nodes | `text` | Match text nodes |
| Comments | `comment` | Match comment nodes |

## Not Supported

| Selector | Syntax | Status |
|---|---|---|
| Child combinator | `div > p` | Not supported |
| Adjacent sibling | `div + p` | Not supported |
| General sibling | `div ~ p` | Not supported |
| Pseudo-classes | `:nth-child()`, `:not()`, `:first-child`, etc. | Not supported |
| Pseudo-elements | `::before`, `::after` | Not supported |

## Universal Selector (`*`) Behavior

`find('*')` returns only **direct children** of the context node, not all descendants. This differs from standard CSS behavior.

```php
// Returns only top-level elements, NOT all descendants
$all = $html->find('*');

// To iterate all element nodes in the document, use:
foreach ($html->nodes as $node) {
    if ($node->nodetype === HDOM_TYPE_ELEMENT) {
        // process node
    }
}
```

> **Note:** Attribute-qualified universal selectors like `find('*[class]')` **do** search all descendants as expected.

## `<tbody>` Is Transparent

The parser silently skips `<tbody>` tags (to match browser-generated DOM behavior). Descendant selectors like `find('tbody tr')` will **not** match.

**Workaround:** Select `<tr>` directly and filter out header rows:

```php
$rows = $dom->find('tr');
foreach ($rows as $row) {
    if ($row->find('th', 0)) {
        continue; // skip header rows
    }
    // process data row
}
```

---

[← Charset Handling](charset-handling.md) | [Back to Manual](../README.md) | [Next: API Reference →](api-reference.md)
