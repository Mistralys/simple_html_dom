<?php

declare(strict_types=1);

namespace Tests\DOM;

use PHPUnit\Framework\TestCase;

class DomTreeTest extends TestCase
{
    private \simple_html_dom $html;

    protected function setUp(): void
    {
        $this->html = new \simple_html_dom();
    }

    protected function tearDown(): void
    {
        $this->html->clear();
        unset($this->html);
    }

    // -------------------------------------------------------------------------
    // Empty DOM tree traversal

    public function testEmptyDomTreeTraversal(): void
    {
        $this->html->load('', true, false);
        $e = $this->html->root;
        $this->assertNull($e->first_child());
        $this->assertNull($e->last_child());
        $this->assertNull($e->next_sibling());
        $this->assertNull($e->prev_sibling());
    }

    // -------------------------------------------------------------------------
    // Single div element

    public function testSingleDivElementNavigation(): void
    {
        $str = '<div id="div1"></div>';
        $this->html->load($str, true, false);

        $e = $this->html->root;
        $this->assertEquals('div1', $e->first_child()->id);
        $this->assertEquals('div1', $e->last_child()->id);
        $this->assertNull($e->next_sibling());
        $this->assertNull($e->prev_sibling());
        $this->assertEquals('', $e->plaintext);
        $this->assertEquals('<div id="div1"></div>', $e->innertext);
        $this->assertEquals($str, $e->outertext);
    }

    // -------------------------------------------------------------------------
    // Nested div elements — child navigation

    public function testNestedDivChildNavigation(): void
    {
        $str = <<<HTML
        <div id="div1">
            <div id="div10"></div>
            <div id="div11"></div>
            <div id="div12"></div>
        </div>
        HTML;

        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);

        $e = $this->html->find('div#div1', 0);
        $this->assertTrue(isset($e->id));
        $this->assertFalse(isset($e->_not_exist));
        $this->assertEquals('div10', $e->first_child()->id);
        $this->assertEquals('div12', $e->last_child()->id);
        $this->assertNull($e->next_sibling());
        $this->assertNull($e->prev_sibling());
    }

    // -------------------------------------------------------------------------
    // Sibling navigation chains

    public function testSiblingNavigation(): void
    {
        $str = <<<HTML
        <div id="div0">
            <div id="div00"></div>
        </div>
        <div id="div1">
            <div id="div10"></div>
            <div id="div11"></div>
            <div id="div12"></div>
        </div>
        <div id="div2"></div>
        HTML;

        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);

        $e = $this->html->find('div#div1', 0);
        $this->assertEquals('div10', $e->first_child()->id);
        $this->assertEquals('div12', $e->last_child()->id);
        $this->assertEquals('div2', $e->next_sibling()->id);
        $this->assertEquals('div0', $e->prev_sibling()->id);

        $e = $this->html->find('div#div2', 0);
        $this->assertNull($e->first_child());
        $this->assertNull($e->last_child());

        $e = $this->html->find('div#div0 div#div00', 0);
        $this->assertNull($e->first_child());
        $this->assertNull($e->last_child());
        $this->assertNull($e->next_sibling());
        $this->assertNull($e->prev_sibling());
    }

    // -------------------------------------------------------------------------
    // Deep nesting (4+ levels) with children()

    public function testDeepNesting(): void
    {
        $str = <<<HTML
        <div id="div0">
            <div id="div00"></div>
        </div>
        <div id="div1">
            <div id="div10"></div>
            <div id="div11">
                <div id="div110"></div>
                <div id="div111">
                    <div id="div1110"></div>
                    <div id="div1111"></div>
                    <div id="div1112"></div>
                </div>
                <div id="div112"></div>
            </div>
            <div id="div12"></div>
        </div>
        <div id="div2"></div>
        HTML;

        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);

        $this->assertEquals('div1', $this->html->find('#div1', 0)->id);
        $this->assertEquals('div10', $this->html->find('#div1', 0)->children(0)->id);
        $this->assertEquals('div111', $this->html->find('#div1', 0)->children(1)->children(1)->id);
        $this->assertEquals('div1112', $this->html->find('#div1', 0)->children(1)->children(1)->children(2)->id);
    }

    // -------------------------------------------------------------------------
    // No-value attributes (checkbox: checked, disabled)

    public function testNoValueAttributes(): void
    {
        $str = <<<HTML
        <form name="form1" method="post" action="">
            <input type="checkbox" name="checkbox0" checked value="checkbox0">aaa<br>
            <input type="checkbox" name="checkbox1" value="checkbox1">bbb<br>
            <input type="checkbox" name="checkbox2" value="checkbox2" checked>ccc<br>
        </form>
        HTML;

        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);

        // Verify via isset()
        $counter = 0;
        foreach ($this->html->find('input[type=checkbox]') as $checkbox) {
            if (isset($checkbox->checked)) {
                $this->assertEquals("checkbox{$counter}", $checkbox->value);
                $counter += 2;
            }
        }
        $this->assertEquals(4, $counter);

        // Verify via truthy value
        $counter = 0;
        foreach ($this->html->find('input[type=checkbox]') as $checkbox) {
            if ($checkbox->checked) {
                $this->assertEquals("checkbox{$counter}", $checkbox->value);
                $counter += 2;
            }
        }
        $this->assertEquals(4, $counter);

        // Modify checked attribute
        $es = $this->html->find('input[type=checkbox]');
        $es[1]->checked = true;
        $this->assertEquals(
            '<input type="checkbox" name="checkbox1" value="checkbox1" checked>',
            $es[1]->outertext
        );
        $es[0]->checked = false;
        $this->assertEquals(
            '<input type="checkbox" name="checkbox0" value="checkbox0">',
            (string) $es[0]
        );
        $es[0]->checked = true;
        $this->assertEquals(
            '<input type="checkbox" name="checkbox0" checked value="checkbox0">',
            $es[0]->outertext
        );
    }

    // -------------------------------------------------------------------------
    // Attribute removal — removeAttribute() / null assignment

    public function testAttributeRemoval(): void
    {
        $str = <<<HTML
        <input type="checkbox" name="checkbox0">
        <input type = "checkbox" name = 'checkbox1' value = "checkbox1">
        HTML;

        // Sequence 1: checkbox0 — remove name then type
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $e = $this->html->find('[name=checkbox0]', 0);
        $e->name = null;
        $this->assertEquals('<input type="checkbox">', (string) $e);
        $e->type = null;
        $this->assertEquals('<input>', (string) $e);

        // Sequence 2: checkbox0 — same order (repeat from original test)
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $e = $this->html->find('[name=checkbox0]', 0);
        $e->name = null;
        $this->assertEquals('<input type="checkbox">', (string) $e);
        $e->type = null;
        $this->assertEquals('<input>', (string) $e);

        // Sequence 3: checkbox1 — remove value, type, name
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $e = $this->html->find('[name=checkbox1]', 0);
        $e->value = null;
        $this->assertEquals("<input type = \"checkbox\" name = 'checkbox1'>", (string) $e);
        $e->type = null;
        $this->assertEquals("<input name = 'checkbox1'>", (string) $e);
        $e->name = null;
        $this->assertEquals('<input>', (string) $e);

        // Sequence 4: checkbox1 — remove type, name, value
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $e = $this->html->find('[name=checkbox1]', 0);
        $e->type = null;
        $this->assertEquals("<input name = 'checkbox1' value = \"checkbox1\">", (string) $e);
        $e->name = null;
        $this->assertEquals('<input value = "checkbox1">', (string) $e);
        $e->value = null;
        $this->assertEquals('<input>', (string) $e);
    }

    // -------------------------------------------------------------------------
    // Remove no-value attribute (checked)

    public function testRemoveNoValueAttribute(): void
    {
        $str = <<<HTML
        <input type="checkbox" checked name='checkbox0'>
        <input type="checkbox" name='checkbox1' checked>
        HTML;

        // checkbox1: remove type, name, checked
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $e = $this->html->find('[name=checkbox1]', 0);
        $e->type = null;
        $this->assertEquals("<input name='checkbox1' checked>", (string) $e);
        $e->name = null;
        $this->assertEquals('<input checked>', (string) $e);
        $e->checked = null;
        $this->assertEquals('<input>', (string) $e);

        // checkbox0: remove type, name, checked
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $e = $this->html->find('[name=checkbox0]', 0);
        $e->type = null;
        $this->assertEquals("<input checked name='checkbox0'>", (string) $e);
        $e->name = null;
        $this->assertEquals('<input checked>', (string) $e);
        $e->checked = null;
        $this->assertEquals('<input>', (string) $e);

        // checkbox0: remove checked, name, type
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $e = $this->html->find('[name=checkbox0]', 0);
        $e->checked = null;
        $this->assertEquals("<input type=\"checkbox\" name='checkbox0'>", (string) $e);
        $e->name = null;
        $this->assertEquals('<input type="checkbox">', (string) $e);
        $e->type = null;
        $this->assertEquals('<input>', (string) $e);
    }

    // -------------------------------------------------------------------------
    // Plaintext extraction

    public function testPlaintextExtraction(): void
    {
        // Simple bold tag — trailing \n from heredoc becomes a text node with stripRN=false
        $str = "<b>okok</b>\n";
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $this->assertEquals("okok\n", $this->html->plaintext);

        // Bold inside div
        $str = "<div><b>okok</b></div>\n";
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $this->assertEquals("okok\n", $this->html->plaintext);

        // Unclosed div
        $str = "<div><b>okok</b>\n";
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $this->assertEquals("okok\n", $this->html->plaintext);

        // Orphan closing div — renders in plaintext
        $str = "<b>okok</b></div>\n";
        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $this->assertEquals("okok</div>\n", $this->html->plaintext);
    }

    // -------------------------------------------------------------------------
    // CamelCase DOM API aliases

    public function testCamelCaseDomApi(): void
    {
        $str = <<<HTML
        <input type="checkbox" id="checkbox" name="checkbox" value="checkbox" checked>
        <input type="checkbox" id="checkbox1" name="checkbox1" value="checkbox1">
        <input type="checkbox" id="checkbox2" name="checkbox2" value="checkbox2" checked>
        HTML;

        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);

        $this->assertTrue($this->html->getElementByTagName('input')->hasAttribute('checked'));
        $this->assertFalse($this->html->getElementsByTagName('input', 1)->hasAttribute('checked'));
        $this->assertFalse($this->html->getElementsByTagName('input', 1)->hasAttribute('not_exist'));

        $this->assertEquals(
            $this->html->find('input', 0)->value,
            $this->html->getElementByTagName('input')->getAttribute('value')
        );
        $this->assertEquals(
            $this->html->find('input', 1)->value,
            $this->html->getElementsByTagName('input', 1)->getAttribute('value')
        );

        $this->assertEquals(
            $this->html->find('#checkbox1', 0)->value,
            $this->html->getElementById('checkbox1')->getAttribute('value')
        );
        $this->assertEquals(
            $this->html->find('#checkbox2', 0)->value,
            $this->html->getElementsById('checkbox2', 0)->getAttribute('value')
        );

        $e = $this->html->find('[name=checkbox]', 0);
        $this->assertEquals('checkbox', $e->getAttribute('value'));
        $this->assertTrue($e->getAttribute('checked') == true);
        $this->assertEquals('', $e->getAttribute('not_exist'));

        $e->setAttribute('value', 'okok');
        $this->assertEquals(
            '<input type="checkbox" id="checkbox" name="checkbox" value="okok" checked>',
            (string) $e
        );

        $e->setAttribute('checked', false);
        $this->assertEquals(
            '<input type="checkbox" id="checkbox" name="checkbox" value="okok">',
            (string) $e
        );

        $e->setAttribute('checked', true);
        $this->assertEquals(
            '<input type="checkbox" id="checkbox" name="checkbox" value="okok" checked>',
            (string) $e
        );

        $e->removeAttribute('value');
        $this->assertEquals(
            '<input type="checkbox" id="checkbox" name="checkbox" checked>',
            (string) $e
        );

        $e->removeAttribute('checked');
        $this->assertEquals(
            '<input type="checkbox" id="checkbox" name="checkbox">',
            (string) $e
        );
    }

    // -------------------------------------------------------------------------
    // CamelCase navigation aliases: firstChild, lastChild, nextSibling, previousSibling

    public function testCamelCaseNavigation(): void
    {
        $str = <<<HTML
        <div id="div1">
            <div id="div10"></div>
            <div id="div11"></div>
            <div id="div12"></div>
        </div>
        HTML;

        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);

        $e = $this->html->find('div#div1', 0);
        $this->assertEquals('div10', $e->firstChild()->getAttribute('id'));
        $this->assertEquals('div12', $e->lastChild()->getAttribute('id'));
        $this->assertNull($e->nextSibling());
        $this->assertNull($e->previousSibling());
    }

    // -------------------------------------------------------------------------
    // childNodes() deep navigation and getElementById / getElementsById

    public function testChildNodesNavigation(): void
    {
        $str = <<<HTML
        <div id="div0">
            <div id="div00"></div>
        </div>
        <div id="div1">
            <div id="div10"></div>
            <div id="div11">
                <div id="div110"></div>
                <div id="div111">
                    <div id="div1110"></div>
                    <div id="div1111"></div>
                    <div id="div1112"></div>
                </div>
                <div id="div112"></div>
            </div>
            <div id="div12"></div>
        </div>
        <div id="div2"></div>
        HTML;

        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);

        $this->assertTrue($this->html->getElementById('div1')->hasAttribute('id'));
        $this->assertFalse($this->html->getElementById('div1')->hasAttribute('not_exist'));

        $this->assertEquals('div1', $this->html->getElementById('div1')->getAttribute('id'));
        $this->assertEquals('div10', $this->html->getElementById('div1')->childNodes(0)->getAttribute('id'));
        $this->assertEquals('div111', $this->html->getElementById('div1')->childNodes(1)->childNodes(1)->getAttribute('id'));
        $this->assertEquals('div1112', $this->html->getElementById('div1')->childNodes(1)->childNodes(1)->childNodes(2)->getAttribute('id'));

        $this->assertEquals('div11', $this->html->getElementsById('div1', 0)->childNodes(1)->id);
        $this->assertEquals('div111', $this->html->getElementsById('div1', 0)->childNodes(1)->childNodes(1)->getAttribute('id'));
        $this->assertEquals('div1111', $this->html->getElementsById('div1', 0)->childNodes(1)->childNodes(1)->childNodes(1)->getAttribute('id'));
    }

    // -------------------------------------------------------------------------
    // List structure parsing

    public function testListStructureParsing(): void
    {
        // Malformed HTML: orphan </li> tags before nested <ul>
        $str = <<<HTML
        <ul class="menublock">
            </li>
                <ul>
                    <li>
                        <a href="http://www.cyberciti.biz/tips/pollsarchive">Polls Archive</a>
                    </li>
                </ul>
            </li>
        </ul>
        HTML;

        $this->html->load($str, true, false);
        $ul = $this->html->find('ul', 0);
        $this->assertSame('ul', $ul->first_child()->tag);

        // Well-formed nested list
        $str = <<<HTML
        <ul>
            <li>Item 1 
                <ul>
                    <li>Sub Item 1 </li>
                    <li>Sub Item 2 </li>
                </ul>
            </li>
            <li>Item 2 </li>
        </ul>
        HTML;

        $this->html->load($str, true, false);
        $this->assertEquals($str, (string) $this->html);
        $ul = $this->html->find('ul', 0);
        $this->assertSame('li', $ul->first_child()->tag);
        $this->assertSame('li', $ul->first_child()->next_sibling()->tag);
    }
}
