<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleHtmlDom\Parser;

class DumpTest extends TestCase
{
    private function parse(string $html): Parser
    {
        return new Parser($html);
    }

    // -------------------------------------------------------------------------
    // dump_node() tests
    // -------------------------------------------------------------------------

    public function testDumpNodeReturnMode(): void
    {
        $parser = $this->parse('<a href="https://example.com" class="btn">click</a>');
        $node   = $parser->find('a', 0);
        $this->assertNotNull($node);

        $result = $node->dump_node(false);

        $this->assertIsString($result);
        $this->assertStringContainsString('[href]', $result);
        $this->assertStringContainsString('href', $result);
        $this->assertStringContainsString('HDOM_INNER_INFO', $result);
        $this->assertStringContainsString('children:', $result);
        $this->assertStringContainsString('nodes:', $result);
        $this->assertStringContainsString('tag_start:', $result);
    }

    public function testDumpNodeEchoMode(): void
    {
        $parser = $this->parse('<a href="https://example.com" class="btn">click</a>');
        $node   = $parser->find('a', 0);
        $this->assertNotNull($node);

        ob_start();
        $returnValue = $node->dump_node(true);
        $output      = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertNull($returnValue);
    }

    public function testDumpNodeNoAttributes(): void
    {
        $parser = $this->parse('<p>hello</p>');
        $node   = $parser->find('p', 0);
        $this->assertNotNull($node);

        $result = $node->dump_node(false);

        $this->assertIsString($result);
        $this->assertStringContainsString('p', $result);
        $this->assertStringNotContainsString('href', $result);
        $this->assertStringNotContainsString('class', $result);
    }

    public function testDumpNodeWithHdomInfoInner(): void
    {
        $parser = $this->parse('<div>inner text</div>');
        $node   = $parser->find('div', 0);
        $this->assertNotNull($node);

        $result = $node->dump_node(false);

        $this->assertIsString($result);
        $this->assertStringContainsString('HDOM_INNER_INFO', $result);
    }

    public function testDumpNodeNullInnerInfo(): void
    {
        $parser = $this->parse('<hr>');
        $node   = $parser->find('hr', 0);
        $this->assertNotNull($node);

        $result = $node->dump_node(false);

        $this->assertIsString($result);
        // dump_node() emits the ' NULL ' placeholder when HDOM_INFO_INNER is not set
        $this->assertStringContainsString('HDOM_INNER_INFO', $result);
        $this->assertStringContainsString(' NULL ', $result);
    }

    // -------------------------------------------------------------------------
    // dump() tests
    // -------------------------------------------------------------------------

    public function testDumpSingleNodeAttrsHidden(): void
    {
        $parser = $this->parse('<span class="x">text</span>');
        $node   = $parser->find('span', 0);
        $this->assertNotNull($node);

        ob_start();
        $node->dump(false, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('span', $output);
        $this->assertStringNotContainsString('class', $output);
    }

    public function testDumpSingleNodeAttrsShown(): void
    {
        $parser = $this->parse('<span class="x">text</span>');
        $node   = $parser->find('span', 0);
        $this->assertNotNull($node);

        ob_start();
        $node->dump(true, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('span', $output);
        $this->assertStringContainsString('class', $output);
    }

    public function testDumpRecursiveTree(): void
    {
        $parser = $this->parse('<ul><li>item</li></ul>');
        $node   = $parser->find('ul', 0);
        $this->assertNotNull($node);

        ob_start();
        $node->dump(true, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('ul', $output);
        $this->assertStringContainsString('li', $output);
        // The li child should be indented at least one level (4 spaces per level)
        $this->assertMatchesRegularExpression('/^ {4}/m', $output);
    }

    // -------------------------------------------------------------------------
    // dump_html_tree() tests
    // -------------------------------------------------------------------------

    public function testDumpHtmlTreeDelegation(): void
    {
        $parser = $this->parse('<section id="main"><p>content</p></section>');
        $node   = $parser->find('section', 0);
        $this->assertNotNull($node);

        ob_start();
        $node->dump(true, 0);
        $outputA = ob_get_clean();

        ob_start();
        dump_html_tree($node, true, 0);
        $outputB = ob_get_clean();

        $this->assertSame($outputA, $outputB);
    }

    public function testDumpHtmlTreeDepthParameter(): void
    {
        $parser = $this->parse('<div>hello</div>');
        $node   = $parser->find('div', 0);
        $this->assertNotNull($node);

        ob_start();
        dump_html_tree($node, true, 2);
        $output = ob_get_clean();

        // At depth 2, the tag line starts with 8 spaces (4 spaces × 2 levels)
        $this->assertStringStartsWith('        ', $output);
    }
}
