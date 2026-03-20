<?php

declare(strict_types=1);

namespace Tests\Parsing;

use PHPUnit\Framework\TestCase;

/**
 * Adapted from testcase/invalid_testcase.php.
 * Covers self-closing tags, optional closing tags, broken nesting, invalid < / > characters,
 * malformed attributes, and severely broken HTML.
 */
class InvalidHtmlTest extends TestCase
{
    private ?\simple_html_dom $dom = null;

    protected function setUp(): void
    {
        $this->dom = new \simple_html_dom();
    }

    protected function tearDown(): void
    {
        $this->dom?->clear();
        $this->dom = null;
    }

    // -------------------------------------------------------------------------
    // Self-closing tags — attribute injection after load

    public function testSelfClosingHr(): void
    {
        $this->dom->load('<hr>');
        $e = $this->dom->find('hr', 0);
        $e->id = 'foo';
        $this->assertEquals('<hr id="foo">', $e->outertext);

        $this->dom->load('<hr/>');
        $e = $this->dom->find('hr', 0);
        $e->id = 'foo';
        $this->assertEquals('<hr id="foo"/>', $e->outertext);

        $this->dom->load('<hr />');
        $e = $this->dom->find('hr', 0);
        $e->id = 'foo';
        $this->assertEquals('<hr id="foo" />', $e->outertext);
    }

    public function testSelfClosingHrTwoAttributes(): void
    {
        $this->dom->load('<hr>');
        $e = $this->dom->find('hr', 0);
        $e->id = 'foo';
        $e->class = 'bar';
        $this->assertEquals('<hr id="foo" class="bar">', $e->outertext);

        $this->dom->load('<hr/>');
        $e = $this->dom->find('hr', 0);
        $e->id = 'foo';
        $e->class = 'bar';
        $this->assertEquals('<hr id="foo" class="bar"/>', $e->outertext);

        $this->dom->load('<hr />');
        $e = $this->dom->find('hr', 0);
        $e->id = 'foo';
        $e->class = 'bar';
        $this->assertEquals('<hr id="foo" class="bar" />', $e->outertext);
    }

    public function testSelfClosingHrExistingUnquotedAttr(): void
    {
        $this->dom->load('<hr id="foo" kk=ll>');
        $e = $this->dom->find('hr', 0);
        $e->class = 'bar';
        $this->assertEquals('<hr id="foo" kk=ll class="bar">', $e->outertext);

        $this->dom->load('<hr id="foo" kk="ll"/>');
        $e = $this->dom->find('hr', 0);
        $e->class = 'bar';
        $this->assertEquals('<hr id="foo" kk="ll" class="bar"/>', $e->outertext);

        $this->dom->load('<hr id="foo" kk=ll />');
        $e = $this->dom->find('hr', 0);
        $e->class = 'bar';
        $this->assertEquals('<hr id="foo" kk=ll class="bar" />', $e->outertext);
    }

    public function testNobRInsideDiv(): void
    {
        $this->dom->load('<div><nobr></div>');
        $e = $this->dom->find('nobr', 0);
        $this->assertEquals('<nobr>', $e->outertext);
    }

    // -------------------------------------------------------------------------
    // Optional closing tags — <tr> / <td>

    public function testOptionalClosingTagsTr(): void
    {
        $str = <<<HTML
<table>
<tr><td>1<td>2<td>3
</table>
HTML;
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertEquals($str, (string) $this->dom);
        $this->assertCount(3, $this->dom->find('td'));
        $this->assertEquals('1', $this->dom->find('td', 0)->innertext);
        $this->assertEquals('<td>1', $this->dom->find('td', 0)->outertext);
        $this->assertEquals('2', $this->dom->find('td', 1)->innertext);
        $this->assertEquals('<td>2', $this->dom->find('td', 1)->outertext);
        $this->assertEquals("3\n", $this->dom->find('td', 2)->innertext);
        $this->assertEquals("<td>3\n", $this->dom->find('td', 2)->outertext);
    }

    public function testOptionalClosingTagsTdInFullTable(): void
    {
        $str = <<<HTML
<table>
<tr>
    <td><b>1</b></td>
    <td><b>2</b></td>
    <td><b>3</b></td>
</table>
HTML;
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertCount(3, $this->dom->find('tr td'));
    }

    public function testOptionalClosingTagsMultiRow(): void
    {
        $str = <<<HTML
<table>
<tr><td><b>11</b></td><td><b>12</b></td><td><b>13</b></td>
<tr><td><b>21</b></td><td><b>32</b></td><td><b>43</b></td>
</table>
HTML;
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertCount(2, $this->dom->find('tr'));
        $this->assertCount(6, $this->dom->find('tr td'));
        $this->assertEquals("<tr><td><b>21</b></td><td><b>32</b></td><td><b>43</b></td>\n", $this->dom->find('tr', 1)->outertext);
        $this->assertEquals("<td><b>21</b></td><td><b>32</b></td><td><b>43</b></td>\n", $this->dom->find('tr', 1)->innertext);
        $this->assertEquals("213243\n", $this->dom->find('tr', 1)->plaintext);
    }

    // -------------------------------------------------------------------------
    // Optional closing tags — <p>

    public function testOptionalClosingTagsP(): void
    {
        $str = "<p>1\r\n<p>2</p>\r\n<p>3";
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertCount(3, $this->dom->find('p'));
        $this->assertEquals("1\r\n", $this->dom->find('p', 0)->innertext);
        $this->assertEquals("<p>1\r\n", $this->dom->find('p', 0)->outertext);
        $this->assertEquals('2', $this->dom->find('p', 1)->innertext);
        $this->assertEquals('<p>2</p>', $this->dom->find('p', 1)->outertext);
        $this->assertEquals('3', $this->dom->find('p', 2)->innertext);
        $this->assertEquals('<p>3', $this->dom->find('p', 2)->outertext);
    }

    // -------------------------------------------------------------------------
    // Optional closing tags — <nobr>

    public function testOptionalClosingTagsNobr(): void
    {
        $str = "<nobr>1\r\n<nobr>2</nobr>\r\n<nobr>3";
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertCount(3, $this->dom->find('nobr'));
        $this->assertEquals("1\r\n", $this->dom->find('nobr', 0)->innertext);
        $this->assertEquals("<nobr>1\r\n", $this->dom->find('nobr', 0)->outertext);
        $this->assertEquals('2', $this->dom->find('nobr', 1)->innertext);
        $this->assertEquals('<nobr>2</nobr>', $this->dom->find('nobr', 1)->outertext);
        $this->assertEquals('3', $this->dom->find('nobr', 2)->innertext);
        $this->assertEquals('<nobr>3', $this->dom->find('nobr', 2)->outertext);
    }

    // -------------------------------------------------------------------------
    // Optional closing tags — <dt> / <dd>

    public function testOptionalClosingTagsDtDd(): void
    {
        $str = '<dl><dt>1<dd>2<dt>3<dd>4</dl>';
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertCount(2, $this->dom->find('dt'));
        $this->assertCount(2, $this->dom->find('dd'));
        $this->assertEquals('1', $this->dom->find('dt', 0)->innertext);
        $this->assertEquals('<dt>1', $this->dom->find('dt', 0)->outertext);
        $this->assertEquals('3', $this->dom->find('dt', 1)->innertext);
        $this->assertEquals('<dt>3', $this->dom->find('dt', 1)->outertext);
        $this->assertEquals('2', $this->dom->find('dd', 0)->innertext);
        $this->assertEquals('<dd>2', $this->dom->find('dd', 0)->outertext);
        $this->assertEquals('4', $this->dom->find('dd', 1)->innertext);
        $this->assertEquals('<dd>4', $this->dom->find('dd', 1)->outertext);
    }

    public function testOptionalClosingTagsMultipleDlLists(): void
    {
        $str = "<dl id=\"dl1\"><dt>11<dd>12<dt>13<dd>14</dl>\r\n<dl id=\"dl2\"><dt>21<dd>22<dt>23<dd>24</dl>";
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertCount(2, $this->dom->find('#dl1 dt'));
        $this->assertCount(2, $this->dom->find('#dl2  dd'));
        $this->assertEquals('<dt>11<dd>12<dt>13<dd>14', $this->dom->find('dl', 0)->innertext);
        $this->assertEquals('<dt>21<dd>22<dt>23<dd>24', $this->dom->find('dl', 1)->innertext);
    }

    // -------------------------------------------------------------------------
    // Optional closing tags — <li>

    public function testOptionalClosingTagsLi(): void
    {
        $str = '<ul id="ul1"><li><b>1</b><li><b>2</b></ul>' . "\r\n" . '<ul id="ul2"><li><b>3</b><li><b>4</b></ul>';
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertCount(2, $this->dom->find('ul[id=ul1] li'));
    }

    // -------------------------------------------------------------------------
    // Broken nesting / extra closing tags

    public function testBrokenNestingImgWithCloseTag(): void
    {
        $str = <<<HTML
<div>
    <div class="class0" id="id0" >
    <img class="class0" id="id0" src="src0">
    </img>
    <img class="class0" id="id0" src="src0">
    </div>
</div>
HTML;
        $this->dom->load($str, true, false);
        $this->assertCount(2, $this->dom->find('img'));
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testBrokenNestingExtraCloseSpan(): void
    {
        $str = <<<HTML
<div>
    <div class="class0" id="id0" >
    <span></span>
    </span>
    <span></span>
    </div>
</div>
HTML;
        $this->dom->load($str, true, false);
        $this->assertCount(2, $this->dom->find('span'));
        $this->assertCount(2, $this->dom->find('div'));
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testBrokenNestingUnclosedNestedSpan(): void
    {
        $str = <<<HTML
<div>
    <div class="class0" id="id0" >
    <span></span>
    <span>
    <span></span>
    </div>
</div>
HTML;
        $this->dom->load($str, true, false);
        $this->assertCount(3, $this->dom->find('span'));
        $this->assertCount(2, $this->dom->find('div'));
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testBrokenNestingMisplacedLiClose(): void
    {
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
        $this->dom->load($str, true, false);
        $this->assertCount(2, $this->dom->find('ul'));
        $this->assertCount(1, $this->dom->find('ul ul'));
        $this->assertCount(1, $this->dom->find('li'));
        $this->assertCount(1, $this->dom->find('a'));
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testBrokenNestingExtraCloseSpanInTd(): void
    {
        $str = <<<HTML
<td>
    <div>
        </span>
    </div>
</td>
HTML;
        $this->dom->load($str, true, false);
        $this->assertCount(1, $this->dom->find('td'));
        $this->assertCount(1, $this->dom->find('div'));
        $this->assertCount(1, $this->dom->find('td div'));
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testBrokenNestingExtraCloseBInTd(): void
    {
        $str = <<<HTML
<td>
    <div>
        </b>
    </div>
</td>
HTML;
        $this->dom->load($str, true, false);
        $this->assertCount(1, $this->dom->find('td'));
        $this->assertCount(1, $this->dom->find('div'));
        $this->assertCount(1, $this->dom->find('td div'));
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testBrokenNestingExtraCloseDivInTd(): void
    {
        $str = <<<HTML
<td>
    <div></div>
    </div>
</td>
HTML;
        $this->dom->load($str, true, false);
        $this->assertCount(1, $this->dom->find('td'));
        $this->assertCount(1, $this->dom->find('div'));
        $this->assertCount(1, $this->dom->find('td div'));
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testBrokenNestingSpanInTableRow(): void
    {
        $str = <<<HTML
<html>
    <body>
        <table>
            <tr>
                foo</span>
                <span>bar</span>
                </span>important
            </tr>
        </table>
    </bod>
</html>
HTML;
        $this->dom->load($str, true, false);
        $this->assertCount(1, $this->dom->find('table span'));
        $this->assertEquals('bar', $this->dom->find('table span', 0)->innertext);
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testBrokenNestingDeepUnclosedTags(): void
    {
        $str = <<<HTML
<td>
    <div>
        <font>
            <b>foo</b>
    </div>
</td>
HTML;
        $this->dom->load($str, true, false);
        $this->assertCount(1, $this->dom->find('td div font b'));
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testBrokenNestingDeeplyNestedSpan(): void
    {
        $str = <<<'HTML'
<span style="okokok">
... then slow into 287 
    <i> 
        <b> 
            <font color="#0000CC">(hanover0...more volume between 202 & 53 
            <i> 
                <b> 
                    <font color="#0000CC">(parsippany)</font> 
                </b>
            </i>
            ...then sluggish in spots out to dover chester road 
            <i> 
                <b> 
                    <font color="#0000CC">(randolph)</font> 
                </b> 
            </i>..then traffic light delays out to route 46 
            <i> 
                <b> 
                    <font color="#0000CC">(roxbury)</font> 
                </b> 
            </i>/eb slow into 202 
            <i> 
                <b> 
                    <font color="#0000CC">(morris plains)</font> 
                </b> 
            </i> & again into 287 
            <i> 
                <b> 
                    <font color="#0000CC">(hanover)</font>
                </b> 
            </i> 
</span>. 
<td class="d N4 c">52</td> 
HTML;
        $this->dom->load($str, true, false);
        $this->assertCount(0, $this->dom->find('span td'));
        $this->assertEquals($str, (string) $this->dom);
    }

    // -------------------------------------------------------------------------
    // Optional closing tags — body level

    public function testBodyWithInvalidClosingTags(): void
    {
        $str = <<<HTML
<body>
</b><.b></a>
</body>
HTML;
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertEquals($str, $this->dom->find('body', 0)->outertext);
    }

    public function testBodyWithUnclosedAnchors(): void
    {
        $str = <<<HTML
<html>
    <body>
        <a>foo</a>
        <a>foo2</a>
HTML;
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertEquals($str, (string) $this->dom);
        $this->assertEquals('foo2', $this->dom->find('html body a', 1)->innertext);
    }

    public function testBodyWithDivNoClose(): void
    {
        $str = <<<HTML
<body>
<div>
</body>
HTML;
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertEquals($str, (string) $this->dom);
        $this->assertEquals($str, $this->dom->find('body', 0)->outertext);
    }

    public function testBodyWithDivAndStrayClose(): void
    {
        $str = <<<HTML
<body>
<div> </a> </div>
</body>
HTML;
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertEquals($str, $this->dom->find('body', 0)->outertext);
    }

    public function testTableWithUnclosedRows(): void
    {
        $str = <<<HTML
<table>
    <tr>
        <td><b>aa</b>
    <tr>
        <td><b>bb</b>
</table>
HTML;
        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $this->assertEquals($str, (string) $this->dom);
    }

    // -------------------------------------------------------------------------
    // Invalid '<' characters in text content

    public function testInvalidLessThanInText(): void
    {
        $cases = [
            '<td><b>test :</b>1 gram but <5 grams</td>',
            '<td><b>test :</b>1 gram but<5 grams</td>',
            '<td><b>test :</b>1 gram but< 5 grams</td>',
            '<td><b>test :</b>1 gram but < 5 grams</td>',
            '<td><b>test :</b>1 gram but 5< grams</td>',
            '<td><b>test :</b>1 gram but 5 < grams</td>',
            '<td><b>test :</b>1 gram but 5 <grams</td>',
            '<td><b>test :</b>1 gram but5< grams</td>',
            '<td><b>test :</b>1 gram but 5<grams</td>',
        ];
        foreach ($cases as $html) {
            $this->dom->load($html);
            // innertext preserves the '<' and surrounding text within <td>
            $this->assertStringContainsString('<', $this->dom->find('td', 0)->innertext, "innertext for: $html");
            $this->assertEquals($html, (string) $this->dom, "round-trip for: $html");
        }
    }

    // -------------------------------------------------------------------------
    // Invalid '>' characters in text content

    public function testInvalidGreaterThanInText(): void
    {
        $cases = [
            '<td><b>test :</b>1 gram but >5 grams</td>',
            '<td><b>test :</b>1 gram but>5 grams</td>',
            '<td><b>test :</b>1 gram but> 5 grams</td>',
            '<td><b>test :</b>1 gram but > 5 grams</td>',
            '<td><b>test :</b>1 gram but 5> grams</td>',
            '<td><b>test :</b>1 gram but 5 > grams</td>',
            '<td><b>test :</b>1 gram but 5 >grams</td>',
            '<td><b>test :</b>1 gram but5> grams</td>',
            '<td><b>test :</b>1 gram but 5>grams</td>',
        ];
        foreach ($cases as $html) {
            $this->dom->load($html);
            $this->assertStringContainsString('>', $this->dom->find('td', 0)->innertext, "innertext for: $html");
            $this->assertEquals($html, (string) $this->dom, "round-trip for: $html");
        }
    }

    // -------------------------------------------------------------------------
    // BAD HTML — severely broken markup — just verify no exception is thrown

    public function testBadHtmlDoesNotThrow(): void
    {
        $badStrings = [
            '<strong class="see <a href="http://www.oeb.harvard.edu/faculty/girguis/">http://www.oeb.harvard.edu/faculty/girguis/</a>">.</strong></p> ',
            '<a href="http://www.oeb.harvard.edu/faculty/girguis\\">http://www.oeb.harvard.edu/faculty/girguis/</a>">',
            '<strong class="\'\'\"\"\";;\'\'\"\"\";;\"\"\'\'\'\'\"\"\"\'\'\'\'\'\"\"\'\'">\"\"\'\'\'\"\'"\' ',
        ];
        foreach ($badStrings as $html) {
            $this->dom->load($html);
            $this->assertIsString((string) $this->dom);
        }
    }
}
