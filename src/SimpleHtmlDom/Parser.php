<?php

declare(strict_types=1);

namespace SimpleHtmlDom;

/**
 * Simple HTML DOM parser / document root.
 *
 * Replaces the legacy global class simple_html_dom.
 * The bridge file registers: class_alias(Parser::class, 'simple_html_dom')
 *
 * Paperg - in the find routine: allow us to specify that we want case insensitive testing of the value of the selector.
 * Paperg - change $size from protected to public so we can easily access it
 * Paperg - added ForceTagsClosed in the constructor which tells us whether we trust the html or not. Default is to NOT trust it.
 *
 * @package SimpleHtmlDom
 */
class Parser
{
    // Token character sets used by the parser's character-stream methods.
    private const string TOKEN_BLANK = " \t\r\n";
    private const string TOKEN_EQUAL = ' =/>';
    private const string TOKEN_SLASH = " />\r\n\t";
    private const string TOKEN_ATTR  = ' >';

    // Self-closing HTML tags (use isset() for ~30% faster lookup than in_array).
    private const array SELF_CLOSING_TAGS = [
        'img' => 1, 'br' => 1, 'input' => 1, 'meta' => 1,
        'link' => 1, 'hr' => 1, 'base' => 1, 'embed' => 1, 'spacer' => 1,
    ];

    // Block-level tags used during end-tag handling.
    private const array BLOCK_TAGS = [
        'root' => 1, 'body' => 1, 'form' => 1,
        'div' => 1, 'span' => 1, 'table' => 1,
    ];

    // Known sourceforge issue #2977341
    // B tags that are not closed cause us to return everything to the end of the document.
    private const array OPTIONAL_CLOSING_TAGS = [
        'tr'    => ['tr' => 1, 'td' => 1, 'th' => 1],
        'th'    => ['th' => 1],
        'td'    => ['td' => 1],
        'li'    => ['li' => 1],
        'dt'    => ['dt' => 1, 'dd' => 1],
        'dd'    => ['dd' => 1, 'dt' => 1],
        'dl'    => ['dd' => 1, 'dt' => 1],
        'p'     => ['p' => 1],
        'nobr'  => ['nobr' => 1],
        'b'     => ['b' => 1],
        'option'=> ['option' => 1],
    ];

    /** @var callable|null */
    public mixed $callback = null;
    public ?Node $root = null;
    /** @var list<Node> */
    public array $nodes = [];
    /** @var array<string, array<string, int>>|null */
    private ?array $optionalClosingArray = null;
    public bool $lowercase = false;
    // Used to keep track of how large the text was when we started.
    public int $original_size = 0;
    public int $size = 0;
    protected int $pos = 0;
    protected string $doc = '';
    protected ?string $char = null;
    protected int $cursor = 0;
    protected ?Node $parent = null;
    /** @var array<string, string> */
    protected array $noise = [];
    // Note that this is referenced by a child node, and so it needs to be public for that node to see this information.
    public string $_charset = '';
    public string $_target_charset = '';
    protected string $default_br_text = "";
    public string $default_span_text = "";

    public function __construct(
        ?string $str = null,
        bool $lowercase = true,
        bool $forceTagsClosed = true,
        string $target_charset = DEFAULT_TARGET_CHARSET,
        bool $stripRN = true,
        string $defaultBRText = DEFAULT_BR_TEXT,
        string $defaultSpanText = DEFAULT_SPAN_TEXT
    ) {
        if ($str) {
            if (preg_match("/^http:\/\//i", $str) || is_file($str)) {
                $this->load_file($str);
            } else {
                $this->load($str, $lowercase, $stripRN, $defaultBRText, $defaultSpanText);
            }
        }
        // Forcing tags to be closed implies that we don't trust the html, but it can lead to parsing errors if we SHOULD trust the html.
        if (!$forceTagsClosed) {
            $this->optionalClosingArray = [];
        }
        $this->_target_charset = $target_charset;
    }

    public function __destruct()
    {
        $this->clear();
    }

    /**
     * Load html from string.
     */
    public function load(string|null $str, bool $lowercase = true, bool $stripRN = true, string $defaultBRText = DEFAULT_BR_TEXT, string $defaultSpanText = DEFAULT_SPAN_TEXT): static
    {
        $str ??= '';
        // prepare
        $this->prepare($str, $lowercase, $stripRN, $defaultBRText, $defaultSpanText);
        // strip out comments
        $this->remove_noise("'<!--(.*?)-->'is");
        // strip out cdata
        $this->remove_noise("'<!\[CDATA\[(.*?)\]\]>'is", true);
        // Per sourceforge http://sourceforge.net/tracker/?func=detail&aid=2949097&group_id=218559&atid=1044037
        // Script tags removal now preceeds style tag removal.
        // strip out <script> tags
        $this->remove_noise("'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'is");
        $this->remove_noise("'<\s*script\s*>(.*?)<\s*/\s*script\s*>'is");
        // strip out <style> tags
        $this->remove_noise("'<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>'is");
        $this->remove_noise("'<\s*style\s*>(.*?)<\s*/\s*style\s*>'is");
        // strip out preformatted tags
        $this->remove_noise("'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is");
        // strip out server side scripts
        $this->remove_noise("'(<\?)(.*?)(\?>)'s", true);
        // strip smarty scripts
        $this->remove_noise("'(\{\w)(.*?)(\})'s", true);

        // parsing
        while ($this->parse());
        // end
        $this->root->_[HDOM_INFO_END] = $this->cursor;
        $this->parse_charset();

        // make load function chainable
        return $this;
    }

    /**
     * Load html from file.
     */
    public function load_file(string ...$args): void
    {
        $this->load((string) file_get_contents(...$args), true);
        // Throw an error if we can't properly load the dom.
        if (($error = error_get_last()) !== null) {
            $this->clear();
        }
    }

    /**
     * Set callback function.
     */
    public function set_callback(mixed $function_name): void
    {
        $this->callback = $function_name;
    }

    /**
     * Remove callback function.
     */
    public function remove_callback(): void
    {
        $this->callback = null;
    }

    /**
     * Save dom as string.
     */
    public function save(string $filepath = ''): string
    {
        $ret = $this->root->innertext();
        if ($filepath !== '') {
            file_put_contents($filepath, $ret, LOCK_EX);
        }
        return $ret;
    }

    /**
     * Find dom node by css selector.
     * Paperg - allow us to specify that we want case insensitive testing of the value of the selector.
     * @return list<Node>|Node|null
     */
    public function find(string $selector, ?int $idx = null, bool $lowercase = false): Node|array|null
    {
        return $this->root->find($selector, $idx, $lowercase);
    }

    /**
     * Clean up memory due to PHP circular references memory leak.
     */
    public function clear(): void
    {
        foreach ($this->nodes as $n) {
            $n->clear();
            $n = null;
        }
        if (isset($this->parent)) {
            $this->parent->clear();
            $this->parent = null;
        }
        if (isset($this->root)) {
            $this->root->clear();
            $this->root = null;
        }
        $this->doc = '';
        $this->noise = [];
    }

    public function dump(bool $show_attr = true): void
    {
        $this->root->dump($show_attr);
    }

    /**
     * Prepare HTML data and init everything.
     */
    protected function prepare(string $str, bool $lowercase = true, bool $stripRN = true, string $defaultBRText = DEFAULT_BR_TEXT, string $defaultSpanText = DEFAULT_SPAN_TEXT): void
    {
        $this->clear();

        // set the length of content before we do anything to it.
        $this->size = strlen($str);
        // Save the original size of the html that we got in. It might be useful to someone.
        $this->original_size = $this->size;

        // before we save the string as the doc... strip out the \r \n's if we are told to.
        if ($stripRN) {
            $str = str_replace("\r", " ", $str);
            $str = str_replace("\n", " ", $str);

            // set the length of content since we have changed it.
            $this->size = strlen($str);
        }

        $this->doc               = $str;
        $this->pos               = 0;
        $this->cursor            = 1;
        $this->noise             = [];
        $this->nodes             = [];
        $this->lowercase         = $lowercase;
        $this->default_br_text   = $defaultBRText;
        $this->default_span_text = $defaultSpanText;
        $this->root              = new Node($this);
        $this->root->tag         = 'root';
        $this->root->_[HDOM_INFO_BEGIN] = -1;
        $this->root->nodetype   = HDOM_TYPE_ROOT;
        $this->parent           = $this->root;
        if ($this->size > 0) {
            $this->char = $this->doc[0];
        }
    }

    /**
     * Parse html content.
     */
    protected function parse(): bool
    {
        if (($s = $this->copy_until_char('<')) === '') {
            return $this->read_tag();
        }

        // text
        $node = new Node($this);
        ++$this->cursor;
        $node->_[HDOM_INFO_TEXT] = $s;
        $this->link_nodes($node);
        return true;
    }

    /**
     * PAPERG - dkchou - added this to try to identify the character set of the page we have just parsed so we know better how to spit it out later.
     * NOTE: IF you provide a routine called get_last_retrieve_url_contents_content_type which returns the CURLINFO_CONTENT_TYPE from the last curl_exec
     * (or the content_type header from the last transfer), we will parse THAT, and if a charset is specified, we will use it over any other mechanism.
     */
    protected function parse_charset(): string
    {
        $charset = null;

        if (function_exists('get_last_retrieve_url_contents_content_type')) {
            $contentTypeHeader = get_last_retrieve_url_contents_content_type();
            $success = preg_match('/charset=(.+)/', $contentTypeHeader, $matches);
            if ($success) {
                $charset = $matches[1];
            }
        }

        if (empty($charset)) {
            $el = $this->root->find('meta[http-equiv=Content-Type]', 0);
            if ($el instanceof Node) {
                $fullvalue = $el->content;

                if (!empty($fullvalue)) {
                    $success = preg_match('/charset=(.+)/', $fullvalue, $matches);
                    if ($success) {
                        $charset = $matches[1];
                    } else {
                        // If there is a meta tag, and they don't specify the character set, research says that it's typically ISO-8859-1
                        $charset = 'ISO-8859-1';
                    }
                }
            }
        }

        // If we couldn't find a charset above, then lets try to detect one based on the text we got...
        if (empty($charset)) {
            // Have php try to detect the encoding from the text given to us.
            $charset = mb_detect_encoding($this->root->plaintext . "ascii", ["UTF-8", "CP1252"]);

            // and if this doesn't work... then we need to just wrongheadedly assume it's UTF-8 so that we can move on
            if ($charset === false) {
                $charset = 'UTF-8';
            }
        }

        // Since CP1252 is a superset, if we get one of it's subsets, we want it instead.
        if (
            (strtolower($charset) == strtolower('ISO-8859-1')) ||
            (strtolower($charset) == strtolower('Latin1')) ||
            (strtolower($charset) == strtolower('Latin-1'))
        ) {
            $charset = 'CP1252';
        }

        return $this->_charset = $charset;
    }

    /**
     * Read tag info.
     */
    protected function read_tag(): bool
    {
        if ($this->char !== '<') {
            $this->root->_[HDOM_INFO_END] = $this->cursor;
            return false;
        }
        $begin_tag_pos = $this->pos;
        $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next

        // end tag
        if ($this->char === '/') {
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
            // This represents the change in the simple_html_dom trunk from revision 180 to 181.
            $this->skip(self::TOKEN_BLANK);
            $tag = $this->copy_until_char('>');

            // skip attributes in end tag
            if (($pos = strpos($tag, ' ')) !== false) {
                $tag = substr($tag, 0, $pos);
            }

            $parent_lower = strtolower($this->parent->tag);
            $tag_lower    = strtolower($tag);

            if ($parent_lower !== $tag_lower) {
                $optionalClosingTags = $this->optionalClosingArray ?? self::OPTIONAL_CLOSING_TAGS;
                if (isset($optionalClosingTags[$parent_lower]) && isset(self::BLOCK_TAGS[$tag_lower])) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $org_parent = $this->parent;

                    while (($this->parent->parent) && strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $this->parent->parent;
                    }

                    if (strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $org_parent; // restore original parent
                        if ($this->parent->parent) {
                            $this->parent = $this->parent->parent;
                        }
                        $this->parent->_[HDOM_INFO_END] = $this->cursor;
                        return $this->as_text_node($tag);
                    }
                } elseif (($this->parent->parent) && isset(self::BLOCK_TAGS[$tag_lower])) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $org_parent = $this->parent;

                    while (($this->parent->parent) && strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $this->parent->parent;
                    }

                    if (strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $org_parent; // restore original parent
                        $this->parent->_[HDOM_INFO_END] = $this->cursor;
                        return $this->as_text_node($tag);
                    }
                } elseif (($this->parent->parent) && strtolower($this->parent->parent->tag) === $tag_lower) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $this->parent = $this->parent->parent;
                } else {
                    return $this->as_text_node($tag);
                }
            }

            $this->parent->_[HDOM_INFO_END] = $this->cursor;
            if ($this->parent->parent) {
                $this->parent = $this->parent->parent;
            }

            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        $node                    = new Node($this);
        $node->_[HDOM_INFO_BEGIN] = $this->cursor;
        ++$this->cursor;
        $tag              = $this->copy_until(self::TOKEN_SLASH);
        $node->tag_start  = $begin_tag_pos;

        // doctype, cdata & comments...
        if (isset($tag[0]) && $tag[0] === '!') {
            $node->_[HDOM_INFO_TEXT] = '<' . $tag . $this->copy_until_char('>');

            if (isset($tag[2]) && $tag[1] === '-' && $tag[2] === '-') {
                $node->nodetype = HDOM_TYPE_COMMENT;
                $node->tag      = 'comment';
            } else {
                $node->nodetype = HDOM_TYPE_UNKNOWN;
                $node->tag      = 'unknown';
            }
            if ($this->char === '>') {
                $node->_[HDOM_INFO_TEXT] .= '>';
            }
            $this->link_nodes($node);
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        // text
        if (($pos = strpos($tag, '<')) !== false) {
            $tag = '<' . substr($tag, 0, -1);
            $node->_[HDOM_INFO_TEXT] = $tag;
            $this->link_nodes($node);
            $this->char = $this->doc[--$this->pos]; // prev
            return true;
        }

        // HTML5: tag names must start with a letter (a-z/A-Z). A '<' followed by a digit
        // or other non-letter character is plain text, not a tag opener.
        // This fixes the '<digit' content-loss bug (e.g. '<1 mol%', '<2NaCl', '<2.0').
        if (!preg_match("/^[a-zA-Z][\w\-:]*$/", $tag)) {
            $node->_[HDOM_INFO_TEXT] = '<' . $tag . $this->copy_until('<>');
            if ($this->char === '<') {
                $this->link_nodes($node);
                return true;
            }

            if ($this->char === '>') {
                $node->_[HDOM_INFO_TEXT] .= '>';
            }
            $this->link_nodes($node);
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        // begin tag
        $node->nodetype = HDOM_TYPE_ELEMENT;
        $tag_lower      = strtolower($tag);
        $node->tag      = ($this->lowercase) ? $tag_lower : $tag;

        // handle optional closing tags
        $optionalClosingTags = $this->optionalClosingArray ?? self::OPTIONAL_CLOSING_TAGS;
        if (isset($optionalClosingTags[$tag_lower])) {
            while (isset($optionalClosingTags[$tag_lower][strtolower($this->parent->tag)])) {
                $this->parent->_[HDOM_INFO_END] = 0;
                $this->parent                   = $this->parent->parent;
            }
            $node->parent = $this->parent;
        }

        $guard = 0; // prevent infinity loop
        $space = [$this->copy_skip(self::TOKEN_BLANK), '', ''];

        // attributes
        do {
            if ($this->char !== null && $space[0] === '') {
                break;
            }
            $name = $this->copy_until(self::TOKEN_EQUAL);
            if ($guard === $this->pos) {
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                continue;
            }
            $guard = $this->pos;

            // handle endless '<'
            if ($this->pos >= $this->size - 1 && $this->char !== '>') {
                $node->nodetype            = HDOM_TYPE_TEXT;
                $node->_[HDOM_INFO_END]   = 0;
                $node->_[HDOM_INFO_TEXT]  = '<' . $tag . $space[0] . $name;
                $node->tag                = 'text';
                $this->link_nodes($node);
                return true;
            }

            // handle mismatch '<'
            if ($this->doc[$this->pos - 1] == '<') {
                $node->nodetype           = HDOM_TYPE_TEXT;
                $node->tag                = 'text';
                $node->attr               = [];
                $node->_[HDOM_INFO_END]  = 0;
                $node->_[HDOM_INFO_TEXT] = substr($this->doc, $begin_tag_pos, $this->pos - $begin_tag_pos - 1);
                $this->pos -= 2;
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                $this->link_nodes($node);
                return true;
            }

            if ($name !== '/' && $name !== '') {
                $space[1] = $this->copy_skip(self::TOKEN_BLANK);
                $name     = $this->restore_noise($name);
                if ($this->lowercase) {
                    $name = strtolower($name);
                }
                if ($this->char === '=') {
                    $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                    $this->parse_attr($node, $name, $space);
                } else {
                    // no value attr: nowrap, checked selected...
                    $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
                    $node->attr[$name]           = true;
                    if ($this->char != '>') {
                        $this->char = $this->doc[--$this->pos]; // prev
                    }
                }
                $node->_[HDOM_INFO_SPACE][] = $space;
                $space = [$this->copy_skip(self::TOKEN_BLANK), '', ''];
            } else {
                break;
            }
        } while ($this->char !== '>' && $this->char !== '/');

        $this->link_nodes($node);
        $node->_[HDOM_INFO_ENDSPACE] = $space[0];

        // check self closing
        if ($this->copy_until_char_escape('>') === '/') {
            $node->_[HDOM_INFO_ENDSPACE] .= '/';
            $node->_[HDOM_INFO_END] = 0;
        } else {
            // reset parent
            if (!isset(self::SELF_CLOSING_TAGS[strtolower($node->tag)])) {
                $this->parent = $node;
            }
        }
        $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next

        // If it's a BR tag, we need to set it's text to the default text.
        if ($node->tag == "br") {
            $node->_[HDOM_INFO_INNER] = $this->default_br_text;
        }

        return true;
    }

    /**
     * Parse attributes.
     *
     * @param array<int, string> &$space
     */
    protected function parse_attr(Node $node, string $name, array &$space): void

    {
        // Per sourceforge: http://sourceforge.net/tracker/?func=detail&aid=3061408&group_id=218559&atid=1044037
        // If the attribute is already defined inside a tag, only pay attention to the first one.
        if (isset($node->attr[$name])) {
            return;
        }

        $space[2] = $this->copy_skip(self::TOKEN_BLANK);
        match ($this->char) {
            '"'     => $this->parse_double_quoted_attr($node, $name),
            "'"     => $this->parse_single_quoted_attr($node, $name),
            default => $this->parse_unquoted_attr($node, $name),
        };
        // PaperG: Attributes should not have \r or \n in them, that counts as html whitespace.
        $node->attr[$name] = str_replace("\r", "", $node->attr[$name]);
        $node->attr[$name] = str_replace("\n", "", $node->attr[$name]);
        // PaperG: If this is a "class" selector, lets get rid of the preceding and trailing space since some people leave it in the multi class case.
        if ($name == "class") {
            $node->attr[$name] = trim($node->attr[$name]);
        }
    }

    /**
     * Parse a double-quoted attribute value.
     */
    private function parse_double_quoted_attr(Node $node, string $name): void
    {
        $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
        $this->char                 = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
        $node->attr[$name]          = $this->restore_noise($this->copy_until_char_escape('"'));
        $this->char                 = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
    }

    /**
     * Parse a single-quoted attribute value.
     */
    private function parse_single_quoted_attr(Node $node, string $name): void
    {
        $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_SINGLE;
        $this->char                 = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
        $node->attr[$name]          = $this->restore_noise($this->copy_until_char_escape("'"));
        $this->char                 = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
    }

    /**
     * Parse an unquoted attribute value.
     */
    private function parse_unquoted_attr(Node $node, string $name): void
    {
        $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
        $node->attr[$name]          = $this->restore_noise($this->copy_until(self::TOKEN_ATTR));
    }

    /**
     * Link node's parent.
     */
    protected function link_nodes(Node &$node): void
    {
        $node->parent          = $this->parent;
        $this->parent->nodes[] = $node;
        $this->parent->invalidate_children_cache();
    }

    /**
     * As a text node.
     */
    protected function as_text_node(string $tag): bool
    {
        $node = new Node($this);
        ++$this->cursor;
        $node->_[HDOM_INFO_TEXT] = '</' . $tag . '>';
        $this->link_nodes($node);
        $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
        return true;
    }

    protected function skip(string $chars): void
    {
        $this->pos  += strspn($this->doc, $chars, $this->pos);
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
    }

    protected function copy_skip(string $chars): string
    {
        $pos = $this->pos;
        $len = strspn($this->doc, $chars, $pos);
        $this->pos  += $len;
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
        if ($len === 0) {
            return '';
        }
        return substr($this->doc, $pos, $len);
    }

    protected function copy_until(string $chars): string
    {
        $pos = $this->pos;
        $len = strcspn($this->doc, $chars, $pos);
        $this->pos  += $len;
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
        return substr($this->doc, $pos, $len);
    }

    protected function copy_until_char(string $char): string
    {
        if ($this->char === null) {
            return '';
        }

        if (($pos = strpos($this->doc, $char, $this->pos)) === false) {
            $ret        = substr($this->doc, $this->pos, $this->size - $this->pos);
            $this->char = null;
            $this->pos  = $this->size;
            return $ret;
        }

        if ($pos === $this->pos) {
            return '';
        }
        $pos_old    = $this->pos;
        $this->char = $this->doc[$pos];
        $this->pos  = $pos;
        return substr($this->doc, $pos_old, $pos - $pos_old);
    }

    protected function copy_until_char_escape(string $char): string
    {
        if ($this->char === null) {
            return '';
        }

        $start = $this->pos;
        while (1) {
            if (($pos = strpos($this->doc, $char, $start)) === false) {
                $ret        = substr($this->doc, $this->pos, $this->size - $this->pos);
                $this->char = null;
                $this->pos  = $this->size;
                return $ret;
            }

            if ($pos === $this->pos) {
                return '';
            }

            if ($this->doc[$pos - 1] === '\\') {
                $start = $pos + 1;
                continue;
            }

            $pos_old    = $this->pos;
            $this->char = $this->doc[$pos];
            $this->pos  = $pos;
            return substr($this->doc, $pos_old, $pos - $pos_old);
        }
    }

    /**
     * Remove noise from html content.
     * Save the noise in the $this->noise array.
     */
    protected function remove_noise(string $pattern, bool $remove_tag = false): void
    {
        $count = preg_match_all($pattern, $this->doc, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        for ($i = $count - 1; $i > -1; --$i) {
            $key = '___noise___' . sprintf('% 5d', count($this->noise) + 1000);
            $idx = ($remove_tag) ? 0 : 1;
            $this->noise[$key] = $matches[$i][$idx][0];
            $this->doc         = substr_replace($this->doc, $key, $matches[$i][$idx][1], strlen($matches[$i][$idx][0]));
        }

        // reset the length of content
        $this->size = strlen($this->doc);
        if ($this->size > 0) {
            $this->char = $this->doc[0];
        }
    }

    /**
     * Restore noise to html content.
     */
    public function restore_noise(string $text): string
    {
        while (($pos = strpos($text, '___noise___')) !== false) {
            // Sometimes there is a broken piece of markup, and we don't GET the pos+11 etc... token which indicates a problem outside of us...
            if (strlen($text) > $pos + 15) {
                $key = '___noise___' . $text[$pos + 11] . $text[$pos + 12] . $text[$pos + 13] . $text[$pos + 14] . $text[$pos + 15];

                if (isset($this->noise[$key])) {
                    $text = substr($text, 0, $pos) . $this->noise[$key] . substr($text, $pos + 16);
                } else {
                    // do this to prevent an infinite loop.
                    $text = substr($text, 0, $pos) . 'UNDEFINED NOISE FOR KEY: ' . $key . substr($text, $pos + 16);
                }
            } else {
                // There is no valid key being given back to us... We must get rid of the ___noise___ or we will have a problem.
                $text = substr($text, 0, $pos) . 'NO NUMERIC NOISE KEY' . substr($text, $pos + 11);
            }
        }
        return $text;
    }

    /**
     * Sometimes we NEED one of the noise elements.
     */
    public function search_noise(string $text): ?string
    {
        foreach ($this->noise as $noiseElement) {
            if (strpos($noiseElement, $text) !== false) {
                return $noiseElement;
            }
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->root->innertext();
    }

    public function __get(string $name): mixed
    {
        switch ($name) {
            case 'outertext':
                return $this->root->innertext();
            case 'innertext':
                return $this->root->innertext();
            case 'plaintext':
                return $this->root->text();
            case 'charset':
                return $this->_charset;
            case 'target_charset':
                return $this->_target_charset;
        }
        return null;
    }

    // camelCase DOM API delegates
    
    /**
     * @return list<Node>|Node|null
     */
    public function childNodes(int $idx = -1): Node|array|null { return $this->root->childNodes($idx); }
    public function firstChild(): ?Node                        { return $this->root->first_child(); }
    public function lastChild(): ?Node                         { return $this->root->last_child(); }
    public function createElement(string $name, mixed $value = null): Node|false
    {
        $parser = new Parser("<{$name}>{$value}</{$name}>");
        return $parser->find($name, 0) ?? false;
    }
    public function createTextNode(string $value): Node|false
    {
        $parser = new Parser($value);
        $last = end($parser->nodes);
        return ($last instanceof Node) ? $last : false;
    }
    public function getElementById(string $id): ?Node           { return $this->find("#$id", 0); }
    
    /**
     * @return list<Node>|Node|null
     */
    public function getElementsById(string $id, ?int $idx = null): Node|array|null { return $this->find("#$id", $idx); }
    public function getElementByTagName(string $name): ?Node    { return $this->find($name, 0); }
    
    /**
     * @return list<Node>|Node|null
     */
    public function getElementsByTagName(string $name, int $idx = -1): Node|array|null { return $this->find($name, $idx); }
    public function loadFile(string ...$args): void { $this->load_file(...$args); }
}
