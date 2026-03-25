# Key Data Flows

## 1. Parse HTML from String

```
Consumer calls str_get_html($html)
  → Bridge creates new Parser(null, ...)
  → Bridge validates: non-empty, within max file size (Settings::getMaxFilesize())
  → On failure: stores Error in Settings('__error'), returns false
  → Parser::load($html)
      → prepare(): clear state, strip \r\n, create root Node
      → remove_noise(): regex-strip comments, CDATA, <script>, <style>, <code>, PHP/Smarty tags → stored in $this->noise[]
      → parse() loop: character-stream tokeniser walks the document
          → Text segments → new Node(HDOM_TYPE_TEXT) linked to parent
          → read_tag(): tokenises tag name, attributes, self-closing markers
              → parse_attr(): dispatches to parseDoubleQuotedAttr / parseSingleQuotedAttr / parseUnquotedAttr
              → link_nodes(): attaches Node to parent.nodes[] and parent.children[]
              → Handles optional closing tags, block tags, self-closing tags
      → parse_charset(): detects charset from <meta>, Content-Type, or mb_detect_encoding()
  → Returns Parser instance (acts as document root via ->root)
```

## 2. Parse HTML from URL/File

```
Consumer calls file_get_html($url)
  → Bridge fetches content via file_get_contents(), following 301 redirects (max 5 hops)
  → Checks HTTP response headers via http_get_last_response_headers() (PHP 8.4)
  → On non-200 / empty / oversized: stores Error in Settings('__error'), returns false
  → Delegates to Parser::load() (same as flow #1)
```

> **Redirect limit:** `file_get_html()` caps HTTP redirect following at 5 hops. This prevents infinite redirect loops while still handling normal multi-step redirects.

## 3. Find Elements by CSS Selector

```
Consumer calls $dom->find('div.item a', $idx)
  → Parser::find() delegates to $this->root->find()
  → Node::find() creates SelectorParser($this)
      → SelectorParser::parse_selector(): regex-parses selector string into groups
          → Each group = array of [tag, key, val, exp, no_key] tuples
      → For each selector group, for each level:
          → SelectorParser::seek(): walks node range [HDOM_INFO_BEGIN+1 .. HDOM_INFO_END)
              → Compares tag, attribute key, attribute value
              → SelectorParser::match(): applies operator (=, !=, ^=, $=, *=)
      → Merges results, sorts by document position
  → Returns Node[] (or single Node if $idx given, or null)
```

## 4. Read/Modify Node Content

```
Read outer HTML:
  $node->outertext  →  (property hook)  →  Node::outertext()
      → Fires callback if set
      → Returns cached HDOM_INFO_OUTER, or reconstructs: makeup() + inner nodes + end tag

Read inner HTML:
  $node->innertext  →  (property hook)  →  Node::innertext()
      → Returns HDOM_INFO_INNER if set, or concatenates children's outertext()

Read plain text:
  $node->plaintext  →  (__get magic)  →  Node::text()
      → Recursively collects text, skipping script/style/comment nodes

Write:
  $node->innertext = '...'  →  sets HDOM_INFO_INNER or HDOM_INFO_TEXT
  $node->outertext = '...'  →  sets HDOM_INFO_OUTER (replaces entire node on next render)
```

## 5. Save / Serialise

```
$dom->save($filepath)
  → Calls $this->root->innertext() to serialise the tree back to HTML
  → If $filepath given: writes to disk via file_put_contents(..., LOCK_EX)
  → Returns the HTML string

$dom->__toString()   // same as save()
$node->__toString()  // same as $node->outertext()
```

## 6. Charset Conversion

```
Node::outertext() / Node::text()
  → For each child node's text, calls Node::convert_text()
      → Delegates to TextConverter::convert($text, $source, $target)
          → If charsets differ and target is UTF-8: checks is_utf8() first
          → Uses iconv() for actual conversion
          → Strips UTF-8 BOM markers
```

## 7. Error Retrieval

```
Consumer calls simple_html_dom_get_error()
  → Returns Settings::get('__error')  →  Error|null
  → Error has getMessage(), getCode(), __toString()
```

## 8. Callback on Node Render

```
$dom->set_callback('my_func')
  → Stores callable in Parser::$callback
  → On each Node::outertext() call:
      → call_user_func_array($this->dom->callback, [$this])
      → Callback receives the Node, can modify it before rendering
```
