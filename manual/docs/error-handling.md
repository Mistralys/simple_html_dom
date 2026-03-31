# Error Handling

The library does **not** throw exceptions. Errors are stored in the `Settings` store and the loading functions return `false` on failure.

## Checking for Errors

```php
$html = file_get_html('https://example.com/broken');

if ($html === false) {
    $error = simple_html_dom_get_error();
    echo $error->getCode();    // e.g. 1003
    echo $error->getMessage();  // e.g. "HTTP response error"
    echo $error;                // "[1003] HTTP response error"
}
```

## Error Codes

| Code | Constant | Trigger |
|---|---|---|
| **1001** | Empty content | HTML string is empty or null |
| **1002** | Oversized content | HTML exceeds `Settings::getMaxFilesize()` (default: 600,000 bytes) |
| **1003** | Bad HTTP response | `file_get_html()` received a non-200 HTTP status code |

## The `Error` Class

```php
use SimpleHtmlDom\Error;

$error = simple_html_dom_get_error(); // Returns Error|null

$error->getCode();     // int
$error->getMessage();  // string
(string) $error;       // "[{code}] {message}"
```

## Per-Call Error Checking

The error is stored globally under `Settings::get('__error')`. Each call to `file_get_html()` or `str_get_html()` overwrites any previous error. Check for errors immediately after each call:

```php
$page1 = file_get_html('https://example.com/page1');
if ($page1 === false) { /* handle error */ }

$page2 = file_get_html('https://example.com/page2');
if ($page2 === false) { /* handle error — any page1 error is gone */ }
```

---

[← Configuration](configuration.md) | [Back to Manual](../README.md) | [Next: Charset Handling →](charset-handling.md)
