# Quick Start

## Get HTML Elements

```php
// Create DOM from URL or file
$html = file_get_html('https://www.example.com/');

// Find all images
foreach ($html->find('img') as $element) {
    echo $element->src . "\n";
}

// Find all links
foreach ($html->find('a') as $element) {
    echo $element->href . "\n";
}
```

## Modify HTML Elements

```php
// Create DOM from string
$html = str_get_html('<div id="hello">Hello</div><div id="world">World</div>');

$html->find('div', 1)->class = 'bar';
$html->find('div[id=hello]', 0)->innertext = 'foo';

echo $html;
// Output: <div id="hello">foo</div><div id="world" class="bar">World</div>
```

## Extract Plain Text

```php
echo file_get_html('https://www.example.com/')->plaintext;
```

## Scrape Structured Data

```php
$html = file_get_html('https://news.example.com/');

foreach ($html->find('div.article') as $article) {
    $item['title']   = $article->find('div.title', 0)->plaintext;
    $item['intro']   = $article->find('div.intro', 0)->plaintext;
    $item['details'] = $article->find('div.details', 0)->plaintext;
    $articles[] = $item;
}

print_r($articles);
```

## Memory Management

When processing multiple documents, always free memory after each one:

```php
$html = file_get_html('https://example.com/');
// ... do work ...
$html->clear();
unset($html);
```

> **Tip:** If you only process one document and the `Parser` goes out of scope, the destructor calls `clear()` automatically.

---

[Back to Manual](../README.md) | [Next: Creating a DOM Object →](creating-dom.md)
