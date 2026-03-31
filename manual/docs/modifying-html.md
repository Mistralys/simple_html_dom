# Modifying HTML

## Change Inner Content

```php
$e->innertext = '<b>new content</b>';
```

## Replace an Entire Element

```php
$e->outertext = '<div class="replacement">Replaced!</div>';
```

## Remove an Element

Set `outertext` to an empty string to suppress the element from output:

```php
$e->outertext = '';
```

> **Important:** This only hides the element during serialisation. The node remains in `$parser->nodes` and in `find()` results. It is **not** a true DOM removal.

## Wrap an Element

```php
$e->outertext = '<div class="wrap">' . $e->outertext . '</div>';
```

## Append After an Element

```php
$e->outertext = $e->outertext . '<div>appended</div>';
```

## Insert Before an Element

```php
$e->outertext = '<div>prepended</div>' . $e->outertext;
```

## Extract Plain Text

```php
// Plain text from the entire document
echo $html->plaintext;

// Plain text from a specific element
echo $html->find('div', 0)->plaintext;
```

---

[← Accessing Attributes](accessing-attributes.md) | [Back to Manual](../README.md) | [Next: Traversing the DOM →](traversing-dom.md)
