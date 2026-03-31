<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleHtmlDom\Node;
use SimpleHtmlDom\Parser;
use SimpleHtmlDom\Settings;

class AppendChildTest extends TestCase
{
    protected function tearDown(): void
    {
        Settings::reset();
    }

    private function parse(string $html): Parser
    {
        return new Parser($html);
    }

    // ---------------------------------------------------------------
    // Basic append
    // ---------------------------------------------------------------

    public function testAppendChildBasic(): void
    {
        $dom = $this->parse('<div id="container"></div>');
        $container = $dom->find('#container', 0);
        $this->assertInstanceOf(Node::class, $container);

        $newNode = $dom->createElement('span', 'hello');
        $this->assertInstanceOf(Node::class, $newNode);

        $container->append_child($newNode);

        $this->assertContains($newNode, $container->children);
        $this->assertContains($newNode, $container->nodes);
        $this->assertSame($container, $newNode->parent);
    }

    // ---------------------------------------------------------------
    // find() discovers appended node
    // ---------------------------------------------------------------

    public function testAppendChildFindDiscoversAppendedNode(): void
    {
        $dom = $this->parse('<div id="container"></div>');
        $container = $dom->find('#container', 0);
        $this->assertInstanceOf(Node::class, $container);

        $newNode = $dom->createElement('span', 'hello');
        $this->assertInstanceOf(Node::class, $newNode);
        $container->append_child($newNode);

        $found = $dom->find('span');
        $this->assertIsArray($found);
        $this->assertCount(1, $found);
        $this->assertSame($newNode, $found[0]);
    }

    // ---------------------------------------------------------------
    // Detaches from old parent
    // ---------------------------------------------------------------

    public function testAppendChildDetachesFromOldParent(): void
    {
        $dom = $this->parse('<div id="a"><span>child</span></div><div id="b"></div>');
        $a = $dom->find('#a', 0);
        $b = $dom->find('#b', 0);
        $this->assertInstanceOf(Node::class, $a);
        $this->assertInstanceOf(Node::class, $b);

        $span = $dom->find('span', 0);
        $this->assertInstanceOf(Node::class, $span);

        $b->append_child($span);

        // span should no longer be in a's children
        $this->assertNotContains($span, $a->children);
        $this->assertNotContains($span, $a->nodes);

        // span should be in b's children
        $this->assertContains($span, $b->children);
        $this->assertSame($b, $span->parent);
    }

    // ---------------------------------------------------------------
    // Propagates $dom reference
    // ---------------------------------------------------------------

    public function testAppendChildPropagatesDomReference(): void
    {
        $dom = $this->parse('<div id="container"></div>');
        $container = $dom->find('#container', 0);
        $this->assertInstanceOf(Node::class, $container);

        $newNode = $dom->createElement('p', 'text');
        $this->assertInstanceOf(Node::class, $newNode);

        // Before append, the node's $dom points to the temporary parser
        $this->assertNotSame($dom, $newNode->dom);

        $container->append_child($newNode);

        // After append, the node's $dom must point to the target parser
        $this->assertSame($dom, $newNode->dom);
    }

    // ---------------------------------------------------------------
    // Subtree reindexed - find discovers grandchild
    // ---------------------------------------------------------------

    public function testAppendChildSubtreeReindexed(): void
    {
        $dom = $this->parse('<div id="container"></div>');
        $container = $dom->find('#container', 0);
        $this->assertInstanceOf(Node::class, $container);

        // Create a node with children: <ul><li>item</li></ul>
        $newNode = $dom->createElement('ul', '<li>item</li>');
        $this->assertInstanceOf(Node::class, $newNode);

        $container->append_child($newNode);

        // find() from root should discover the nested li
        $found = $dom->find('li');
        $this->assertIsArray($found);
        $this->assertCount(1, $found);
        $this->assertSame('li', $found[0]->tag);
    }

    // ---------------------------------------------------------------
    // Serialises correctly via save()
    // ---------------------------------------------------------------

    public function testAppendChildSerialisesCorrectly(): void
    {
        $dom = $this->parse('<div id="container"></div>');
        $container = $dom->find('#container', 0);
        $this->assertInstanceOf(Node::class, $container);

        $newNode = $dom->createElement('span', 'hello');
        $this->assertInstanceOf(Node::class, $newNode);
        $container->append_child($newNode);

        $output = $dom->save();
        $this->assertStringContainsString('<span>hello</span>', $output);
    }

    // ---------------------------------------------------------------
    // outertext includes appended child
    // ---------------------------------------------------------------

    public function testAppendChildOutertextIncludesAppended(): void
    {
        $dom = $this->parse('<div id="container"></div>');
        $container = $dom->find('#container', 0);
        $this->assertInstanceOf(Node::class, $container);

        $newNode = $dom->createElement('b', 'bold');
        $this->assertInstanceOf(Node::class, $newNode);
        $container->append_child($newNode);

        $outer = $container->outertext();
        $this->assertStringContainsString('<b>bold</b>', $outer);
        $this->assertStringContainsString('<div id="container">', $outer);
    }

    // ---------------------------------------------------------------
    // Text node append
    // ---------------------------------------------------------------

    public function testAppendChildWithTextNode(): void
    {
        $dom = $this->parse('<p id="para"></p>');
        $para = $dom->find('#para', 0);
        $this->assertInstanceOf(Node::class, $para);

        $textNode = $dom->createTextNode('some text');
        $this->assertInstanceOf(Node::class, $textNode);

        $para->append_child($textNode);

        $output = $dom->save();
        $this->assertStringContainsString('some text', $output);
    }

    // ---------------------------------------------------------------
    // camelCase delegate works
    // ---------------------------------------------------------------

    public function testAppendChildCamelCaseDelegate(): void
    {
        $dom = $this->parse('<div id="container"></div>');
        $container = $dom->find('#container', 0);
        $this->assertInstanceOf(Node::class, $container);

        $newNode = $dom->createElement('em', 'emphasis');
        $this->assertInstanceOf(Node::class, $newNode);

        // Use camelCase variant
        $result = $container->appendChild($newNode);

        $this->assertSame($newNode, $result);
        $this->assertContains($newNode, $container->children);

        $found = $dom->find('em');
        $this->assertIsArray($found);
        $this->assertCount(1, $found);
    }

    // ---------------------------------------------------------------
    // Subtree dom propagation (all descendants get new $dom)
    // ---------------------------------------------------------------

    public function testAppendChildPropagatesDomToEntireSubtree(): void
    {
        $dom = $this->parse('<div id="container"></div>');
        $container = $dom->find('#container', 0);
        $this->assertInstanceOf(Node::class, $container);

        $newNode = $dom->createElement('ul', '<li><a href="#">link</a></li>');
        $this->assertInstanceOf(Node::class, $newNode);

        $container->append_child($newNode);

        // All nodes in the subtree should point to $dom
        $li = $dom->find('li', 0);
        $this->assertInstanceOf(Node::class, $li);
        $this->assertSame($dom, $li->dom);

        $a = $dom->find('a', 0);
        $this->assertInstanceOf(Node::class, $a);
        $this->assertSame($dom, $a->dom);
    }

    // ---------------------------------------------------------------
    // Appending node without parent (orphan)
    // ---------------------------------------------------------------

    public function testAppendOrphanNode(): void
    {
        $dom = $this->parse('<div id="container"></div>');
        $container = $dom->find('#container', 0);
        $this->assertInstanceOf(Node::class, $container);

        // Create a completely new node manually
        $node = new Node();
        $node->tag = 'custom';
        $node->nodetype = HDOM_TYPE_ELEMENT;
        $node->_[HDOM_INFO_BEGIN] = 0;
        $node->_[HDOM_INFO_END] = 0;

        $container->append_child($node);

        $this->assertSame($container, $node->parent);
        $this->assertSame($dom, $node->dom);
        $this->assertContains($node, $container->children);
    }
}
