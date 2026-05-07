# Outputting HTML

## Quick Way (String Casting)

```php
// Dumps the DOM tree back into a string
$str = (string) $html;

// Print it directly
echo $html;
```

## Using `save()`

```php
// Get the HTML as a string
$str = $html->save();

// Save to a file
$html->save('result.htm');
```

> **Note:** `save()` writes with `LOCK_EX` to prevent concurrent write corruption.

## Debug Dumps

### `dump()` — Visual Tree

Prints a human-readable hierarchical view of the DOM:

```php
$html->dump();        // Dump entire document
$element->dump();     // Dump a subtree

$element->dump(false); // Hide attributes
```

### `dump_node()` — Single Node

Prints debug info for a single node:

```php
$element->dump_node();         // Print to stdout
$str = $element->dump_node(false); // Return as string
```

### `dump_html_tree()` — Procedural

```php
dump_html_tree($node, $show_attr = true, $deep = 0);
```

---

[← Traversing the DOM](traversing-dom.md) | [Back to Manual](../README.md) | [Next: Callbacks →](callbacks.md)
