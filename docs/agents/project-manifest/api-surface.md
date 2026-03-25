# Public API Surface

## Enums

### `SimpleHtmlDom\NodeType` (backed: int)

| Case | Value |
|---|---|
| `Element` | 1 |
| `Comment` | 2 |
| `Text` | 3 |
| `EndTag` | 4 |
| `Root` | 5 |
| `Unknown` | 6 |

### `SimpleHtmlDom\QuoteStyle` (backed: int)

| Case | Value |
|---|---|
| `Double` | 0 |
| `Single` | 1 |
| `None` | 3 |

### `SimpleHtmlDom\NodeInfo` (backed: int)

| Case | Value |
|---|---|
| `Begin` | 0 |
| `End` | 1 |
| `Quote` | 2 |
| `Space` | 3 |
| `Text` | 4 |
| `Inner` | 5 |
| `Outer` | 6 |
| `EndSpace` | 7 |

---

## `SimpleHtmlDom\Error`

Immutable value object for parse errors.

### Constructor

```php
public function __construct(private readonly string $message, private readonly int $code)
```

### Public Methods

```php
public function getMessage(): string
public function getCode(): int
public function __toString(): string   // "[{code}] {message}"
```

---

## `SimpleHtmlDom\Settings`

Static key/value store for library-global configuration.

### Public Static Methods

```php
public static function setMaxFilesize(int $bytes): void
public static function getMaxFilesize(): int
public static function set(string $name, mixed $value): void
public static function get(string $name, mixed $default = null): mixed
public static function reset(): void
```

---

## `SimpleHtmlDom\TextConverter`

Stateless charset-conversion helper. All methods are static.

### Public Static Methods

```php
public static function convert(string $text, string $sourceCharset, string $targetCharset): string
public static function is_utf8(mixed $str): bool
```

---

## `SimpleHtmlDom\SelectorParser`

CSS selector parsing, seeking, and matching. Instantiated with a context `Node`.

### Constructor

```php
public function __construct(private readonly Node $node)
```

### Public Methods

```php
public function parse_selector(string $selectorString): array
public function seek(array $selector, array &$ret, bool $lowercase = false): void
public function match(string $exp, mixed $pattern, mixed $value): bool
```

---

## `SimpleHtmlDom\Node`

A single node in the parsed HTML tree.

Legacy alias: `simple_html_dom_node`

### Public Properties

```php
public int $nodetype = HDOM_TYPE_TEXT;
public string $tag = 'text';
public array $attr = [];
public ?array $children = [];
public ?array $nodes = [];
public ?Node $parent = null;
public array $_ = [];              // Info array (HDOM_INFO_* keys)
public int $tag_start = 0;
```

### Property Hooks (PHP 8.4)

```php
public string $outertext { get; set; }   // Virtual: delegates to outertext()/sets HDOM_INFO_OUTER
public string $innertext { get; set; }   // Virtual: delegates to innertext()/sets HDOM_INFO_INNER or HDOM_INFO_TEXT
```

### Magic Read-Only Properties (via `__get`)

```php
/** @property-read string $plaintext  Plain text content (strips tags); delegates to Node::text() */
/** @property mixed $content          Alias for the node's raw text info (HDOM_INFO_TEXT) */
```

### Constructor

```php
public function __construct(public ?Parser $dom = null)
```

### Tree Navigation

```php
public function parent(?Node $parent = null): ?Node
public function has_child(): bool
public function children(int $idx = -1): Node|array|null
public function first_child(): ?Node
public function last_child(): ?Node
public function next_sibling(): ?Node
public function prev_sibling(): ?Node
public function find_ancestor_tag(string $tag): ?Node
```

### Content Access

```php
public function innertext(): string
public function outertext(): string
public function text(): string
public function xmltext(): string
public function makeup(): string
```

### Search

```php
public function find(string $selector, ?int $idx = null, bool $lowercase = false): Node|array|null
```

### Debug / Dump

```php
public function dump(bool $show_attr = true, int $deep = 0): void
public function dump_node(bool $echo = true): ?string
```

### Attribute Access

```php
public function __get(string $name): mixed
public function __set(string $name, mixed $value): void
public function __isset(string $name): bool
public function __unset(string $name): void
```

### Conversion Helpers

```php
public function convert_text(string $text): string
public static function is_utf8(mixed $str): bool
public function get_display_size(): array|false        // IMG tags only
```

### camelCase DOM-API Delegates

```php
public function getAllAttributes(): array
public function getAttribute(string $name): mixed
public function setAttribute(string $name, mixed $value): void
public function hasAttribute(string $name): bool
public function removeAttribute(string $name): void
public function getElementById(string $id): ?Node
public function getElementsById(string $id, ?int $idx = null): Node|array|null
public function getElementByTagName(string $name): ?Node
public function getElementsByTagName(string $name, ?int $idx = null): Node|array|null
public function parentNode(): ?Node
public function childNodes(int $idx = -1): Node|array|null
public function firstChild(): ?Node
public function lastChild(): ?Node
public function nextSibling(): ?Node
public function previousSibling(): ?Node
public function hasChildNodes(): bool
public function nodeName(): string
public function appendChild(Node $node): Node
```

---

## `SimpleHtmlDom\Parser`

HTML tokeniser and document root. This is the main entry-point class.

Legacy alias: `simple_html_dom`

### Public Properties

```php
public mixed $callback = null;         // callable|null
public ?Node $root = null;
public array $nodes = [];
public bool $lowercase = false;
public int $original_size = 0;
public int $size = 0;
public string $_charset = '';
public string $_target_charset = '';
public string $default_span_text = '';
```

### Constructor

```php
public function __construct(
    ?string $str = null,
    bool $lowercase = true,
    bool $forceTagsClosed = true,
    string $target_charset = DEFAULT_TARGET_CHARSET,
    bool $stripRN = true,
    string $defaultBRText = DEFAULT_BR_TEXT,
    string $defaultSpanText = DEFAULT_SPAN_TEXT
)
```

### Loading

```php
public function load(string|null $str, bool $lowercase = true, bool $stripRN = true, string $defaultBRText = DEFAULT_BR_TEXT, string $defaultSpanText = DEFAULT_SPAN_TEXT): static
public function load_file(string ...$args): void
public function loadFile(string ...$args): void   // camelCase delegate
```

### Searching

```php
public function find(string $selector, ?int $idx = null, bool $lowercase = false): Node|array|null
```

### Output

```php
public function save(string $filepath = ''): string
public function __toString(): string
```

### Callbacks

```php
public function set_callback(mixed $function_name): void
public function remove_callback(): void
```

### Lifecycle

```php
public function clear(): void
public function dump(bool $show_attr = true): void
```

### Noise Handling

```php
public function restore_noise(string $text): string
public function search_noise(string $text): ?string
```

### Magic Properties (via `__get`)

```php
->outertext   // string — root inner text
->innertext   // string — root inner text
->plaintext   // string — root plain text
->charset     // string — detected charset
->target_charset // string — target charset
```

### camelCase DOM-API Delegates

```php
public function childNodes(int $idx = -1): Node|array|null
public function firstChild(): ?Node
public function lastChild(): ?Node
public function createElement(string $name, mixed $value = null): Node|false
public function createTextNode(string $value): Node|false
public function getElementById(string $id): ?Node
public function getElementsById(string $id, ?int $idx = null): Node|array|null
public function getElementByTagName(string $name): ?Node
public function getElementsByTagName(string $name, int $idx = -1): Node|array|null
```

---

## Procedural Functions (Bridge File)

These global functions are defined in `src/simple_html_dom.php` and delegate to the namespaced classes.

```php
function file_get_html(
    string $url,
    bool $use_include_path = false,
    mixed $context = null,
    int $offset = -1,
    int $maxLen = -1,
    bool $lowercase = true,
    bool $forceTagsClosed = true,
    string $target_charset = DEFAULT_TARGET_CHARSET,
    bool $stripRN = true,
    string $defaultBRText = DEFAULT_BR_TEXT,
    string $defaultSpanText = DEFAULT_SPAN_TEXT
): \SimpleHtmlDom\Parser|false

function str_get_html(
    string $str,
    bool $lowercase = true,
    bool $forceTagsClosed = true,
    string $target_charset = DEFAULT_TARGET_CHARSET,
    bool $stripRN = true,
    string $defaultBRText = DEFAULT_BR_TEXT,
    string $defaultSpanText = DEFAULT_SPAN_TEXT
): \SimpleHtmlDom\Parser|false

function simple_html_dom_get_error(): \SimpleHtmlDom\Error|null

function dump_html_tree(\SimpleHtmlDom\Node $node, bool $show_attr = true, int $deep = 0): void
```

---

## Legacy Class Aliases

| Legacy Name | Maps To |
|---|---|
| `simple_html_dom` | `SimpleHtmlDom\Parser` |
| `simple_html_dom_node` | `SimpleHtmlDom\Node` |
| `simple_html_dom_settings` | `SimpleHtmlDom\Settings` |
| `simple_html_dom_error` | `SimpleHtmlDom\Error` |

## Legacy Constants

All `HDOM_*` constants are defined as global `define()` calls that point to the corresponding enum `->value`.
