# FAQ

## Selector Problems

**Q: My selector with spaces in the attribute value doesn't match.**

```php
// WRONG — space breaks the parser
$html->find('div[style=padding: 0px 2px;] span[class=rf]');

// CORRECT — quote the value
$html->find('div[style="padding: 0px 2px;"] span[class=rf]');
```

**Q: `find('tbody tr')` returns nothing.**

The parser treats `<tbody>` as transparent and skips it. Use `find('tr')` directly instead. See [Selector Limitations](selector-limitations.md) for a workaround pattern.

**Q: `find('*')` only returns top-level elements.**

This is by design. The universal selector without attribute qualification returns only direct children. To iterate all elements, use:

```php
foreach ($html->nodes as $node) {
    if ($node->nodetype === HDOM_TYPE_ELEMENT) {
        // ...
    }
}
```

---

## URL / Network Problems

**Q: Works locally, but fails on my remote server.**

`file_get_html()` wraps `file_get_contents()`. The `allow_url_fopen` directive must be `TRUE` in `php.ini` for URL loading. If your host disables it, use cURL instead:

```php
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'https://example.com');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
$str = curl_exec($curl);
curl_close($curl);

$html = str_get_html($str);
```

**Q: My server is behind a proxy.**

Pass a stream context to `file_get_html()`:

```php
$context = stream_context_create([
    'http' => [
        'proxy' => 'tcp://proxy.example.com:8080',
        'request_fulluri' => true,
    ],
]);

$html = file_get_html('https://www.example.com', false, $context);
```

---

## Memory

**Q: The script is leaking memory.**

PHP circular references between `Parser` and `Node` objects can cause memory leaks. Always call `clear()` when you're done with a document, especially in loops:

```php
$html = file_get_html('https://example.com');
// ... do work ...
$html->clear();
unset($html);
```

> **Tip:** If the `Parser` variable goes out of scope, the destructor calls `clear()` automatically. Explicit cleanup is only needed when processing multiple documents in the same scope.

**Q: The parser refuses to load my large file.**

The default maximum file size is 600,000 bytes. Increase it before loading:

```php
use SimpleHtmlDom\Settings;

Settings::setMaxFilesize(5_000_000); // 5 MB
$html = file_get_html('large-page.html');
```

---

## Output

**Q: How do I get the modified HTML back as a string?**

```php
$str = $html->save();
// or
$str = (string) $html;
```

**Q: How do I save the modified HTML to a file?**

```php
$html->save('output.html');
```

**Q: How do I remove an element?**

Set its `outertext` to an empty string:

```php
$html->find('div.ad', 0)->outertext = '';
echo $html; // The element is gone from the output
```

> **Note:** The node still exists in `find()` results — this is render-time suppression, not DOM removal.

---

[← API Reference](api-reference.md) | [Back to Manual](../README.md)
