<?php

declare(strict_types=1);

namespace Tests\Selectors;

use PHPUnit\Framework\TestCase;

class SelectorTest extends TestCase
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
    // Tab / newline characters inside a tag

    public function testTabNewlineInTag(): void
    {
        $str = <<<HTML
        <img 
        class="class0" id="id0" src="src0">
        <img
         class="class1" id="id1" src="src1">
        <img class="class2" id="id2" src="src2">
        HTML;
        $this->dom->load($str);
        $this->assertCount(3, $this->dom->find('img'));
    }

    // -------------------------------------------------------------------------
    // Wildcard, tag, class, id, attr selectors on a common HTML fixture

    public function testWildcardSelector(): void
    {
        $str = <<<HTML
        <div>
        <img class="class0" id="id0" src="src0">
        <img class="class1" id="id1" src="src1">
        <img class="class2" id="id2" src="src2">
        </div>
        HTML;
        $this->dom->load($str);

        $this->assertCount(1, $this->dom->find('*'));
        $this->assertCount(3, $this->dom->find('div *'));
        $this->assertCount(0, $this->dom->find('div img *'));
        $this->assertCount(1, $this->dom->find(' * '));
        $this->assertCount(3, $this->dom->find(' div  * '));
        $this->assertCount(0, $this->dom->find(' div  img  *'));
    }

    public function testTagSelector(): void
    {
        $str = <<<HTML
        <div>
        <img class="class0" id="id0" src="src0">
        <img class="class1" id="id1" src="src1">
        <img class="class2" id="id2" src="src2">
        </div>
        HTML;
        $this->dom->load($str);

        $this->assertCount(3, $this->dom->find('img'));
        $this->assertCount(4, $this->dom->find('text'));
    }

    public function testClassSelector(): void
    {
        $str = <<<HTML
        <div>
        <img class="class0" id="id0" src="src0">
        <img class="class1" id="id1" src="src1">
        <img class="class2" id="id2" src="src2">
        </div>
        HTML;
        $this->dom->load($str);

        $es = $this->dom->find('img.class0');
        $this->assertCount(1, $es);
        $this->assertEquals('src0', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class0" id="id0" src="src0">', $es[0]->outertext);

        $es = $this->dom->find('.class0');
        $this->assertCount(1, $es);
        $this->assertEquals('src0', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class0" id="id0" src="src0">', $es[0]->outertext);
    }

    public function testIndexSelector(): void
    {
        $str = <<<HTML
        <div>
        <img class="class0" id="id0" src="src0">
        <img class="class1" id="id1" src="src1">
        <img class="class2" id="id2" src="src2">
        </div>
        HTML;
        $this->dom->load($str);

        $this->assertEquals('src0', $this->dom->find('img', 0)->src);
        $this->assertEquals('src1', $this->dom->find('img', 1)->src);
        $this->assertEquals('src2', $this->dom->find('img', 2)->src);
        $this->assertEquals('src0', $this->dom->find('img', -3)->src);
        $this->assertEquals('src1', $this->dom->find('img', -2)->src);
        $this->assertEquals('src2', $this->dom->find('img', -1)->src);
    }

    public function testIdSelector(): void
    {
        $str = <<<HTML
        <div>
        <img class="class0" id="id0" src="src0">
        <img class="class1" id="id1" src="src1">
        <img class="class2" id="id2" src="src2">
        </div>
        HTML;
        $this->dom->load($str);

        $es = $this->dom->find('img#id1');
        $this->assertCount(1, $es);
        $this->assertEquals('src1', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class1" id="id1" src="src1">', $es[0]->outertext);

        $es = $this->dom->find('#id2');
        $this->assertCount(1, $es);
        $this->assertEquals('src2', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class2" id="id2" src="src2">', $es[0]->outertext);
    }

    public function testAttrSelector(): void
    {
        $str = <<<HTML
        <div>
        <img class="class0" id="id0" src="src0">
        <img class="class1" id="id1" src="src1">
        <img class="class2" id="id2" src="src2">
        </div>
        HTML;
        $this->dom->load($str);

        $es = $this->dom->find('img[src="src0"]');
        $this->assertCount(1, $es);
        $this->assertEquals('src0', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class0" id="id0" src="src0">', $es[0]->outertext);

        $es = $this->dom->find('img[src=src0]');
        $this->assertCount(1, $es);
        $this->assertEquals('src0', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class0" id="id0" src="src0">', $es[0]->outertext);
    }

    public function testWildcardAttrSelector(): void
    {
        $str = <<<HTML
        <div>
        <img class="class0" id="id0" src="src0">
        <img class="class1" id="id1" src="src1">
        <img class="class2" id="id2" src="src2">
        </div>
        HTML;
        $this->dom->load($str);

        $this->assertCount(3, $this->dom->find('*[src]'));
        $this->assertCount(3, $this->dom->find('*[src=*]'));
        $this->assertCount(0, $this->dom->find('*[alt=*]'));

        $es = $this->dom->find('*[src="src0"]');
        $this->assertCount(1, $es);
        $this->assertEquals('src0', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class0" id="id0" src="src0">', $es[0]->outertext);

        $es = $this->dom->find('*[src=src0]');
        $this->assertCount(1, $es);
        $this->assertEquals('src0', $es[0]->src);

        $es = $this->dom->find('[src=src0]');
        $this->assertCount(1, $es);
        $this->assertEquals('src0', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class0" id="id0" src="src0">', $es[0]->outertext);

        $es = $this->dom->find('[src="src0"]');
        $this->assertCount(1, $es);
        $this->assertEquals('src0', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class0" id="id0" src="src0">', $es[0]->outertext);

        $es = $this->dom->find('*#id1');
        $this->assertCount(1, $es);
        $this->assertEquals('src1', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class1" id="id1" src="src1">', $es[0]->outertext);

        $es = $this->dom->find('*.class0');
        $this->assertCount(1, $es);
        $this->assertEquals('src0', $es[0]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('<img class="class0" id="id0" src="src0">', $es[0]->outertext);
    }

    // -------------------------------------------------------------------------
    // text selector

    public function testTextSelector(): void
    {
        $this->dom->load('<b>text1</b><b>text2</b>');
        $es = $this->dom->find('text');
        $this->assertCount(2, $es);
        $this->assertEquals('text1', $es[0]->innertext);
        $this->assertEquals('text1', $es[0]->outertext);
        $this->assertEquals('text1', $es[0]->plaintext);
        $this->assertEquals('text2', $es[1]->innertext);
        $this->assertEquals('text2', $es[1]->outertext);
        $this->assertEquals('text2', $es[1]->plaintext);

        $this->dom->load('<b>text1</b><b>text2</b>');
        $es = $this->dom->find('b text');
        $this->assertCount(2, $es);
        $this->assertEquals('text1', $es[0]->innertext);
        $this->assertEquals('text1', $es[0]->outertext);
        $this->assertEquals('text1', $es[0]->plaintext);
        $this->assertEquals('text2', $es[1]->innertext);
        $this->assertEquals('text2', $es[1]->outertext);
        $this->assertEquals('text2', $es[1]->plaintext);
    }

    // -------------------------------------------------------------------------
    // XML namespace selector

    public function testXmlNamespaceSelector(): void
    {
        $this->dom->load('<bw:bizy id="date">text</bw:bizy>');
        $es = $this->dom->find('bw:bizy');
        $this->assertCount(1, $es);
        $this->assertEquals('date', $es[0]->id);
    }

    // -------------------------------------------------------------------------
    // User-defined tag names

    public function testUserDefinedTags(): void
    {
        $this->dom->load('<div_test id="1">text</div_test>');
        $es = $this->dom->find('div_test');
        $this->assertCount(1, $es);
        $this->assertEquals('1', $es[0]->id);

        $this->dom->load('<div-test id="1">text</div-test>');
        $es = $this->dom->find('div-test');
        $this->assertCount(1, $es);
        $this->assertEquals('1', $es[0]->id);

        $this->dom->load('<div::test id="1">text</div::test>');
        $es = $this->dom->find('div::test');
        $this->assertCount(1, $es);
        $this->assertEquals('1', $es[0]->id);
    }

    // -------------------------------------------------------------------------
    // Find by attribute value across multiple tag types

    public function testFindByAttrAcrossTags(): void
    {
        $this->dom->load('<img class="class0" id="1" src="src0"> <img class="class1" id="2" src="src1"> <div class="class2" id="1">ok</div>');
        $es = $this->dom->find('[id=1]');
        $this->assertCount(2, $es);
        $this->assertEquals('img', $es[0]->tag);
        $this->assertEquals('div', $es[1]->tag);
    }

    // -------------------------------------------------------------------------
    // Multiple / descendant selectors

    public function testDescendantSelectors(): void
    {
        $str = '<div class="class0" id="id0" ><div class="class1" id="id1"><div class="class2" id="id2">ok</div><div style="st1 st2" id="id3"><span class="id4">ok</span></div></div></div>';
        $this->dom->load($str);

        $es = $this->dom->find('div');
        $this->assertCount(4, $es);
        $this->assertEquals('id0', $es[0]->id);
        $this->assertEquals('id1', $es[1]->id);
        $this->assertEquals('id2', $es[2]->id);

        $es = $this->dom->find('div div');
        $this->assertCount(3, $es);
        $this->assertEquals('id1', $es[0]->id);
        $this->assertEquals('id2', $es[1]->id);

        $es = $this->dom->find('div div div');
        $this->assertCount(2, $es);
        $this->assertEquals('id2', $es[0]->id);

        $es = $this->dom->find('.class0 .class1 .class2');
        $this->assertCount(1, $es);
        $this->assertEquals('id2', $es[0]->id);

        $es = $this->dom->find('#id0 #id1 #id2');
        $this->assertCount(1, $es);
        $this->assertEquals('id2', $es[0]->id);

        $es = $this->dom->find('div[id=id0] div[id=id1] div[id=id2]');
        $this->assertCount(1, $es);
        $this->assertEquals('id2', $es[0]->id);

        $es = $this->dom->find('div[id="id0"] div[id="id1"] div[id="id2"]');
        $this->assertCount(1, $es);
        $this->assertEquals('id2', $es[0]->id);

        $es = $this->dom->find('div[id=id0] div[id="id1"] div[id="id2"]');
        $this->assertCount(1, $es);
        $this->assertEquals('id2', $es[0]->id);

        $es = $this->dom->find('div[id="id0"] div[id=id1] div[id="id2"]');
        $this->assertCount(1, $es);
        $this->assertEquals('id2', $es[0]->id);

        $es = $this->dom->find('div[id="id0"] div[id="id1"] div[id=id2]');
        $this->assertCount(1, $es);
        $this->assertEquals('id2', $es[0]->id);

        $es = $this->dom->find("div[id='id0'] div[id='id1'] div[id='id2']");
        $this->assertCount(1, $es);
        $this->assertEquals('id2', $es[0]->id);

        $es = $this->dom->find('[id=id0] [id=id1] [id=id2]');
        $this->assertCount(1, $es);
        $this->assertEquals('id2', $es[0]->id);

        $es = $this->dom->find('[id] [id] [id]');
        $this->assertCount(2, $es);
        $this->assertEquals('id2', $es[0]->id);
        $this->assertEquals('id3', $es[1]->id);

        $es = $this->dom->find('[id=id0] [id=id1] [id=id3]');
        $this->assertCount(1, $es);
        $this->assertEquals('id3', $es[0]->id);

        $es = $this->dom->find('[id=id0] [id=id1] [style="st1 st2"]');
        $this->assertCount(1, $es);
        $this->assertEquals('id3', $es[0]->id);

        $es = $this->dom->find('[id=id0] [id=id1] [style=st1 st2]');
        $this->assertCount(1, $es);
        $this->assertEquals('id3', $es[0]->id);

        $es = $this->dom->find('[id=id0] [id=id1] [style=st1 st2] span[class=id4]');
        $this->assertCount(1, $es);
        $this->assertEquals('ok', $es[0]->innertext);

        $es = $this->dom->find('[id=id0] [id=id1] [style="st1 st2"] span[class="id4"]');
        $this->assertCount(1, $es);
        $this->assertEquals('ok', $es[0]->innertext);
    }

    public function testTableDescendantSelector(): void
    {
        $str = <<<HTML
        <table>
            <tr>
                <td>0</td>
                <td>1</td>
            </tr>
        </table>
        <table>
            <tr>
                <td>2</td>
                <td>3</td>
            </tr>
        </table>
        HTML;
        $this->dom->load($str);
        $es = $this->dom->find('table td');
        $this->assertCount(4, $es);
        $this->assertEquals('0', $es[0]->innertext);
        $this->assertEquals('1', $es[1]->innertext);
        $this->assertEquals('2', $es[2]->innertext);
        $this->assertEquals('3', $es[3]->innertext);
    }

    public function testNestedTableClassSelector(): void
    {
        $str = <<<HTML
        <table>
            <tr>
                <td>
                    <table class="hello">
                        <tr>
                            <td>0</td>
                            <td>1</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table class="hello">
            <tr>
                <td>2</td>
                <td>3</td>
            </tr>
        </table>
        HTML;
        $dom = str_get_html($str, true, true, 'UTF-8', false);
        $es = $dom->find('table.hello td');
        $this->assertCount(4, $es);
        $this->assertEquals('0', $es[0]->innertext);
        $this->assertEquals('1', $es[1]->innertext);
        $this->assertEquals('2', $es[2]->innertext);
        $this->assertEquals('3', $es[3]->innertext);
        $dom->clear();
    }

    public function testNestedSelectorWithLoop(): void
    {
        $str = <<<HTML
        <ul>
            <li>0</li>
            <li>1</li>
        </ul>
        <ul>
            <li>2</li>
            <li>3</li>
        </ul>
        HTML;
        $dom = str_get_html($str, true, true, 'UTF-8', false);
        $es = $dom->find('ul');
        $this->assertCount(2, $es);
        foreach ($es as $n) {
            $li = $n->find('li');
            $this->assertCount(2, $li);
        }

        $es = $dom->find('li');
        $this->assertCount(4, $es);
        $this->assertEquals('0', $es[0]->innertext);
        $this->assertEquals('1', $es[1]->innertext);
        $this->assertEquals('2', $es[2]->innertext);
        $this->assertEquals('3', $es[3]->innertext);
        $this->assertEquals('<li>0</li>', $es[0]->outertext);
        $this->assertEquals('<li>1</li>', $es[1]->outertext);
        $this->assertEquals('<li>2</li>', $es[2]->outertext);
        $this->assertEquals('<li>3</li>', $es[3]->outertext);

        $counter = 0;
        foreach ($dom->find('ul') as $ul) {
            foreach ($ul->find('li') as $li) {
                $this->assertEquals("$counter", $li->innertext);
                $this->assertEquals("<li>$counter</li>", $li->outertext);
                ++$counter;
            }
        }
        $dom->clear();
    }

    // -------------------------------------------------------------------------
    // Attribute value comparison selectors

    public function testAttributeEqualsSelector(): void
    {
        $this->dom->load('<input type="radio" name="newsletter" value="Hot Fuzz" /> <input type="radio" name="newsletters" value="Cold Fusion" /> <input type="radio" name="accept" value="Evil Plans" />');

        $es = $this->dom->find('[name=newsletter]');
        $this->assertCount(1, $es);
        $this->assertEquals('newsletter', $es[0]->name);
        $this->assertEquals('Hot Fuzz', $es[0]->value);
        $this->assertEquals('<input type="radio" name="newsletter" value="Hot Fuzz" />', $es[0]->outertext);

        $es = $this->dom->find('[name="newsletter"]');
        $this->assertCount(1, $es);
        $this->assertEquals('newsletter', $es[0]->name);
        $this->assertEquals('Hot Fuzz', $es[0]->value);
        $this->assertEquals('<input type="radio" name="newsletter" value="Hot Fuzz" />', $es[0]->outertext);
    }

    public function testAttributeNotEqualsSelector(): void
    {
        $this->dom->load('<input type="radio" name="newsletter" value="Hot Fuzz" /> <input type="radio" name="newsletter" value="Cold Fusion" /> <input type="radio" name="accept" value="Evil Plans" />');

        $es = $this->dom->find('[name!=newsletter]');
        $this->assertCount(1, $es);
        $this->assertEquals('accept', $es[0]->name);
        $this->assertEquals('Evil Plans', $es[0]->value);
        $this->assertEquals('<input type="radio" name="accept" value="Evil Plans" />', $es[0]->outertext);

        $es = $this->dom->find('[name!="newsletter"]');
        $this->assertCount(1, $es);
        $this->assertEquals('accept', $es[0]->name);

        $es = $this->dom->find("[name!='newsletter']");
        $this->assertCount(1, $es);
        $this->assertEquals('accept', $es[0]->name);
        $this->assertEquals('Evil Plans', $es[0]->value);
        $this->assertEquals('<input type="radio" name="accept" value="Evil Plans" />', $es[0]->outertext);
    }

    public function testAttributeStartsWithSelector(): void
    {
        $this->dom->load('<input name="newsletter" /> <input name="milkman" /> <input name="newsboy" />');

        $es = $this->dom->find('[name^=news]');
        $this->assertCount(2, $es);
        $this->assertEquals('newsletter', $es[0]->name);
        $this->assertEquals('<input name="newsletter" />', $es[0]->outertext);
        $this->assertEquals('newsboy', $es[1]->name);
        $this->assertEquals('<input name="newsboy" />', $es[1]->outertext);

        $es = $this->dom->find('[name^="news"]');
        $this->assertCount(2, $es);
        $this->assertEquals('newsletter', $es[0]->name);
        $this->assertEquals('<input name="newsletter" />', $es[0]->outertext);
        $this->assertEquals('newsboy', $es[1]->name);
        $this->assertEquals('<input name="newsboy" />', $es[1]->outertext);
    }

    public function testAttributeEndsWithSelector(): void
    {
        $this->dom->load('<input name="newsletter" /> <input name="milkman" /> <input name="jobletter" />');

        $es = $this->dom->find('[name$=letter]');
        $this->assertCount(2, $es);
        $this->assertEquals('newsletter', $es[0]->name);
        $this->assertEquals('<input name="newsletter" />', $es[0]->outertext);
        $this->assertEquals('jobletter', $es[1]->name);
        $this->assertEquals('<input name="jobletter" />', $es[1]->outertext);

        $es = $this->dom->find('[name$="letter"]');
        $this->assertCount(2, $es);
        $this->assertEquals('newsletter', $es[0]->name);
        $this->assertEquals('<input name="newsletter" />', $es[0]->outertext);
        $this->assertEquals('jobletter', $es[1]->name);
        $this->assertEquals('<input name="jobletter" />', $es[1]->outertext);
    }

    public function testAttributeContainsSelector(): void
    {
        $this->dom->load('<input name="man-news" /> <input name="milkman" /> <input name="letterman2" /> <input name="newmilk" /> <div class="foo hello bar"></div> <div class="foo bar hello"></div> <div class="hello foo bar"></div>');

        $es = $this->dom->find('[name*=man]');
        $this->assertCount(3, $es);
        $this->assertEquals('man-news', $es[0]->name);
        $this->assertEquals('<input name="man-news" />', $es[0]->outertext);
        $this->assertEquals('milkman', $es[1]->name);
        $this->assertEquals('<input name="milkman" />', $es[1]->outertext);
        $this->assertEquals('letterman2', $es[2]->name);
        $this->assertEquals('<input name="letterman2" />', $es[2]->outertext);

        $es = $this->dom->find('[name*="man"]');
        $this->assertCount(3, $es);
        $this->assertEquals('man-news', $es[0]->name);
        $this->assertEquals('<input name="man-news" />', $es[0]->outertext);
        $this->assertEquals('milkman', $es[1]->name);
        $this->assertEquals('<input name="milkman" />', $es[1]->outertext);
        $this->assertEquals('letterman2', $es[2]->name);
        $this->assertEquals('<input name="letterman2" />', $es[2]->outertext);

        $es = $this->dom->find('[class*=hello]');
        $this->assertEquals('<div class="foo hello bar"></div>', $es[0]->outertext);
        $this->assertEquals('<div class="foo bar hello"></div>', $es[1]->outertext);
        $this->assertEquals('<div class="hello foo bar"></div>', $es[2]->outertext);
    }

    // -------------------------------------------------------------------------
    // [] array-style attribute names (checkboxes with name="news[]")

    public function testNormalCheckboxNameSelector(): void
    {
        $this->dom->load('<input type="checkbox" name="news" value="foo" /> <input type="checkbox" name="news" value="bar"> <input type="checkbox" name="news" value="baz" />');
        $es = $this->dom->find('[name=news]');
        $this->assertCount(3, $es);
        $this->assertEquals('news', $es[0]->name);
        $this->assertEquals('foo', $es[0]->value);
        $this->assertEquals('news', $es[1]->name);
        $this->assertEquals('bar', $es[1]->value);
        $this->assertEquals('news', $es[2]->name);
        $this->assertEquals('baz', $es[2]->value);
    }

    public function testBracketNameSelector(): void
    {
        $this->dom->load('<input type="checkbox" name="news[]" value="foo" /> <input type="checkbox" name="news[]" value="bar"> <input type="checkbox" name="news[]" value="baz" />');
        $es = $this->dom->find('[name=news[]]');
        $this->assertCount(3, $es);
        $this->assertEquals('news[]', $es[0]->name);
        $this->assertEquals('foo', $es[0]->value);
        $this->assertEquals('news[]', $es[1]->name);
        $this->assertEquals('bar', $es[1]->value);
        $this->assertEquals('news[]', $es[2]->name);
        $this->assertEquals('baz', $es[2]->value);

        $this->dom->load('<input type="checkbox" name="news[foo]" value="foo" /> <input type="checkbox" name="news[bar]" value="bar">');
        $es = $this->dom->find('[name=news[foo]]');
        $this->assertCount(1, $es);
        $this->assertEquals('news[foo]', $es[0]->name);
        $this->assertEquals('foo', $es[0]->value);
    }

    public function testNestedBracketDescendantSelector(): void
    {
        $str = <<<HTML
        <div name="div[]">
            <input type="checkbox" name="checkbox[]" value="foo" />
        </div>
        HTML;
        $this->dom->load($str);
        $es = $this->dom->find('div[name=div[]] input[name=checkbox[]]');
        $this->assertCount(1, $es);
        $this->assertEquals('foo', $es[0]->value);
    }

    // -------------------------------------------------------------------------
    // Regex attribute selectors

    public function testRegexAttrSelector(): void
    {
        $str = <<<HTML
        <div>
        <a href="image/one.png">one</a>
        <a href="image/two.jpg">two</a>
        <a href="/favorites/aaa">three (text)</a>
        </div>
        HTML;
        $this->dom->load($str);
        $this->assertCount(2, $this->dom->find('a[href^="image/"]'));
        $this->assertCount(1, $this->dom->find('a[href*="/favorites/"]'));

        $str = <<<HTML
        <html>
            <body>
                <div id="news-id-123">okok</div>
            </body>
        </html>
        HTML;
        $this->dom->load($str);
        $this->assertCount(1, $this->dom->find('div[id*=news-id-[0-9]+]'));
        $this->assertCount(1, $this->dom->find('div[id*=/news-id-[0-9]+/i]'));
    }

    // -------------------------------------------------------------------------
    // Multiple class matching

    public function testMultipleClassMatching(): void
    {
        $str = '<div class="hello">should verify</div> <div class="foo hello bar">should verify</div> <div class="foo bar hello">should verify</div> <div class="hello foo bar">should verify</div> <div class="helloworld">should not verify</div> <div class="worldhello">should not verify</div> <div class="worldhelloworld">should not verify</div>';
        $this->dom->load($str);

        $es = $this->dom->find('[class="hello"],[class*="hello "],[class*=" hello"]');
        $this->assertCount(4, $es);
        $this->assertEquals('hello', $es[0]->class);
        $this->assertEquals('foo hello bar', $es[1]->class);
        $this->assertEquals('foo bar hello', $es[2]->class);
        $this->assertEquals('hello foo bar', $es[3]->class);

        $es = $this->dom->find('.hello');
        $this->assertCount(4, $es);
        $this->assertEquals('hello', $es[0]->class);
        $this->assertEquals('foo hello bar', $es[1]->class);
        $this->assertEquals('foo bar hello', $es[2]->class);
        $this->assertEquals('hello foo bar', $es[3]->class);
    }

    public function testMultipleClassSelector2(): void
    {
        $this->dom->load('<div class="aa bb"></div>');
        $this->assertCount(1, $this->dom->find('[class=aa]'));
        $this->assertCount(1, $this->dom->find('[class=bb]'));
        $this->assertCount(1, $this->dom->find('[class="aa bb"]'));
        $this->assertCount(1, $this->dom->find('[class=aa], [class=bb]'));
    }

    // -------------------------------------------------------------------------
    // Comma-separated selectors

    public function testCommaSeparatedSelectors(): void
    {
        $this->dom->load('<p>aaa</p> <b>bbb</b> <i>ccc</i>');

        foreach (['p,b,i', 'p, b, i', 'p,  b  ,   i', 'p ,b ,i', 'b,p,i', 'i,b,p', 'p,b,i,p,b'] as $selector) {
            $es = $this->dom->find($selector);
            $this->assertCount(3, $es, "Failed for selector: $selector");
            $this->assertEquals('p', $es[0]->tag);
            $this->assertEquals('b', $es[1]->tag);
            $this->assertEquals('i', $es[2]->tag);
        }

        $this->dom->load('<img title="aa" src="src"> <a href="href" title="aa"></a>');
        $this->assertCount(2, $this->dom->find('a[title], img[title]'));
    }

    // -------------------------------------------------------------------------
    // Negation — elements that do NOT have the specified attribute

    public function testNegationSelector(): void
    {
        $this->dom->load('<img id="aa" src="src"> <img src="src">');
        $this->assertCount(1, $this->dom->find('img[!id]'));
    }

    // -------------------------------------------------------------------------
    // JavaScript-style attribute value

    public function testJsAttributeSelector(): void
    {
        $this->dom->load('<a onMouseover="dropdownmenu(this, event, \'messagesmenu\')" class="n" href="messagecenter.cfm?key=972489434">foo</a>');
        $this->assertEquals('foo', $this->dom->find('a[onMouseover="dropdownmenu(this, event, \'messagesmenu\')"]', 0)->innertext);
        $this->assertEquals('foo', $this->dom->find("a[onMouseover=dropdownmenu(this, event, 'messagesmenu')]", 0)->innertext);
    }

    // -------------------------------------------------------------------------
    // Dash in attribute name

    public function testDashInAttributeName(): void
    {
        $this->dom->load('<meta http-equiv="content-type" content="text/html; charset=utf-8" />');
        $this->assertSame('text/html; charset=utf-8', $this->dom->find('meta[http-equiv=content-type]', 0)->content);
    }
}
