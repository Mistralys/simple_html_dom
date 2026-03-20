<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleHtmlDom\Node;
use SimpleHtmlDom\Parser;

class NodeTest extends TestCase
{
    private function parse(string $html): Parser
    {
        return new Parser($html);
    }

    public function testFirstChild(): void
    {
        $p = $this->parse('<ul><li>a</li><li>b</li></ul>');
        $ul = $p->find('ul', 0);
        $this->assertInstanceOf(Node::class, $ul);
        $first = $ul->first_child();
        $this->assertInstanceOf(Node::class, $first);
        $this->assertSame('li', $first->tag);
    }

    public function testLastChild(): void
    {
        $p = $this->parse('<ul><li>a</li><li>b</li></ul>');
        $ul = $p->find('ul', 0);
        $this->assertInstanceOf(Node::class, $ul);
        $last = $ul->last_child();
        $this->assertInstanceOf(Node::class, $last);
        $this->assertSame('li', $last->tag);
        // last child should contain 'b'
        $this->assertStringContainsString('b', $last->innertext());
    }

    public function testNextSibling(): void
    {
        $p = $this->parse('<ul><li>a</li><li>b</li></ul>');
        $ul = $p->find('ul', 0);
        $this->assertInstanceOf(Node::class, $ul);
        $first = $ul->first_child();
        $this->assertInstanceOf(Node::class, $first);
        $next = $first->next_sibling();
        $this->assertInstanceOf(Node::class, $next);
        $this->assertSame('li', $next->tag);
    }

    public function testPrevSibling(): void
    {
        $p = $this->parse('<ul><li>a</li><li>b</li></ul>');
        $ul = $p->find('ul', 0);
        $this->assertInstanceOf(Node::class, $ul);
        $last = $ul->last_child();
        $this->assertInstanceOf(Node::class, $last);
        $prev = $last->prev_sibling();
        $this->assertInstanceOf(Node::class, $prev);
        $this->assertSame('li', $prev->tag);
        // prev should be the first li (containing 'a')
        $this->assertStringContainsString('a', $prev->innertext());
    }

    public function testNextSiblingOnLastReturnsNull(): void
    {
        $p = $this->parse('<ul><li>a</li><li>b</li></ul>');
        $ul = $p->find('ul', 0);
        $this->assertInstanceOf(Node::class, $ul);
        $last = $ul->last_child();
        $this->assertInstanceOf(Node::class, $last);
        $this->assertNull($last->next_sibling());
    }

    public function testPrevSiblingOnFirstReturnsNull(): void
    {
        $p = $this->parse('<ul><li>a</li><li>b</li></ul>');
        $ul = $p->find('ul', 0);
        $this->assertInstanceOf(Node::class, $ul);
        $first = $ul->first_child();
        $this->assertInstanceOf(Node::class, $first);
        $this->assertNull($first->prev_sibling());
    }

    public function testHasChild(): void
    {
        $p = $this->parse('<ul><li>a</li></ul>');
        $ul = $p->find('ul', 0);
        $this->assertInstanceOf(Node::class, $ul);
        $this->assertTrue($ul->has_child());

        // A text node inside li has no child element nodes
        $li = $p->find('li', 0);
        $this->assertInstanceOf(Node::class, $li);
        $textNode = $li->first_child();
        if ($textNode !== null) {
            $this->assertFalse($textNode->has_child());
        }
    }

    public function testFindAncestorTag(): void
    {
        $p = $this->parse('<table><tr><td>cell</td></tr></table>');
        $td = $p->find('td', 0);
        $this->assertInstanceOf(Node::class, $td);
        $ancestor = $td->find_ancestor_tag('table');
        $this->assertInstanceOf(Node::class, $ancestor);
        $this->assertSame('table', $ancestor->tag);
    }

    public function testText(): void
    {
        $p = $this->parse('<p>hello <b>world</b></p>');
        $node = $p->find('p', 0);
        $this->assertInstanceOf(Node::class, $node);
        $text = $node->text();
        $this->assertStringContainsString('hello', $text);
        $this->assertStringContainsString('world', $text);
    }

    public function testMakeup(): void
    {
        $p = $this->parse('<a href="x">link</a>');
        $a = $p->find('a', 0);
        $this->assertInstanceOf(Node::class, $a);
        $makeup = $a->makeup();
        $this->assertStringStartsWith('<a', $makeup);
        $this->assertStringContainsString('href', $makeup);
    }

    public function testDumpNodeRegressionB001B002(): void
    {
        // Regression for B-001 (undefined $node variable) and B-002 (dead isset($this->text) block).
        // dump_node() should return a non-empty string without any PHP errors or warnings.
        $p = $this->parse('<div class="test">content</div>');
        $node = $p->find('div', 0);
        $this->assertInstanceOf(Node::class, $node);

        $result = $node->dump_node(false);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('div', $result);
    }

    public function testNullChildrenAfterClear(): void
    {
        // Regression for M-007: first_child() and last_child() must not throw TypeError
        // when $children is null (set to null by clear()).
        $p = $this->parse('<ul><li>a</li></ul>');
        $ul = $p->find('ul', 0);
        $this->assertInstanceOf(Node::class, $ul);

        $ul->clear();

        // These must not throw a TypeError — $children is now null
        $this->assertNull($ul->first_child());
        $this->assertNull($ul->last_child());
    }

    public function testDumpHtmlTree(): void
    {
        // Regression for B-003: dump_html_tree() previously called $node->dump($node)
        // passing a Node object as bool $show_attr — caused TypeError.
        // After the fix it correctly calls $node->dump($show_attr, $deep).
        $p = $this->parse('<div>test</div>');
        $node = $p->find('div', 0);
        $this->assertInstanceOf(Node::class, $node);

        // Capture output and assert no TypeError is thrown
        ob_start();
        dump_html_tree($node, false, 0);
        $output = ob_get_clean();

        // dump() echoes the tag tree; at minimum it should not throw and should produce output
        $this->assertIsString($output);
    }
}
