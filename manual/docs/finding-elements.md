# Finding Elements

Use `find()` with CSS-like selectors to locate elements in the DOM tree.

## Basic Selectors

```php
// Find all anchors — returns an array of Node objects
$ret = $html->find('a');

// Find the Nth anchor (zero-based) — returns a Node or null
$ret = $html->find('a', 0);

// Find the last anchor (negative index)
$ret = $html->find('a', -1);

// Find all <div> with the "id" attribute
$ret = $html->find('div[id]');

// Find all <div> where id=foo
$ret = $html->find('div[id=foo]');
```

## Shorthand Selectors

```php
// Find all elements with id=foo
$ret = $html->find('#foo');

// Find all elements with class=foo
$ret = $html->find('.foo');

// Find all elements that have an "id" attribute
$ret = $html->find('*[id]');
```

## Multiple Selectors (Comma-Separated)

```php
// Find all anchors and images
$ret = $html->find('a, img');

// Find all anchors and images with a "title" attribute
$ret = $html->find('a[title], img[title]');
```

## Descendant Selectors

```php
// Find all <li> inside <ul>
$es = $html->find('ul li');

// Find nested <div> tags
$es = $html->find('div div div');

// Find all <td> in <table> with class=hello
$es = $html->find('table.hello td');

// Find all <td> with align=center in <table> tags
$es = $html->find('table td[align=center]');
```

## Nested Find (Chained Calls)

```php
// Find all <li> in each <ul>
foreach ($html->find('ul') as $ul) {
    foreach ($ul->find('li') as $li) {
        // do something...
    }
}

// Find the first <li> in the first <ul>
$e = $html->find('ul', 0)->find('li', 0);
```

## Attribute Filters

| Filter | Description |
|---|---|
| `[attribute]` | Matches elements that **have** the specified attribute |
| `[!attribute]` | Matches elements that **don't have** the specified attribute |
| `[attribute=value]` | Matches elements with the attribute **equal to** a value |
| `[attribute!=value]` | Matches elements with the attribute **not equal to** a value |
| `[attribute^=value]` | Matches elements where the attribute **starts with** a value |
| `[attribute$=value]` | Matches elements where the attribute **ends with** a value |
| `[attribute*=value]` | Matches elements where the attribute **contains** a value |

### Quoting Attribute Values

If an attribute value contains spaces, wrap it in quotes:

```php
// WRONG — will not match (space in value breaks the selector)
$html->find('div[style=padding: 0px 2px;] span[class=rf]');

// CORRECT — quote the value
$html->find('div[style="padding: 0px 2px;"] span[class=rf]');
```

## Finding Text and Comment Nodes

```php
// Find all text blocks
$es = $html->find('text');

// Find all comment (<!--...->) blocks
$es = $html->find('comment');
```

## Selector Limitations

The selector engine supports a subset of CSS selectors. See [Selector Limitations](selector-limitations.md) for the full list of what is and isn't supported.

---

[← Creating a DOM Object](creating-dom.md) | [Back to Manual](../README.md) | [Next: Accessing Attributes →](accessing-attributes.md)
