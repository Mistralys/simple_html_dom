# Creating a DOM Object

## Procedural (Quick Way)

```php
// From a string
$html = str_get_html('<html><body>Hello!</body></html>');

// From a URL
$html = file_get_html('https://www.example.com/');

// From a local file
$html = file_get_html('test.htm');
```

Both functions return a `Parser` instance (aliased as `simple_html_dom`) or `false` on failure.

## Object-Oriented Way

```php
use SimpleHtmlDom\Parser;

// Create and load in one step
$html = new Parser('<html><body>Hello!</body></html>');

// Or create first, load later
$html = new Parser();
$html->load('<html><body>Hello!</body></html>');

// Load from a file or URL
$html = new Parser();
$html->load_file('https://www.example.com/');
$html->load_file('test.htm');
```

## Constructor Parameters

```php
new Parser(
    ?string $str = null,              // HTML string (null = load later)
    bool    $lowercase = true,        // Convert tag names to lowercase
    bool    $forceTagsClosed = true,   // Force all tags to be closed
    string  $target_charset = 'UTF-8', // Target character encoding
    bool    $stripRN = true,           // Strip \r\n from source
    string  $defaultBRText = "\r\n",   // Text replacement for <br>
    string  $defaultSpanText = ' '     // Text replacement for <span>
);
```

## `file_get_html()` Parameters

```php
file_get_html(
    string $url,                       // URL or file path
    bool   $use_include_path = false,  // Search include_path
    mixed  $context = null,            // Stream context (for proxy, auth, etc.)
    int    $offset = -1,               // Start reading from offset
    int    $maxLen = -1,               // Maximum bytes to read
    bool   $lowercase = true,
    bool   $forceTagsClosed = true,
    string $target_charset = 'UTF-8',
    bool   $stripRN = true,
    string $defaultBRText = "\r\n",
    string $defaultSpanText = ' '
);
```

> **Note:** `file_get_html()` follows HTTP redirects up to 5 hops. It uses `file_get_contents()` internally, so `allow_url_fopen` must be enabled in `php.ini` for URL loading.

## Always Check for Errors

Both `str_get_html()` and `file_get_html()` return `false` on failure. Always check the return value:

```php
$html = file_get_html('https://example.com/');

if ($html === false) {
    $error = simple_html_dom_get_error();
    echo "Failed: " . $error; // e.g. "[1003] HTTP response error"
    return;
}

// Safe to use $html now
```

See [Error Handling](error-handling.md) for details on error codes and the `Error` class.

---

[← Quick Start](quick-start.md) | [Back to Manual](../README.md) | [Next: Finding Elements →](finding-elements.md)
