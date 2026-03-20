<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleHtmlDom\Node;
use SimpleHtmlDom\Parser;

class ParserTest extends TestCase
{
    public function testLoadRoundTrip(): void
    {
        $p = new Parser();
        $p->load('<p>hello</p>');
        $this->assertStringContainsString('hello', $p->save());
    }

    public function testFindByTag(): void
    {
        $p = new Parser('<b>x</b><i>y</i>');
        $node = $p->find('b', 0);
        $this->assertInstanceOf(Node::class, $node);
        $this->assertSame('b', $node->tag);
    }

    public function testFindByClass(): void
    {
        $p = new Parser('<span class="foo">x</span>');
        $node = $p->find('.foo', 0);
        $this->assertInstanceOf(Node::class, $node);
        $this->assertSame('span', $node->tag);
    }

    public function testFindById(): void
    {
        $p = new Parser('<div id="bar">x</div>');
        $node = $p->find('#bar', 0);
        $this->assertInstanceOf(Node::class, $node);
        $this->assertSame('div', $node->tag);
    }

    public function testSave(): void
    {
        $p = new Parser('<p>test</p>');
        $output = $p->save();
        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    public function testToString(): void
    {
        $p = new Parser('<p>test</p>');
        $this->assertSame($p->save(), (string) $p);
    }

    public function testFindReturnsArrayWithNoIndex(): void
    {
        $p = new Parser('<p>one</p><p>two</p>');
        $nodes = $p->find('p');
        $this->assertIsArray($nodes);
        $this->assertCount(2, $nodes);
    }

    public function testForceTagsClosedFalse(): void
    {
        // Regression test for M-001: constructing with forceTagsClosed: false must not
        // emit a PHP 8.4 deprecation notice about the optional_closing_array dynamic property.
        $deprecationTriggered = false;
        set_error_handler(
            function (int $errno, string $errstr) use (&$deprecationTriggered): bool {
                if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
                    $deprecationTriggered = true;
                }
                return true;
            },
            E_DEPRECATED | E_USER_DEPRECATED
        );

        try {
            $p = new Parser('<ul><li>a<li>b</ul>', forceTagsClosed: false);
            $this->assertInstanceOf(Parser::class, $p);
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($deprecationTriggered, 'No deprecation notice should be emitted for $optionalClosingArray');
    }
}
