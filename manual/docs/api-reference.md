# API Reference

## Procedural Helper Functions

| Signature | Description |
|---|---|
| `file_get_html(string $url, ...): Parser\|false` | Create a DOM from a file or URL |
| `str_get_html(string $str, ...): Parser\|false` | Create a DOM from an HTML string |
| `simple_html_dom_get_error(): Error\|null` | Retrieve the last parse error |
| `dump_html_tree(Node $node, bool $show_attr = true, int $deep = 0): void` | Print a visual DOM tree |

---

## `Parser` (DOM Object)

**Namespace:** `SimpleHtmlDom\Parser`
**Legacy alias:** `simple_html_dom`

### Properties

| Property | Type | Description |
|---|---|---|
| `$root` | `?Node` | The root node of the parsed tree |
| `$nodes` | `Node[]` | Flat array of all nodes in document order |
| `$callback` | `callable\|null` | Registered callback function |
| `$lowercase` | `bool` | Whether tag names are lowercased |
| `$original_size` | `int` | Original HTML size in bytes |
| `$size` | `int` | Current HTML size after processing |

### Magic Properties (Read-Only)

| Property | Type | Description |
|---|---|---|
| `->outertext` | `string` | Full document HTML |
| `->innertext` | `string` | Document body HTML |
| `->plaintext` | `string` | Plain text content |
| `->charset` | `string` | Detected source charset |
| `->target_charset` | `string` | Target charset for output |

### Loading Methods

| Method | Description |
|---|---|
| `load(string $str, ...): static` | Load HTML from a string |
| `load_file(string $filename, ...): void` | Load HTML from a file or URL |
| `loadFile(string $filename, ...): void` | camelCase alias for `load_file()` |

### Searching

| Method | Description |
|---|---|
| `find(string $selector, ?int $idx = null): Node\|Node[]\|null` | Find elements by CSS selector |

### Output

| Method | Description |
|---|---|
| `save(string $filepath = ''): string` | Serialise the DOM to string, optionally write to file |
| `__toString(): string` | Same as `save()` |

### Callbacks

| Method | Description |
|---|---|
| `set_callback(mixed $function_name): void` | Register a render callback |
| `remove_callback(): void` | Remove the registered callback |

### Lifecycle

| Method | Description |
|---|---|
| `clear(): void` | Free memory (nulls all node references) |
| `dump(bool $show_attr = true): void` | Print a debug tree view |

### Noise Handling

| Method | Description |
|---|---|
| `restore_noise(string $text): string` | Re-insert stripped content (scripts, styles, etc.) into a text |
| `search_noise(string $text): ?string` | Find a noise placeholder in the text |

### DOM-Style Delegates

| Method | Maps to |
|---|---|
| `childNodes([$idx]): Node\|Node[]\|null` | `$root->children()` |
| `firstChild(): ?Node` | `$root->first_child()` |
| `lastChild(): ?Node` | `$root->last_child()` |
| `getElementById(string $id): ?Node` | `find("#$id", 0)` |
| `getElementsById(string $id, [$idx]): mixed` | `find("#$id", [$idx])` |
| `getElementByTagName(string $name): ?Node` | `find($name, 0)` |
| `getElementsByTagName(string $name, [$idx]): mixed` | `find($name, [$idx])` |
| `createElement(string $name, $value = null): Node\|false` | Create a new element node |
| `createTextNode(string $value): Node\|false` | Create a new text node |

---

## `Node` (Element Object)

**Namespace:** `SimpleHtmlDom\Node`
**Legacy alias:** `simple_html_dom_node`

### Properties

| Property | Type | Description |
|---|---|---|
| `$nodetype` | `int` | Node type (see constants below) |
| `$tag` | `string` | Tag name |
| `$attr` | `array` | Associative array of attributes |
| `$children` | `?Node[]` | Child element nodes |
| `$nodes` | `?Node[]` | All child nodes (elements, text, comments) |
| `$parent` | `?Node` | Parent node reference |
| `$tag_start` | `int` | Byte offset of the tag in source |

### Virtual Properties

| Property | Read | Write | Description |
|---|---|---|---|
| `$outertext` | Yes | Yes | Outer HTML of the element |
| `$innertext` | Yes | Yes | Inner HTML of the element |
| `$plaintext` | Yes | — | Plain text (tags stripped) |

### Attribute Access (Magic Methods)

```php
$e->href;             // Get
$e->href = 'val';     // Set
isset($e->href);      // Exists?
$e->href = null;      // Remove
```

### Content Methods

| Method | Description |
|---|---|
| `innertext(): string` | Get inner HTML |
| `outertext(): string` | Get outer HTML |
| `text(): string` | Get plain text (recursive) |
| `xmltext(): string` | Get inner XML text |
| `makeup(): string` | Reconstruct the opening tag |

### Search

| Method | Description |
|---|---|
| `find(string $selector, ?int $idx = null): Node\|Node[]\|null` | Find descendants matching a CSS selector |

### Tree Navigation

| Method | Description |
|---|---|
| `parent(): ?Node` | Parent node |
| `children(int $idx = -1): Node\|Node[]\|null` | Child elements (or Nth child) |
| `first_child(): ?Node` | First child element |
| `last_child(): ?Node` | Last child element |
| `next_sibling(): ?Node` | Next sibling element |
| `prev_sibling(): ?Node` | Previous sibling element |
| `has_child(): bool` | Whether the node has child elements |
| `find_ancestor_tag(string $tag): ?Node` | Walk up to find ancestor by tag |

### Debug

| Method | Description |
|---|---|
| `dump(bool $show_attr = true, int $deep = 0): void` | Print subtree |
| `dump_node(bool $echo = true): ?string` | Print/return debug info for this node |

### DOM-Style Delegates

| camelCase Method | Maps to |
|---|---|
| `getAllAttributes()` | `$e->attr` |
| `getAttribute($name)` | `$e->$name` |
| `setAttribute($name, $value)` | `$e->$name = $value` |
| `hasAttribute($name)` | `isset($e->$name)` |
| `removeAttribute($name)` | `$e->$name = null` |
| `getElementById($id)` | `find("#$id", 0)` |
| `getElementsById($id, [$idx])` | `find("#$id", [$idx])` |
| `getElementByTagName($name)` | `find($name, 0)` |
| `getElementsByTagName($name, [$idx])` | `find($name, [$idx])` |
| `parentNode()` | `parent()` |
| `childNodes([$idx])` | `children([$idx])` |
| `firstChild()` | `first_child()` |
| `lastChild()` | `last_child()` |
| `nextSibling()` | `next_sibling()` |
| `previousSibling()` | `prev_sibling()` |
| `hasChildNodes()` | `has_child()` |
| `nodeName()` | Returns `$tag` |
| `appendChild(Node $node)` | Append a child node (see warning below) |

> **Warning — `appendChild()`:** This method has known defects and is functionally unsupported. It does not properly move the node from its previous parent, propagate the Parser reference, or rebuild index positions.

---

## `Settings`

**Namespace:** `SimpleHtmlDom\Settings`
**Legacy alias:** `simple_html_dom_settings`

| Method | Description |
|---|---|
| `Settings::setMaxFilesize(int $bytes): void` | Set the max parseable file size |
| `Settings::getMaxFilesize(): int` | Get the current max file size |
| `Settings::set(string $name, mixed $value): void` | Store a setting |
| `Settings::get(string $name, mixed $default = null): mixed` | Retrieve a setting |
| `Settings::reset(): void` | Clear all settings |

---

## `Error`

**Namespace:** `SimpleHtmlDom\Error`
**Legacy alias:** `simple_html_dom_error`

| Method | Description |
|---|---|
| `getCode(): int` | Error code (1001, 1002, 1003) |
| `getMessage(): string` | Human-readable message |
| `__toString(): string` | `"[{code}] {message}"` |

---

## `TextConverter`

**Namespace:** `SimpleHtmlDom\TextConverter`

| Method | Description |
|---|---|
| `TextConverter::convert(string $text, string $from, string $to): string` | Convert charset |
| `TextConverter::is_utf8(mixed $str): bool` | Check if a string is valid UTF-8 |

---

## Node Type Constants

| Constant | Value | Description |
|---|---|---|
| `HDOM_TYPE_ELEMENT` | 1 | HTML element |
| `HDOM_TYPE_COMMENT` | 2 | Comment node |
| `HDOM_TYPE_TEXT` | 3 | Text node |
| `HDOM_TYPE_ENDTAG` | 4 | End tag marker |
| `HDOM_TYPE_ROOT` | 5 | Root node |
| `HDOM_TYPE_UNKNOWN` | 6 | Unknown node |

The enum equivalents are `SimpleHtmlDom\NodeType::Element`, `::Comment`, `::Text`, `::EndTag`, `::Root`, `::Unknown`.

---

[← Selector Limitations](selector-limitations.md) | [Back to Manual](../README.md) | [Next: FAQ →](faq.md)
