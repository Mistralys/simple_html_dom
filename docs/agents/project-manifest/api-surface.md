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
public ?array $nodes = [];
public ?Node $parent = null;
public array $_ = [];              // Info array (HDOM_INFO_* keys)
public int $tag_start = 0;
```

### Property Hooks (PHP 8.4)

```php
public string $outertext { get; set; }   // Virtual: delegates to outertext()/sets HDOM_INFO_OUTER
public string $innertext { get; set; }   // Virtual: delegates to innertext()/sets HDOM_INFO_INNER or HDOM_INFO_TEXT
public ?array $children { get; }         // Virtual: filters nodes[] (lazy-cached, invalidated on mutation)
```

> **Caveat — `outertext`:** Setting `outertext` to an empty string (`''`) suppresses serialisation of that node (the node is omitted from `outertext()` / `save()` output), but does **not** remove the node from `Parser::$nodes` or from `find()` results. Treat this as a render-time suppression, not a DOM removal.

### Magic Read-Only Properties (via `__get`)

```php
/** @property-read string $plaintext  Plain text content (strips tags); delegates to Node::text() */
/** @property mixed $content          Alias for the node's raw text info (HDOM_INFO_TEXT) */
```

> **Note — `plaintext` / `text()`:** Inter-node whitespace between inline child nodes is preserved as-is; for indented HTML the result may contain unexpected leading/trailing padding. No trimming or normalisation is applied.

### Constructor

```php
public function __construct(public ?Parser $dom = null)
```

### Tree Navigation

```php
public function parent(): ?Node
public function has_child(): bool
public function children(int $idx = -1): Node|array|null
public function first_child(): ?Node
public function last_child(): ?Node
public function next_sibling(): ?Node
public function prev_sibling(): ?Node
public function find_ancestor_tag(string $tag): ?Node
```

> **Note — `children()`:** Returns non-text, non-end-tag child nodes. The method reads from the virtual `children` property, which is computed by filtering `nodes[]` to exclude `HDOM_TYPE_TEXT` and `HDOM_TYPE_ENDTAG` nodes. To access all child node types, iterate `$node->nodes` directly.

> **Note — `next_sibling()` / `prev_sibling()`:** Traverses non-text siblings only. Both methods scan the parent's virtual `children` property (which excludes text and end-tag nodes), so pure text nodes between siblings are not visited. To traverse all node types, iterate `$node->parent->nodes` directly.

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

> **Note — `find('*')`:** Using the universal selector returns only the direct children of this node, not all descendants. This differs from standard CSS Selectors Level 3 behaviour. See [constraints.md — CSS Selector Limitations](constraints.md#css-selector-limitations) for details and workarounds.

### DOM Manipulation

```php
public function append_child(Node $node): Node
public function invalidate_children_cache(): void
```

> **`append_child()`:** Detaches the node from its previous parent (if any), appends it to this node's `nodes[]` array (the virtual `children` property auto-updates), propagates the `$dom` (Parser) reference to the entire appended subtree, and rebuilds index positions so that `find()` can discover the appended nodes. The camelCase delegate `appendChild()` calls this method.

> **`invalidate_children_cache()`:** Resets the lazy cache backing the virtual `children` property. Internal mutation methods (`append_child()`, `detach_from_parent()`, `clear()`, `Parser::link_nodes()`) call this automatically. Consumer code only needs to call this if it mutates `$node->nodes` directly (e.g. `$node->nodes[] = $newNode`). The camelCase delegate `invalidateChildrenCache()` calls this method.

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
public function invalidateChildrenCache(): void
```

> **Note — `childNodes()`:** Delegates to `children()` and therefore returns non-text, non-end-tag child nodes via the virtual `children` property. Text nodes are not included. To access all child node types, iterate `$node->nodes` directly.

> **Note — `nextSibling()` / `previousSibling()`:** Delegate to `next_sibling()` / `prev_sibling()` respectively, and therefore traverse non-text siblings only (via the parent's virtual `children` property). Text nodes between siblings are skipped. To traverse all node types, iterate `$node->parent->nodes` directly.

> **Note — `appendChild()`:** Delegates to `append_child()`. See the DOM Manipulation section above for full behaviour details.

> **Note — `invalidateChildrenCache()`:** Delegates to `invalidate_children_cache()`. See the DOM Manipulation section above for details.

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

> **Note — `find('*')`:** Using the universal selector returns only the top-level elements (direct children of the root), not all elements in the document. This differs from standard CSS Selectors Level 3 behaviour. See [constraints.md — CSS Selector Limitations](constraints.md#css-selector-limitations) for details and workarounds.

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

## Legacy Constants Reference

All `HDOM_*` constants are defined as global `define()` calls in `src/simple_html_dom.php` that delegate to the corresponding backed enum `->value`. They are always available (loaded via Composer `files` autoload).

### Node Type Constants (`HDOM_TYPE_*`)

Map to `SimpleHtmlDom\NodeType` enum cases.

| Constant | Value | Enum Equivalent |
|---|---|---|
| `HDOM_TYPE_ELEMENT` | `1` | `NodeType::Element` |
| `HDOM_TYPE_COMMENT` | `2` | `NodeType::Comment` |
| `HDOM_TYPE_TEXT` | `3` | `NodeType::Text` |
| `HDOM_TYPE_ENDTAG` | `4` | `NodeType::EndTag` |
| `HDOM_TYPE_ROOT` | `5` | `NodeType::Root` |
| `HDOM_TYPE_UNKNOWN` | `6` | `NodeType::Unknown` |

### Quote Style Constants (`HDOM_QUOTE_*`)

Map to `SimpleHtmlDom\QuoteStyle` enum cases.

| Constant | Value | Enum Equivalent |
|---|---|---|
| `HDOM_QUOTE_DOUBLE` | `0` | `QuoteStyle::Double` |
| `HDOM_QUOTE_SINGLE` | `1` | `QuoteStyle::Single` |
| `HDOM_QUOTE_NO` | `3` | `QuoteStyle::None` |

### Node Info Constants (`HDOM_INFO_*`)

Map to `SimpleHtmlDom\NodeInfo` enum cases. Used as keys into the internal `$node->_[]` info array.

| Constant | Value | Enum Equivalent |
|---|---|---|
| `HDOM_INFO_BEGIN` | `0` | `NodeInfo::Begin` |
| `HDOM_INFO_END` | `1` | `NodeInfo::End` |
| `HDOM_INFO_QUOTE` | `2` | `NodeInfo::Quote` |
| `HDOM_INFO_SPACE` | `3` | `NodeInfo::Space` |
| `HDOM_INFO_TEXT` | `4` | `NodeInfo::Text` |
| `HDOM_INFO_INNER` | `5` | `NodeInfo::Inner` |
| `HDOM_INFO_OUTER` | `6` | `NodeInfo::Outer` |
| `HDOM_INFO_ENDSPACE` | `7` | `NodeInfo::EndSpace` |

### Miscellaneous Constants

| Constant | Value | Description |
|---|---|---|
| `DEFAULT_TARGET_CHARSET` | `'UTF-8'` | Default output charset for `str_get_html()` / `file_get_html()` |
| `DEFAULT_BR_TEXT` | `"\r\n"` | Text injected in place of `<br>` tags when rendering plain text |
| `DEFAULT_SPAN_TEXT` | `" "` | Text injected in place of `<span>` tags when rendering plain text |
| `MAX_FILE_SIZE` | `600000` | Default maximum file size in bytes; overridable via `Settings::setMaxFilesize()` |
