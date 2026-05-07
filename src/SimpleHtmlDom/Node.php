<?php

declare(strict_types=1);

namespace SimpleHtmlDom;

/**
 * A single DOM node in the parsed HTML tree.
 *
 * Replaces the legacy global class simple_html_dom_node.
 * The bridge file registers: class_alias(Node::class, 'simple_html_dom_node')
 *
 * PaperG - added ability for "find" routine to lowercase the value of the selector.
 * PaperG - added $tag_start to track the start position of the tag in the total byte index
 *
 * @property-read string $plaintext Plain-text content of the node (delegates to text()).
 * @property mixed $content  Dynamic HTML attribute — accessed via __get/__set.
 *
 * @package SimpleHtmlDom
 */
class Node
{
    public int $nodetype = HDOM_TYPE_TEXT;
    public string $tag = 'text';
    /** @var array<string, string|bool|null> */
    public array $attr = [];
    /** @var list<Node>|null */
    public ?array $nodes = [];

    /**
     * Lazy cache for the virtual children property.
     * Reset to null whenever nodes[] is mutated.
     * @var list<Node>|null
     */
    private ?array $_childrenCache = null;

    /**
     * Virtual property: element children derived from nodes[].
     *
     * Filters nodes[] to exclude text and end-tag nodes, returning only
     * elements, comments, and unknown-type nodes — the same set that was
     * previously maintained in a separate stored array.
     *
     * Uses a lazy-invalidation cache: the filter result is computed once
     * and reused until nodes[] is mutated.
     *
     * @var list<Node>|null
     */
    public ?array $children {
        get {
            if ($this->nodes === null) {
                return null;
            }
            if ($this->_childrenCache === null) {
                $this->_childrenCache = array_values(
                    array_filter($this->nodes, static fn(Node $n): bool =>
                        $n->nodetype !== HDOM_TYPE_TEXT && $n->nodetype !== HDOM_TYPE_ENDTAG
                    )
                );
            }
            return $this->_childrenCache;
        }
    }
    public ?Node $parent = null;
    /**
     * The "info" array — see HDOM_INFO_* for what each element contains.
     * @var array<int, mixed>
     */
    public array $_ = [];
    public int $tag_start = 0;

    /**
     * Virtual property: get/set the node's outer HTML (with tag).
     * Replacing the __get/__set switch cases for 'outertext'.
     */
    public string $outertext {
        get => $this->outertext();
        set(string $value) {
            $this->_[HDOM_INFO_OUTER] = $value;
        }
    }

    /**
     * Virtual property: get/set the node's inner HTML (without tag).
     * Replacing the __get/__set switch cases for 'innertext'.
     */
    public string $innertext {
        get => $this->innertext();
        set(string $value) {
            if (isset($this->_[HDOM_INFO_TEXT])) {
                $this->_[HDOM_INFO_TEXT] = $value;
            } else {
                $this->_[HDOM_INFO_INNER] = $value;
            }
        }
    }

    public function __construct(public ?Parser $dom = null)
    {
        if ($dom !== null) {
            $dom->nodes[] = $this;
        }
    }

    public function __destruct()
    {
        $this->clear();
    }

    public function __toString(): string
    {
        return $this->outertext();
    }

    /**
     * Clean up memory due to PHP circular references memory leak.
     */
    public function clear(): void
    {
        $this->dom = null;
        $this->nodes = null;
        $this->_childrenCache = null;
        $this->parent = null;
    }

    /**
     * Dump node's tree.
     */
    public function dump(bool $show_attr = true, int $deep = 0): void
    {
        $lead = str_repeat('    ', $deep);

        echo $lead . $this->tag;
        if ($show_attr && count($this->attr) > 0) {
            echo '(';
            foreach ($this->attr as $k => $v) {
                echo "[{$k}]=>\"" . $this->$k . '", ';
            }
            echo ')';
        }
        echo "\n";

        if ($this->nodes) {
            foreach ($this->nodes as $c) {
                $c->dump($show_attr, $deep + 1);
            }
        }
    }

    /**
     * Debugging function to dump a single dom node with a bunch of information about it.
     */
    public function dump_node(bool $echo = true): ?string
    {
        $string = $this->tag;
        if (count($this->attr) > 0) {
            $string .= '(';
            foreach ($this->attr as $k => $v) {
                $string .= "[{$k}]=>\"" . $this->$k . '", ';
            }
            $string .= ')';
        }
        if (count($this->_) > 0) {
            $string .= ' $_ (';
            foreach ($this->_ as $k => $v) {
                if (is_array($v)) {
                    $string .= "[{$k}]=>(";
                    foreach ($v as $k2 => $v2) {
                        if (is_array($v2)) {
                            $string .= "[{$k2}]=>[" . implode(', ', $v2) . "], ";
                        } else {
                            $string .= "[{$k2}]=>\"" . $v2 . '", ';
                        }
                    }
                    $string .= ")";
                } else {
                    $string .= "[{$k}]=>\"" . $v . '", ';
                }
            }
            $string .= ")";
        }

        $string .= " HDOM_INNER_INFO: '";
        if (isset($this->_[HDOM_INFO_INNER])) {
            $string .= $this->_[HDOM_INFO_INNER] . "'";
        } else {
            $string .= ' NULL ';
        }

        $string .= " nodes: " . count($this->nodes);
        $string .= " tag_start: " . $this->tag_start;
        $string .= "\n";

        if ($echo) {
            echo $string;
            return null;
        } else {
            return $string;
        }
    }

    /**
     * Returns the parent of node.
     */
    public function parent(): ?Node
    {
        return $this->parent;
    }

    /**
     * Append a child node to this node.
     *
     * Detaches the node from its previous parent (if any), attaches it to this
     * node's children[] and nodes[] arrays, propagates the $dom reference, and
     * rebuilds index positions so that find() can discover the appended subtree.
     */
    public function append_child(Node $node): Node
    {
        $node->detach_from_parent();

        $node->parent = $this;
        $this->nodes[] = $node;
        $this->_childrenCache = null;

        if ($this->dom !== null) {
            $node->reindex_subtree($this->dom);

            // Extend _[HDOM_INFO_END] up the ancestor chain so find() can reach the appended nodes.
            $newEnd = count($this->dom->nodes);
            $ancestor = $this;
            while ($ancestor !== null) {
                if (isset($ancestor->_[HDOM_INFO_END])) {
                    if ($ancestor->_[HDOM_INFO_END] < $newEnd) {
                        $ancestor->_[HDOM_INFO_END] = $newEnd;
                    }
                }
                $ancestor = $ancestor->parent;
            }
        }

        return $node;
    }

    /**
     * Detach this node from its current parent's nodes[] array.
     * The parent's children property is virtual and auto-updates.
     */
    private function detach_from_parent(): void
    {
        if ($this->parent === null) {
            return;
        }

        if ($this->parent->nodes !== null) {
            $this->parent->nodes = array_values(
                array_filter($this->parent->nodes, fn(Node $n): bool => $n !== $this)
            );
            $this->parent->_childrenCache = null;
        }

        $this->parent = null;
    }

    /**
     * Recursively re-link $dom and assign new index positions in Parser::$nodes.
     */
    private function reindex_subtree(Parser $dom): void
    {
        $this->dom = $dom;
        $this->_[HDOM_INFO_BEGIN] = count($dom->nodes);
        $dom->nodes[] = $this;

        if ($this->nodes !== null) {
            foreach ($this->nodes as $child) {
                $child->reindex_subtree($dom);
            }
        }

        $this->_[HDOM_INFO_END] = count($dom->nodes);
    }

    /**
     * Verify that node has children.
     */
    public function has_child(): bool
    {
        return !empty($this->children);
    }

    /**
     * Returns children of node.
     * @return list<Node>|Node|null
     */
    public function children(int $idx = -1): Node|array|null
    {
        $children = $this->children;
        if ($idx === -1) {
            return $children;
        }
        return $children[$idx] ?? null;
    }

    /**
     * Returns the first child of node.
     */
    public function first_child(): ?Node
    {
        if (count($this->children ?? []) > 0) {
            return $this->children[0];
        }
        return null;
    }

    /**
     * Returns the last child of node.
     */
    public function last_child(): ?Node
    {
        if (($count = count($this->children ?? [])) > 0) {
            return $this->children[$count - 1];
        }
        return null;
    }

    /**
     * Returns the next sibling of node.
     */
    public function next_sibling(): ?Node
    {
        if ($this->parent === null) {
            return null;
        }

        $siblings = $this->parent->children ?? [];
        $count = count($siblings);
        $idx = 0;
        while ($idx < $count && $this !== $siblings[$idx]) {
            ++$idx;
        }
        if (++$idx >= $count) {
            return null;
        }
        return $siblings[$idx];
    }

    /**
     * Returns the previous sibling of node.
     */
    public function prev_sibling(): ?Node
    {
        if ($this->parent === null) {
            return null;
        }
        $siblings = $this->parent->children ?? [];
        $count = count($siblings);
        $idx = 0;
        while ($idx < $count && $this !== $siblings[$idx]) {
            ++$idx;
        }
        if (--$idx < 0) {
            return null;
        }
        return $siblings[$idx];
    }

    /**
     * Function to locate a specific ancestor tag in the path to the root.
     */
    public function find_ancestor_tag(string $tag): ?Node
    {
        // Start by including ourselves in the comparison.
        $returnDom = $this;

        while (!is_null($returnDom)) {
            if ($returnDom->tag == $tag) {
                break;
            }
            $returnDom = $returnDom->parent;
        }
        return $returnDom;
    }

    /**
     * Get dom node's inner html.
     */
    public function innertext(): string
    {
        if (isset($this->_[HDOM_INFO_INNER])) {
            return $this->_[HDOM_INFO_INNER];
        }
        if (isset($this->_[HDOM_INFO_TEXT])) {
            if ($this->dom === null) {
                return $this->_[HDOM_INFO_TEXT];
            }
            return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
        }

        $ret = '';
        if ($this->nodes !== null) {
            foreach ($this->nodes as $n) {
                $ret .= $n->outertext();
            }
        }
        return $ret;
    }

    /**
     * Get dom node's outer text (with tag).
     */
    public function outertext(): string
    {
        if ($this->tag === 'root') {
            return $this->innertext();
        }

        // trigger callback
        if ($this->dom && $this->dom->callback !== null) {
            call_user_func_array($this->dom->callback, [$this]);
        }

        if (isset($this->_[HDOM_INFO_OUTER])) {
            return $this->_[HDOM_INFO_OUTER];
        }
        if (isset($this->_[HDOM_INFO_TEXT])) {
            if ($this->dom === null) {
                return $this->_[HDOM_INFO_TEXT];
            }
            return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
        }

        // render begin tag
        $ret = $this->makeup();

        // render inner text
        if (isset($this->_[HDOM_INFO_INNER])) {
            // If it's a br tag... don't return the HDOM_INNER_INFO that we may or may not have added.
            if ($this->tag != "br") {
                $ret .= $this->_[HDOM_INFO_INNER];
            }
        } else {
            if ($this->nodes) {
                foreach ($this->nodes as $n) {
                    $ret .= $this->convert_text($n->outertext());
                }
            }
        }

        // render end tag
        if (isset($this->_[HDOM_INFO_END]) && $this->_[HDOM_INFO_END] != 0) {
            $ret .= '</' . $this->tag . '>';
        }
        return $ret;
    }

    /**
     * Get dom node's plain text.
     */
    public function text(): string
    {
        if (isset($this->_[HDOM_INFO_INNER])) {
            return $this->_[HDOM_INFO_INNER];
        }
        switch ($this->nodetype) {
            case HDOM_TYPE_TEXT:
                if ($this->dom === null) {
                    return $this->_[HDOM_INFO_TEXT];
                }
                return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
            case HDOM_TYPE_COMMENT: return '';
            case HDOM_TYPE_UNKNOWN: return '';
        }
        if (strcasecmp($this->tag, 'script') === 0) {
            return '';
        }
        if (strcasecmp($this->tag, 'style') === 0) {
            return '';
        }

        $ret = '';
        // In rare cases, (always node type 1 or HDOM_TYPE_ELEMENT - observed for some span tags, and some p tags) $this->nodes is set to NULL.
        if (!is_null($this->nodes)) {
            foreach ($this->nodes as $n) {
                $ret .= $this->convert_text($n->text());
            }

            // If this node is a span... add a space at the end of it so multiple spans don't run into each other.
            if ($this->tag == "span" && $this->dom !== null) {
                $ret .= $this->dom->default_span_text;
            }
        }
        return $ret;
    }

    public function xmltext(): string
    {
        $ret = $this->innertext();
        $ret = str_ireplace('<![CDATA[', '', $ret);
        $ret = str_replace(']]>', '', $ret);
        return $ret;
    }

    /**
     * Build node's text with tag.
     */
    public function makeup(): string
    {
        // text, comment, unknown
        if (isset($this->_[HDOM_INFO_TEXT])) {
            if ($this->dom === null) {
                return $this->_[HDOM_INFO_TEXT];
            }
            return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
        }

        $ret = '<' . $this->tag;
        $i   = -1;

        foreach ($this->attr as $key => $val) {
            ++$i;

            // skip removed attribute
            if ($val === null || $val === false) {
                continue;
            }

            $ret .= $this->_[HDOM_INFO_SPACE][$i][0];
            // no value attr: nowrap, checked, selected...
            if ($val === true) {
                $ret .= $key;
            } else {
                switch ($this->_[HDOM_INFO_QUOTE][$i]) {
                    case HDOM_QUOTE_DOUBLE: $quote = '"'; break;
                    case HDOM_QUOTE_SINGLE: $quote = "'"; break;
                    default: $quote = '';
                }
                $ret .= $key . $this->_[HDOM_INFO_SPACE][$i][1] . '=' . $this->_[HDOM_INFO_SPACE][$i][2] . $quote . $val . $quote;
            }
        }
        
        if ($this->dom === null) {
            return $ret . $this->_[HDOM_INFO_ENDSPACE] . '>';
        }
        $ret = $this->dom->restore_noise($ret);
        return $ret . $this->_[HDOM_INFO_ENDSPACE] . '>';
    }

    /**
     * Find elements by CSS selector.
     * PaperG - added ability for find to lowercase the value of the selector.
     * @return list<Node>|Node|null
     */
    public function find(string $selector, ?int $idx = null, bool $lowercase = false): Node|array|null
    {
        $selectorParser = new SelectorParser($this);
        $selectors = $selectorParser->parse_selector($selector);
        if (($count = count($selectors)) === 0) {
            return [];
        }
        /** @var array<int, int> */
        $found_keys = [];

        // find each selector
        for ($c = 0; $c < $count; ++$c) {
            if (($levle = count($selectors[$c])) === 0) {
                return [];
            }
            if (!isset($this->_[HDOM_INFO_BEGIN])) {
                return [];
            }

            /** @var array<int, int> */
            $head = [$this->_[HDOM_INFO_BEGIN] => 1];

            // handle descendant selectors, no recursive!
            for ($l = 0; $l < $levle; ++$l) {
                /** @var array<int, int> */
                $ret = [];
                foreach ($head as $k => $v) {
                    $n = ($k === -1) ? $this->dom->root : $this->dom->nodes[$k];
                    // PaperG - Pass this optional parameter on to the seek function.
                    $n->seek($selectors[$c][$l], $ret, $lowercase);
                }
                $head = $ret;
            }

            foreach ($head as $k => $v) {
                if (!isset($found_keys[$k])) {
                    $found_keys[$k] = 1;
                }
            }
        }

        // sort keys
        ksort($found_keys);

        $found = [];
        foreach ($found_keys as $k => $v) {
            $found[] = $this->dom->nodes[$k];
        }

        // return nth-element or array
        if (is_null($idx)) {
            return $found;
        } elseif ($idx < 0) {
            $idx = count($found) + $idx;
        }
        return (isset($found[$idx])) ? $found[$idx] : null;
    }

    /**
     * Seek for given conditions.
     *
     * @param list<mixed> $selector
     * @param array<int, int> &$ret
     * PaperG - added parameter to allow for case insensitive testing of the value of a selector.
     */
    protected function seek(array $selector, array &$ret, bool $lowercase = false, ?SelectorParser $parser = null): void
    {
        $parser ??= new SelectorParser($this);
        $parser->seek($selector, $ret, $lowercase);
    }

    protected function match(string $exp, mixed $pattern, mixed $value, ?SelectorParser $parser = null): bool
    {
        $parser ??= new SelectorParser($this);
        return $parser->match($exp, $pattern, $value);
    }

    /**
     * @return array<int, array<int, array{0: string, 1: string|null, 2: string|null, 3: string, 4: bool}>>
     */
    protected function parse_selector(string $selector_string, ?SelectorParser $parser = null): array
    {
        $parser ??= new SelectorParser($this);
        return $parser->parse_selector($selector_string);
    }

    public function __get(string $name): mixed
    {
        if (isset($this->attr[$name])) {
            $val = $this->attr[$name];
            if (is_bool($val)) {
                return $val;
            }
            return $this->convert_text($val);
        }
        switch ($name) {
            case 'plaintext': return $this->text();
            case 'xmltext':   return $this->xmltext();
            default:          return array_key_exists($name, $this->attr);
        }
    }

    public function __set(string $name, mixed $value): void
    {
        if (!isset($this->attr[$name])) {
            $this->_[HDOM_INFO_SPACE][] = [' ', '', ''];
            $this->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
        }
        $this->attr[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        switch ($name) {
            case 'outertext': return true;
            case 'innertext': return true;
            case 'plaintext': return true;
        }
        // no value attr: nowrap, checked selected...
        return (array_key_exists($name, $this->attr)) ? true : isset($this->attr[$name]);
    }

    public function __unset(string $name): void
    {
        if (isset($this->attr[$name])) {
            unset($this->attr[$name]);
        }
    }

    /**
     * PaperG - Function to convert the text from one character set to another if the two sets are not the same.
     */
    public function convert_text(string $text): string
    {
        $sourceCharset = "";
        $targetCharset = "";

        if ($this->dom) {
            $sourceCharset = strtoupper($this->dom->_charset);
            $targetCharset = strtoupper($this->dom->_target_charset);
        }

        return TextConverter::convert($text, $sourceCharset, $targetCharset);
    }

    /**
     * Returns true if $string is valid UTF-8 and false otherwise.
     *
     * @param mixed $str String to be tested
     */
    public static function is_utf8(mixed $str): bool
    {
        return TextConverter::is_utf8($str);
    }

    /**
     * Function to try a few tricks to determine the displayed size of an img on the page.
     * NOTE: This will ONLY work on an IMG tag. Returns FALSE on all other tag types.
     *
     * @author John Schlick
     * @return array{height: int, width: int}|false An array containing 'height' and 'width' or false for non-img tags.
     */
    public function get_display_size(): array|false
    {
        $width  = -1;
        $height = -1;

        if ($this->tag !== 'img') {
            return false;
        }

        // See if there is a height or width attribute in the tag itself.
        if (isset($this->attr['width'])) {
            $width = $this->attr['width'];
        }

        if (isset($this->attr['height'])) {
            $height = $this->attr['height'];
        }

        // Now look for an inline style.
        if (isset($this->attr['style'])) {
            $attributes = [];
            preg_match_all("/([\w\-]+)\s*:\s*([^;]+)\s*;?/", $this->attr['style'], $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }

            if (isset($attributes['width']) && $width == -1) {
                if (strtolower(substr($attributes['width'], -2)) == 'px') {
                    $proposed_width = substr($attributes['width'], 0, -2);
                    if (filter_var($proposed_width, FILTER_VALIDATE_INT)) {
                        $width = $proposed_width;
                    }
                }
            }

            if (isset($attributes['height']) && $height == -1) {
                if (strtolower(substr($attributes['height'], -2)) == 'px') {
                    $proposed_height = substr($attributes['height'], 0, -2);
                    if (filter_var($proposed_height, FILTER_VALIDATE_INT)) {
                        $height = $proposed_height;
                    }
                }
            }
        }

        return ['height' => $height, 'width' => $width];
    }

    // camelCase DOM API delegates
    
    /**
     * @return array<string, string|bool|null>
     */
    public function getAllAttributes(): array           { return $this->attr; }
    public function getAttribute(string $name): mixed  { return $this->__get($name); }
    public function setAttribute(string $name, mixed $value): void { $this->__set($name, $value); }
    public function hasAttribute(string $name): bool   { return $this->__isset($name); }
    public function removeAttribute(string $name): void { $this->__unset($name); }
    public function getElementById(string $id): ?Node  { return $this->find("#$id", 0); }
    
    /**
     * @return list<Node>|Node|null
     */
    public function getElementsById(string $id, ?int $idx = null): Node|array|null { return $this->find("#$id", $idx); }
    public function getElementByTagName(string $name): ?Node { return $this->find($name, 0); }
    
    /**
     * @return list<Node>|Node|null
     */
    public function getElementsByTagName(string $name, ?int $idx = null): Node|array|null { return $this->find($name, $idx); }
    public function parentNode(): ?Node                { return $this->parent(); }
    
    /**
     * @return list<Node>|Node|null
     */
    public function childNodes(int $idx = -1): Node|array|null { return $this->children($idx); }
    public function firstChild(): ?Node                { return $this->first_child(); }
    public function lastChild(): ?Node                 { return $this->last_child(); }
    public function nextSibling(): ?Node               { return $this->next_sibling(); }
    public function previousSibling(): ?Node           { return $this->prev_sibling(); }
    public function hasChildNodes(): bool              { return $this->has_child(); }
    public function nodeName(): string                 { return $this->tag; }
    public function appendChild(Node $node): Node      { return $this->append_child($node); }

    /**
     * Invalidate the lazy children cache.
     *
     * Must be called after externally mutating this node's nodes[] array.
     * Internal Node methods handle this automatically.
     */
    public function invalidate_children_cache(): void
    {
        $this->_childrenCache = null;
    }

    public function invalidateChildrenCache(): void    { $this->invalidate_children_cache(); }
}
