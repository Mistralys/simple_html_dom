# Charset Handling

The parser automatically detects the source charset and converts output to the target charset (default: UTF-8).

## How Detection Works

The parser tries these methods in order:

1. `<meta charset="...">` or `<meta http-equiv="Content-Type" content="...; charset=...">` in the HTML
2. `Content-Type` response header (for URL loads via `file_get_html()`)
3. `mb_detect_encoding()` as a fallback

## Setting the Target Charset

```php
// Via constructor
$html = new Parser($str, target_charset: 'ISO-8859-1');

// Via procedural function
$html = str_get_html($str, target_charset: 'ISO-8859-1');
```

## Checking Detected Charset

```php
echo $html->charset;         // Source charset detected
echo $html->target_charset;  // Target charset for output
```

## Manual Conversion

The `TextConverter` class is available for standalone use:

```php
use SimpleHtmlDom\TextConverter;

$converted = TextConverter::convert($text, 'ISO-8859-1', 'UTF-8');
$isUtf8 = TextConverter::is_utf8($text);
```

---

[← Error Handling](error-handling.md) | [Back to Manual](../README.md) | [Next: Selector Limitations →](selector-limitations.md)
