# Traversing the DOM

## Navigation Methods

| Method | Returns | Description |
|---|---|---|
| `$e->parent()` | `Node\|null` | The parent element |
| `$e->children()` | `Node[]` | All child **element** nodes (array) |
| `$e->children($n)` | `Node\|null` | The Nth child element (zero-based) |
| `$e->first_child()` | `Node\|null` | First child element |
| `$e->last_child()` | `Node\|null` | Last child element |
| `$e->next_sibling()` | `Node\|null` | Next sibling element |
| `$e->prev_sibling()` | `Node\|null` | Previous sibling element |
| `$e->has_child()` | `bool` | Whether the node has any child elements |
| `$e->find_ancestor_tag($tag)` | `Node\|null` | Walk up the tree to find the first ancestor with the given tag |

> **Important:** `children()`, `first_child()`, `last_child()`, `next_sibling()`, and `prev_sibling()` traverse **element nodes only**. Text nodes, comments, and other non-element nodes are skipped. To access all node types, iterate `$node->nodes` directly.

## Example

```php
echo $html->find('#div1', 0)->children(1)->children(1)->children(2)->id;
```

## CamelCase Aliases (DOM-Style)

All traversal methods also have W3C DOM-style aliases:

| camelCase | Maps to |
|---|---|
| `$e->parentNode()` | `$e->parent()` |
| `$e->childNodes([$idx])` | `$e->children([$idx])` |
| `$e->firstChild()` | `$e->first_child()` |
| `$e->lastChild()` | `$e->last_child()` |
| `$e->nextSibling()` | `$e->next_sibling()` |
| `$e->previousSibling()` | `$e->prev_sibling()` |
| `$e->hasChildNodes()` | `$e->has_child()` |
| `$e->nodeName()` | Returns the tag name |

## Iterating All Node Types

To include text and comment nodes in traversal, use the `nodes` array directly:

```php
foreach ($element->nodes as $node) {
    echo $node->tag . ': ' . $node->outertext . "\n";
}
```

---

[← Modifying HTML](modifying-html.md) | [Back to Manual](../README.md) | [Next: Outputting HTML →](outputting-html.md)
