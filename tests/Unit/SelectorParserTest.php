<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleHtmlDom\SelectorParser;

class SelectorParserTest extends TestCase
{
    private ?\simple_html_dom $dom = null;

    protected function setUp(): void
    {
        $this->dom = str_get_html('<div id="root" class="container"><span class="child">text</span></div>');
    }

    protected function tearDown(): void
    {
        $this->dom?->clear();
        $this->dom = null;
    }

    private function getParser(): SelectorParser
    {
        $root = $this->dom->find('div', 0);
        return new SelectorParser($root);
    }

    // -------------------------------------------------------------------------
    // parse_selector
    // -------------------------------------------------------------------------

    public function testParseSelectorSimpleTag(): void
    {
        $parser = $this->getParser();
        $result = $parser->parse_selector('span');
        $this->assertCount(1, $result);        // one selector group
        $this->assertCount(1, $result[0]);     // one segment
        $this->assertSame('span', $result[0][0][0]); // tag
        $this->assertNull($result[0][0][1]);          // no key
        $this->assertNull($result[0][0][2]);          // no val
        $this->assertSame('=', $result[0][0][3]);     // default exp
        $this->assertFalse($result[0][0][4]);          // no_key = false
    }

    public function testParseSelectorClassSelector(): void
    {
        $parser = $this->getParser();
        $result = $parser->parse_selector('.foo');
        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]);
        $this->assertSame('class', $result[0][0][1]); // key = class
        $this->assertSame('foo', $result[0][0][2]);   // val = foo
    }

    public function testParseSelectorIdSelector(): void
    {
        $parser = $this->getParser();
        $result = $parser->parse_selector('#bar');
        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]);
        $this->assertSame('id', $result[0][0][1]);  // key = id
        $this->assertSame('bar', $result[0][0][2]); // val = bar
    }

    public function testParseSelectorAttributeSelector(): void
    {
        $parser = $this->getParser();
        $result = $parser->parse_selector('a[href]');
        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]);
        $this->assertSame('a', $result[0][0][0]);     // tag = a
        $this->assertSame('href', $result[0][0][1]);  // key = href
    }

    public function testParseSelectorAttributeValueSelector(): void
    {
        $parser = $this->getParser();
        $result = $parser->parse_selector('input[type=text]');
        $this->assertCount(1, $result);
        $this->assertSame('input', $result[0][0][0]);
        $this->assertSame('type', $result[0][0][1]);
        $this->assertSame('text', $result[0][0][2]);
        $this->assertSame('=', $result[0][0][3]);
    }

    public function testParseSelectorNegatedAttribute(): void
    {
        $parser = $this->getParser();
        $result = $parser->parse_selector('[!hidden]');
        $this->assertCount(1, $result);
        // The '!' prefix is consumed; key becomes 'hidden' and no_key becomes true.
        $this->assertSame('hidden', $result[0][0][1]);
        $this->assertTrue($result[0][0][4]); // no_key = true
    }

    public function testParseSelectorMultipleGroups(): void
    {
        $parser = $this->getParser();
        $result = $parser->parse_selector('div, span');
        $this->assertCount(2, $result);
        $this->assertSame('div', $result[0][0][0]);
        $this->assertSame('span', $result[1][0][0]);
    }

    // -------------------------------------------------------------------------
    // match
    // -------------------------------------------------------------------------

    public function testMatchEquals(): void
    {
        $parser = $this->getParser();
        $this->assertTrue($parser->match('=', 'foo', 'foo'));
        $this->assertFalse($parser->match('=', 'foo', 'bar'));
    }

    public function testMatchNotEquals(): void
    {
        $parser = $this->getParser();
        $this->assertTrue($parser->match('!=', 'foo', 'bar'));
        $this->assertFalse($parser->match('!=', 'foo', 'foo'));
    }

    public function testMatchStartsWith(): void
    {
        $parser = $this->getParser();
        $this->assertTrue($parser->match('^=', 'foo', 'foobar'));
        $this->assertFalse($parser->match('^=', 'foo', 'barfoo'));
    }

    public function testMatchEndsWith(): void
    {
        $parser = $this->getParser();
        $this->assertTrue($parser->match('$=', 'bar', 'foobar'));
        $this->assertFalse($parser->match('$=', 'bar', 'barfoo'));
    }

    public function testMatchContains(): void
    {
        $parser = $this->getParser();
        $this->assertTrue($parser->match('*=', 'oba', 'foobar'));
        $this->assertFalse($parser->match('*=', 'xyz', 'foobar'));
    }

    public function testMatchUnknownOperatorReturnsFalse(): void
    {
        $parser = $this->getParser();
        $this->assertFalse($parser->match('~=', 'foo', 'foo'));
    }
}
