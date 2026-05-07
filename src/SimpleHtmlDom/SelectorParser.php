<?php

declare(strict_types=1);

namespace SimpleHtmlDom;

/**
 * CSS selector parsing, seeking, and matching logic.
 *
 * Extracted from simple_html_dom_node::parse_selector(), ::seek(), and ::match().
 * Receives the calling node as context so it can access the DOM reference.
 *
 * @package SimpleHtmlDom
 */
class SelectorParser
{
    public function __construct(private readonly Node $node)
    {
    }

    /**
     * Parse a CSS selector string into an array of selector groups.
     *
     * @return array<int, array<int, array{0: string, 1: string|null, 2: string|null, 3: string, 4: bool}>>
     */
    public function parse_selector(string $selectorString): array
    {
        // pattern of CSS selectors, modified from mootools
        // Paperg: Add the colon to the attribute, so that it properly finds <tag attr:ibute="something" > like google does.
        $pattern = "/([\w\-:\*]*)(?:\#([\w\-]+)|\.([\w\-]+))?(?:\[@?(!?[\w\-:]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        preg_match_all($pattern, trim($selectorString) . ' ', $matches, PREG_SET_ORDER);

        $selectors = [];
        $result = [];

        foreach ($matches as $m) {
            $m[0] = trim($m[0]);
            if ($m[0] === '' || $m[0] === '/' || $m[0] === '//') {
                continue;
            }
            // for browser generated xpath
            if ($m[1] === 'tbody') {
                continue;
            }

            $tag    = $m[1];
            $key    = null;
            $val    = null;
            $exp    = '=';
            $no_key = false;

            if (!empty($m[2])) {
                $key = 'id';
                $val = $m[2];
            }
            if (!empty($m[3])) {
                $key = 'class';
                $val = $m[3];
            }
            if (!empty($m[4])) {
                $key = $m[4];
            }
            if (!empty($m[5])) {
                $exp = $m[5];
            }
            if (!empty($m[6])) {
                $val = $m[6];
            }

            // convert to lowercase
            if ($this->node->dom && $this->node->dom->lowercase) {
                $tag = strtolower($tag);
                $key = $key !== null ? strtolower($key) : null;
            }
            // elements that do NOT have the specified attribute
            if (isset($key[0]) && $key[0] === '!') {
                $key    = substr($key, 1);
                $no_key = true;
            }

            $result[] = [$tag, $key, $val, $exp, $no_key];
            if (trim($m[7]) === ',') {
                $selectors[] = $result;
                $result      = [];
            }
        }
        if (count($result) > 0) {
            $selectors[] = $result;
        }
        return $selectors;
    }

    /**
     * Seek matching nodes within the subtree of $this->node.
     *
     * @param array{0: string, 1: string|null, 2: string|null, 3: string, 4: bool} $selector
     * @param array<int, int> &$ret Results accumulator (passed by reference)
     */
    public function seek(array $selector, array &$ret, bool $lowercase = false): void
    {
        [$tag, $key, $val, $exp, $no_key] = $selector;

        // Cache the virtual children property once (it filters nodes[] on every access).
        $children = $this->node->children;

        // xpath index
        if ($tag && $key && is_numeric($key)) {
            $count = 0;
            foreach ($children as $c) {
                if ($tag === '*' || $tag === $c->tag) {
                    if (++$count == $key) {
                        $ret[(int) $c->_[HDOM_INFO_BEGIN]] = 1;
                        return;
                    }
                }
            }
            return;
        }

        $end = (!empty($this->node->_[HDOM_INFO_END])) ? $this->node->_[HDOM_INFO_END] : 0;
        if ($end == 0) {
            $parent = $this->node->parent;
            while (!isset($parent->_[HDOM_INFO_END]) && $parent !== null) {
                $end -= 1;
                $parent = $parent->parent;
            }
            $end += $parent->_[HDOM_INFO_END];
        }

        for ($i = $this->node->_[HDOM_INFO_BEGIN] + 1; $i < $end; ++$i) {
            $node = $this->node->dom->nodes[$i];

            $pass = true;

            if ($tag === '*' && !$key) {
                if (in_array($node, $children, true)) {
                    $ret[$i] = 1;
                }
                continue;
            }

            // compare tag
            if ($tag && $tag != $node->tag && $tag !== '*') {
                $pass = false;
            }
            // compare key
            if ($pass && $key) {
                if ($no_key) {
                    if (isset($node->attr[$key])) {
                        $pass = false;
                    }
                } else {
                    if (($key != "plaintext") && !isset($node->attr[$key])) {
                        $pass = false;
                    }
                }
            }
            // compare value
            if ($pass && $key && $val && $val !== '*') {
                if ($key == "plaintext") {
                    // $node->plaintext actually returns $node->text()
                    $nodeKeyValue = $node->text();
                } else {
                    $nodeKeyValue = $node->attr[$key];
                }

                if ($lowercase) {
                    $check = $this->match($exp, strtolower($val), strtolower($nodeKeyValue));
                } else {
                    $check = $this->match($exp, $val, $nodeKeyValue);
                }

                // handle multiple class
                if (!$check && strcasecmp($key, 'class') === 0) {
                    foreach (explode(' ', $node->attr[$key]) as $k) {
                        if (!empty($k)) {
                            if ($lowercase) {
                                $check = $this->match($exp, strtolower($val), strtolower($k));
                            } else {
                                $check = $this->match($exp, $val, $k);
                            }
                            if ($check) {
                                break;
                            }
                        }
                    }
                }
                if (!$check) {
                    $pass = false;
                }
            }
            if ($pass) {
                $ret[$i] = 1;
            }
            unset($node);
        }
    }

    /**
     * Test a single value against a pattern using the given CSS selector operator.
     */
    public function match(string $exp, mixed $pattern, mixed $value): bool
    {
        return match ($exp) {
            '='  => ($value === $pattern),
            '!=' => ($value !== $pattern),
            '^=' => (bool) preg_match("/^" . preg_quote($pattern, '/') . "/", $value),
            '$=' => (bool) preg_match("/" . preg_quote($pattern, '/') . "$/", $value),
            '*=' => ($pattern[0] === '/')
                ? (bool) preg_match($pattern, $value)
                : (bool) preg_match("/" . preg_quote($pattern, '/') . "/i", $value),
            default => false,
        };
    }
}
