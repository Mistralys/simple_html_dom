<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleHtmlDom\Parser;
use SimpleHtmlDom\Settings;

/**
 * Verifies the six node-behaviour scenarios identified in the synthesis report:
 *  1. children[] population rules (elements only)
 *  2. nodes[] population rules (all node types)
 *  3. next_sibling() skips text nodes
 *  4. prev_sibling() skips text nodes
 *  5. outertext = '' retains the node in nodes[]
 *  6. plaintext preserves inter-node whitespace
 */
class NodeBehaviorTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset all settings to avoid cross-test contamination.
        Settings::reset();
    }

    // -------------------------------------------------------------------------
    // 1. children[] contains only element nodes
    // -------------------------------------------------------------------------

    /**
     * When a parent element has mixed content (element + text nodes), only
     * element-type children must appear in the children[] array.
     */
    public function test_children_array_contains_only_elements(): void
    {
        $dom = str_get_html('<div><p>A</p> whitespace <p>B</p></div>');
        $this->assertNotFalse($dom, 'str_get_html() must return a Parser instance');

        $div = $dom->find('div', 0);
        $this->assertNotNull($div, '<div> must be found');

        // All entries in children[] must be element nodes.
        foreach ($div->children as $child) {
            $this->assertSame(
                HDOM_TYPE_ELEMENT,
                $child->nodetype,
                'children[] must contain only HDOM_TYPE_ELEMENT nodes; found nodetype ' . $child->nodetype
            );
        }

        // There should be exactly 2 element children (<p>A</p> and <p>B</p>).
        $this->assertCount(2, $div->children, 'children[] must hold exactly the two <p> elements');
    }

    // -------------------------------------------------------------------------
    // 2. nodes[] contains all node types (elements AND text)
    // -------------------------------------------------------------------------

    /**
     * nodes[] must include every child — both element nodes and inter-element
     * text nodes — so that it always holds a superset of children[].
     */
    public function test_nodes_array_contains_all_node_types(): void
    {
        $dom = str_get_html('<div><p>A</p> whitespace <p>B</p></div>');
        $this->assertNotFalse($dom, 'str_get_html() must return a Parser instance');

        $div = $dom->find('div', 0);
        $this->assertNotNull($div, '<div> must be found');

        $nodeTypes = array_map(fn($n) => $n->nodetype, $div->nodes);

        // nodes[] must contain at least one text node.
        $this->assertContains(
            HDOM_TYPE_TEXT,
            $nodeTypes,
            'nodes[] must include at least one text node for the " whitespace " content'
        );

        // nodes[] must contain at least one element node.
        $this->assertContains(
            HDOM_TYPE_ELEMENT,
            $nodeTypes,
            'nodes[] must include element nodes'
        );

        // nodes[] must hold more entries than children[] (text nodes inflate the count).
        $this->assertGreaterThan(
            count($div->children),
            count($div->nodes),
            'nodes[] must hold more entries than children[] when text nodes are present'
        );
    }

    // -------------------------------------------------------------------------
    // 3. next_sibling() returns an element, not a text node
    // -------------------------------------------------------------------------

    /**
     * next_sibling() walks children[], which contains only element nodes.
     * Therefore calling it on the first <p> must return the second <p>, not the
     * whitespace text node that lies between them.
     */
    public function test_next_sibling_returns_element_not_text(): void
    {
        $dom = str_get_html('<div><p>A</p> whitespace <p>B</p></div>');
        $this->assertNotFalse($dom, 'str_get_html() must return a Parser instance');

        $firstP = $dom->find('p', 0);
        $this->assertNotNull($firstP, 'First <p> must be found');

        $next = $firstP->next_sibling();
        $this->assertNotNull($next, 'next_sibling() must not return null');

        $this->assertSame(
            HDOM_TYPE_ELEMENT,
            $next->nodetype,
            'next_sibling() must return an element node, not a text node'
        );
        $this->assertSame('p', $next->tag, 'next_sibling() must return the second <p>');
    }

    // -------------------------------------------------------------------------
    // 4. prev_sibling() returns an element, not a text node
    // -------------------------------------------------------------------------

    /**
     * prev_sibling() walks children[], which contains only element nodes.
     * Therefore calling it on the second <p> must return the first <p>, not the
     * whitespace text node between them.
     */
    public function test_prev_sibling_returns_element_not_text(): void
    {
        $dom = str_get_html('<div><p>A</p> whitespace <p>B</p></div>');
        $this->assertNotFalse($dom, 'str_get_html() must return a Parser instance');

        $secondP = $dom->find('p', 1);
        $this->assertNotNull($secondP, 'Second <p> must be found');

        $prev = $secondP->prev_sibling();
        $this->assertNotNull($prev, 'prev_sibling() must not return null');

        $this->assertSame(
            HDOM_TYPE_ELEMENT,
            $prev->nodetype,
            'prev_sibling() must return an element node, not a text node'
        );
        $this->assertSame('p', $prev->tag, 'prev_sibling() must return the first <p>');
    }

    // -------------------------------------------------------------------------
    // 5. Setting outertext = '' retains the node object in nodes[]
    // -------------------------------------------------------------------------

    /**
     * Setting a node's outertext to an empty string suppresses its rendered
     * output, but the node object itself must remain in the parent's nodes[]
     * array (and thus in the Parser's nodes[] flat list). The node must NOT be
     * physically removed from the tree by the assignment.
     */
    public function test_outertext_empty_retains_node_in_nodes_array(): void
    {
        $dom = str_get_html('<div><span>hello</span></div>');
        $this->assertNotFalse($dom, 'str_get_html() must return a Parser instance');

        $span = $dom->find('span', 0);
        $this->assertNotNull($span, '<span> must be found before assignment');

        $countBefore = count($dom->nodes);

        // Suppress the span's rendered output.
        $span->outertext = '';

        // The node count on the Parser must not decrease.
        $this->assertCount(
            $countBefore,
            $dom->nodes,
            'Setting outertext="" must not remove the node from the Parser nodes[] array'
        );

        // The span can still be found via find().
        $spanAfter = $dom->find('span', 0);
        $this->assertNotNull(
            $spanAfter,
            'The span node must still be discoverable via find() after outertext=""'
        );

        // Its rendered output must now be empty.
        $this->assertSame('', $span->outertext, 'outertext must be empty after the assignment');
    }

    // -------------------------------------------------------------------------
    // 6. plaintext preserves inter-node whitespace
    // -------------------------------------------------------------------------

    /**
     * When adjacent inline elements are separated by a literal space in the
     * source HTML, that space must survive in the plain-text output produced by
     * text() / the $plaintext virtual property, so that words from different
     * child nodes do not run together.
     */
    public function test_plaintext_preserves_inter_node_whitespace(): void
    {
        $dom = str_get_html('<p><span>A</span> <span>B</span></p>');
        $this->assertNotFalse($dom, 'str_get_html() must return a Parser instance');

        $p = $dom->find('p', 0);
        $this->assertNotNull($p, '<p> must be found');

        $plain = $p->text();

        // The two span texts must NOT be merged without a space.
        $this->assertStringNotContainsString(
            'AB',
            $plain,
            'plaintext must not merge "A" and "B" without whitespace'
        );

        // There must be whitespace between A and B.
        $this->assertMatchesRegularExpression(
            '/A\s+B/',
            $plain,
            'plaintext must preserve the inter-node space between <span>A</span> and <span>B</span>'
        );
    }
}
