<?php

declare(strict_types=1);

namespace Tests\DOM;

use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase
{
    private \simple_html_dom $dom;

    protected function setUp(): void
    {
        $this->dom = new \simple_html_dom();
    }

    protected function tearDown(): void
    {
        $this->dom->clear();
        unset($this->dom);
    }

    // -------------------------------------------------------------------------
    // innertext tests

    public function testInnertext(): void
    {
        // Round-trip with foo span
        $str = <<<HTML
        <html>
            <head></head>
            <body>
                <br>
                <span>foo</span>
            </body>
        </html>
        HTML;
        $this->dom->load($str, true, false);
        $this->assertEquals($str, (string) $this->dom);

        // Round-trip after setting span innertext (already 'bar' in str)
        $str = <<<HTML
        <html>
            <head></head>
            <body>
                <br>
                <span>bar</span>
            </body>
        </html>
        HTML;
        $this->dom->load($str, true, false);
        $this->dom->find('span', 0)->innertext = 'bar';
        $this->assertEquals($str, (string) $this->dom);

        // Round-trip after setting head innertext (already 'ok' in str)
        $str = <<<HTML
        <html>
            <head>ok</head>
            <body>
                <br>
                <span>bar</span>
            </body>
        </html>
        HTML;
        $this->dom->load($str, true, false);
        $this->dom->find('head', 0)->innertext = 'ok';
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testInnertextTextNode(): void
    {
        $this->dom->load('<b>foo</b>');

        $e = $this->dom->find('b text', 0);
        $this->assertEquals('foo', $e->innertext);
        $this->assertEquals('foo', $e->outertext);

        $e->innertext = 'bar';
        $this->assertEquals('bar', $e->innertext);
        $this->assertEquals('bar', $e->outertext);

        $e = $this->dom->find('b', 0);
        $this->assertEquals('bar', $e->innertext);
        $this->assertEquals('<b>bar</b>', $e->outertext);
    }

    // -------------------------------------------------------------------------
    // outertext tests — tables

    public function testOutertextTable(): void
    {
        // Well-formed table with closing </tr>
        $str = <<<HTML
        <table>
        <tr><th>Head1</th><th>Head2</th><th>Head3</th></tr>
        <tr><td>1</td><td>2</td><td>3</td></tr>
        </table>
        HTML;
        $this->dom->load($str, true, false);
        $this->assertEquals('<tr><th>Head1</th><th>Head2</th><th>Head3</th></tr>', $this->dom->find('tr', 0)->outertext);
        $this->assertEquals('<tr><td>1</td><td>2</td><td>3</td></tr>', $this->dom->find('tr', 1)->outertext);

        // Table without closing </tr> — outertext excludes next row
        $this->dom->load('<table><tr><th>Head1</th><th>Head2</th><th>Head3</th><tr><td>1</td><td>2</td><td>3</td></table>');
        $this->assertEquals('<tr><th>Head1</th><th>Head2</th><th>Head3</th>', $this->dom->find('tr', 0)->outertext);
        $this->assertEquals('<tr><td>1</td><td>2</td><td>3</td>', $this->dom->find('tr', 1)->outertext);
    }

    // -------------------------------------------------------------------------
    // outertext tests — lists

    public function testOutertextList(): void
    {
        // Two separate, well-formed lists
        $this->dom->load('<ul><li><b>li11</b></li><li><b>li12</b></li></ul><ul><li><b>li21</b></li><li><b>li22</b></li></ul>');
        $this->assertEquals('<ul><li><b>li11</b></li><li><b>li12</b></li></ul>', $this->dom->find('ul', 0)->outertext);
        $this->assertEquals('<ul><li><b>li21</b></li><li><b>li22</b></li></ul>', $this->dom->find('ul', 1)->outertext);

        // Nested list without closing tags
        $this->dom->load('<ul><li><b>li11</b></li><li><b>li12</b></li><ul><li><b>li21</b></li><li><b>li22</b></li>');
        $this->assertEquals('<ul><li><b>li11</b></li><li><b>li12</b></li><ul><li><b>li21</b></li><li><b>li22</b></li>', $this->dom->find('ul', 0)->outertext);
        $this->assertEquals('<ul><li><b>li21</b></li><li><b>li22</b></li>', $this->dom->find('ul', 1)->outertext);

        // Nested list with missing </li>
        $this->dom->load('<ul><li><b>li11</b><li><b>li12</b></li><ul><li><b>li21</b></li><li><b>li22</b>');
        $this->assertEquals('<ul><li><b>li11</b><li><b>li12</b></li><ul><li><b>li21</b></li><li><b>li22</b>', $this->dom->find('ul', 0)->outertext);
        $this->assertEquals('<ul><li><b>li21</b></li><li><b>li22</b>', $this->dom->find('ul', 1)->outertext);

        // Repeated table test (from same section in source)
        $str = <<<HTML
        <table>
        <tr><th>Head1</th><th>Head2</th><th>Head3</th></tr>
        <tr><td>1</td><td>2</td><td>3</td></tr>
        </table>
        HTML;
        $this->dom->load($str, true, false);
        $this->assertEquals('<tr><th>Head1</th><th>Head2</th><th>Head3</th></tr>', $this->dom->find('tr', 0)->outertext);
        $this->assertEquals('<tr><td>1</td><td>2</td><td>3</td></tr>', $this->dom->find('tr', 1)->outertext);
    }

    // -------------------------------------------------------------------------
    // element replacement via attribute/tag mutation

    public function testReplacement(): void
    {
        $str = '<div class="class1" id="id2" ><div class="class2">ok</div></div>';
        $this->dom->load($str);
        $es = $this->dom->find('div');

        $this->assertCount(2, $es);
        $this->assertEquals('<div class="class2">ok</div>', $es[0]->innertext);
        $this->assertEquals('<div class="class1" id="id2" ><div class="class2">ok</div></div>', $es[0]->outertext);

        // isset on existing and non-existing attribute
        $es[0]->class = 'class_test';
        $this->assertTrue(isset($es[0]->class));
        $this->assertFalse(isset($es[0]->okok));

        // Modify class
        $es[0]->class = 'class_test';
        $this->assertEquals('<div class="class_test" id="id2" ><div class="class2">ok</div></div>', $es[0]->outertext);

        // Modify tag
        $es[0]->tag = 'span';
        $this->assertEquals('<span class="class_test" id="id2" ><div class="class2">ok</div></span>', $es[0]->outertext);

        // Remove attribute via unset on attr array
        $this->dom->load($str);
        $es = $this->dom->find('div');
        unset($es[0]->attr['class']);
        $this->assertEquals('<div id="id2" ><div class="class2">ok</div></div>', $es[0]->outertext);
    }

    public function testSelectInnertext(): void
    {
        $this->dom->load('<select name=something><options>blah</options><options>blah2</options></select>');
        $e = $this->dom->find('select[name=something]', 0);
        $e->innertext = '';
        $this->assertEquals('<select name=something></select>', $e->outertext);
    }

    // -------------------------------------------------------------------------
    // nested element replacement

    public function testNestedReplacement(): void
    {
        $str = '<div class="class0" id="id0" ><div class="class1">ok</div></div>';
        $this->dom->load($str);
        $es = $this->dom->find('div');

        $this->assertCount(2, $es);
        $this->assertEquals('<div class="class1">ok</div>', $es[0]->innertext);
        $this->assertEquals('<div class="class0" id="id0" ><div class="class1">ok</div></div>', $es[0]->outertext);
        $this->assertEquals('ok', $es[1]->innertext);
        $this->assertEquals('<div class="class1">ok</div>', $es[1]->outertext);

        // Modify inner div innertext — changes propagate to parent
        $es[1]->innertext = 'okok';
        $this->assertEquals('<div class="class1">okok</div>', $es[1]->outertext);
        $this->assertEquals('<div class="class0" id="id0" ><div class="class1">okok</div></div>', $es[0]->outertext);
        $this->assertEquals('<div class="class0" id="id0" ><div class="class1">okok</div></div>', (string) $this->dom);

        // Modify inner div class
        $es[1]->class = 'class_test';
        $this->assertEquals('<div class="class_test">okok</div>', $es[1]->outertext);
        $this->assertEquals('<div class="class0" id="id0" ><div class="class_test">okok</div></div>', $es[0]->outertext);
        $this->assertEquals('<div class="class0" id="id0" ><div class="class_test">okok</div></div>', (string) $this->dom);

        // Modify outer div class
        $es[0]->class = 'class_test';
        $this->assertEquals('<div class="class_test" id="id0" ><div class="class_test">okok</div></div>', $es[0]->outertext);
        $this->assertEquals('<div class="class_test" id="id0" ><div class="class_test">okok</div></div>', (string) $this->dom);

        // Replace outer div innertext entirely
        $es[0]->innertext = 'okokok';
        $this->assertEquals('<div class="class_test" id="id0" >okokok</div>', $es[0]->outertext);
        $this->assertEquals('<div class="class_test" id="id0" >okokok</div>', (string) $this->dom);
    }

    // -------------------------------------------------------------------------
    // <p> tag parsing

    public function testParagraphHandling(): void
    {
        $str = <<<HTML
        <div class="class0">
            <p>ok0<a href="#">link0</a></p>
            <div class="class1"><p>ok1<a href="#">link1</a></p></div>
            <div class="class2"></div>
            <p>ok2<a href="#">link2</a></p>
        </div>
        HTML;
        $this->dom->load($str, true, false);
        $es = $this->dom->find('p');

        $this->assertEquals('ok0<a href="#">link0</a>', $es[0]->innertext);
        $this->assertEquals('ok1<a href="#">link1</a>', $es[1]->innertext);
        $this->assertEquals('ok2<a href="#">link2</a>', $es[2]->innertext);
        $this->assertEquals('ok0link0', $this->dom->find('p', 0)->plaintext);
        $this->assertEquals('ok1link1', $this->dom->find('p', 1)->plaintext);
        $this->assertEquals('ok2link2', $this->dom->find('p', 2)->plaintext);

        // Nested find on each <p>
        $count = 0;
        foreach ($this->dom->find('p') as $p) {
            $a = $p->find('a');
            $this->assertEquals('link' . $count, $a[0]->innertext);
            ++$count;
        }

        // Find 'p a' selector
        $es = $this->dom->find('p a');
        $this->assertEquals('link0', $es[0]->innertext);
        $this->assertEquals('link1', $es[1]->innertext);
        $this->assertEquals('link2', $es[2]->innertext);
        $this->assertEquals('link0', $this->dom->find('p a', 0)->plaintext);
        $this->assertEquals('link1', $this->dom->find('p a', 1)->plaintext);
        $this->assertEquals('link2', $this->dom->find('p a', 2)->plaintext);

        $this->assertEquals($str, (string) $this->dom);
    }

    // -------------------------------------------------------------------------
    // <embed> tag

    public function testEmbedTag(): void
    {
        $str = <<<HTML
        <EMBED 
           SRC="../graphics/sounds/1812over.mid"
           HEIGHT=60 WIDTH=144>
        HTML;
        $this->dom->load($str, true, false);
        $e = $this->dom->find('embed', 0);

        $this->assertEquals('../graphics/sounds/1812over.mid', $e->src);
        $this->assertEquals('60', $e->height);
        $this->assertEquals('144', $e->width);
        $this->assertEquals(strtolower($str), (string) $this->dom);
    }
}
