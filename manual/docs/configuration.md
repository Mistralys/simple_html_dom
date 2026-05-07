# Configuration

## Max File Size

By default, the parser refuses to process HTML content larger than **600,000 bytes**. Both `file_get_html()` and `str_get_html()` enforce this limit before parsing.

### Change the Limit at Runtime

```php
use SimpleHtmlDom\Settings;

// Allow up to 2 MB
Settings::setMaxFilesize(2_000_000);

// Check the current limit
echo Settings::getMaxFilesize();
```

Or via the legacy alias:

```php
simple_html_dom_settings::setMaxFilesize(2_000_000);
```

## General Settings Store

`Settings` is a static key/value store used by the library for internal state. You can also use it for custom configuration:

```php
use SimpleHtmlDom\Settings;

Settings::set('my_option', 'value');
$val = Settings::get('my_option');
$val = Settings::get('missing_key', 'default_value');

// Reset all settings to defaults
Settings::reset();
```

> **Warning:** `Settings::reset()` clears **all** stored values, including the `__error` key used by error handling. Call it only when you want a clean slate.

## Constructor Options

These options are available when creating a `Parser` (or calling `str_get_html()` / `file_get_html()`):

| Parameter | Default | Description |
|---|---|---|
| `$lowercase` | `true` | Convert tag names to lowercase during parsing |
| `$forceTagsClosed` | `true` | Auto-close unclosed tags |
| `$target_charset` | `'UTF-8'` | Target character encoding for output |
| `$stripRN` | `true` | Strip `\r\n` pairs from the source before parsing |
| `$defaultBRText` | `"\r\n"` | Text replacement for `<br>` tags in `plaintext` output |
| `$defaultSpanText` | `' '` | Text replacement for `<span>` tags in `plaintext` output |

---

[← Callbacks](callbacks.md) | [Back to Manual](../README.md) | [Next: Error Handling →](error-handling.md)
