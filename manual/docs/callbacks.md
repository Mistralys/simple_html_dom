# Callbacks

Register a callback function that runs on every node during serialisation (when the DOM is converted back to HTML via `echo`, `save()`, or reading `outertext`).

## Setting a Callback

```php
// Define a callback function that receives a Node
function my_callback($element) {
    // Hide all <b> tags
    if ($element->tag == 'b') {
        $element->outertext = '';
    }
}

// Register the callback
$html->set_callback('my_callback');

// Callback fires on every node when dumping
echo $html;
```

## How It Works

1. You register a callable via `$html->set_callback($functionName)`.
2. Every time a `Node`'s `outertext()` method is called during rendering, the callback is invoked with that node as the argument.
3. The callback can modify the node (e.g., change attributes, hide it, wrap it) before its HTML is serialised.

## Removing a Callback

```php
$html->remove_callback();
```

---

[← Outputting HTML](outputting-html.md) | [Back to Manual](../README.md) | [Next: Configuration →](configuration.md)
