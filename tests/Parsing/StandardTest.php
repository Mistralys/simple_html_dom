<?php

declare(strict_types=1);

namespace Tests\Parsing;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Adapted from testcase/std_testcase.php.
 * The original fuzz test used mt_rand() — replaced with a deterministic fixed set of strings.
 */
class StandardTest extends TestCase
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
    // Empty / null input

    public function testEmptyString(): void
    {
        $str = '';
        $this->dom->load($str);
        $this->assertEquals($str, $this->dom->save());
    }

    public function testNullInput(): void
    {
        $str = null;
        $this->dom->load($str);
        $this->assertEquals($str, $this->dom->save());
    }

    // -------------------------------------------------------------------------
    // DOCTYPE handling

    public function testDoctypeAndXmlns(): void
    {
        $str = <<<HTML
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                              "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"></html>
        HTML;
        $this->dom->load($str);
        $this->assertCount(1, $this->dom->find('unknown'));
        $this->assertCount(1, $this->dom->find('text'));
    }

    // -------------------------------------------------------------------------
    // String quote handling

    public function testStringQuotes(): void
    {
        $str = <<<HTML
<div class="class0" id="id0" >
    okok<br>
    <input type=submit name="btnG" value="go" onclick='goto("url0")'>
    <br/>
    <div><input type=submit name="btnG2" value="go" onclick="goto('url1'+'\'')"/></div>
    <input type=submit name="btnG2" value="go" onclick="goto('url2')"/>
    <div><input type=submit name="btnG2" value="go" onclick='goto("url4"+"\"")'></div>
    <br/>
</div>
HTML;
        $this->dom->load($str);
        $es = $this->dom->find('input');
        $this->assertCount(4, $es);
        $this->assertEquals('goto("url0")', $es[0]->onclick);
        $this->assertEquals("goto('url1'+'\\'')", $es[1]->onclick);
        $this->assertEquals("goto('url2')", $es[2]->onclick);
        $this->assertEquals('goto("url4"+"\"")', $es[3]->onclick);
    }

    // -------------------------------------------------------------------------
    // Clone test

    public function testCloneDom(): void
    {
        $str = <<<HTML
<div class="class0" id="id0" >
    okok<br>
    <input type=submit name="btnG" value="go" onclick='goto("url0")'>
    <br/>
    <div><input type=submit name="btnG2" value="go" onclick="goto('url1'+'\'')"/></div>
    <input type=submit name="btnG2" value="go" onclick="goto('url2')"/>
    <div><input type=submit name="btnG2" value="go" onclick='goto("url4"+"\"")'></div>
    <br/>
</div>
HTML;
        $this->dom->load($str);
        $es = $this->dom->find('input');
        $this->assertCount(4, $es);
        $this->assertEquals('goto("url0")', $es[0]->onclick);
        $this->assertEquals("goto('url1'+'\\'')", $es[1]->onclick);
        $this->assertEquals("goto('url2')", $es[2]->onclick);
        $this->assertEquals('goto("url4"+"\"")', $es[3]->onclick);

        unset($es);
        $dom2 = clone($this->dom);
        $es = $dom2->find('input');
        $this->assertCount(4, $es);
        $this->assertEquals('goto("url0")', $es[0]->onclick);
        $this->assertEquals("goto('url1'+'\\'')", $es[1]->onclick);
        $this->assertEquals("goto('url2')", $es[2]->onclick);
        $this->assertEquals('goto("url4"+"\"")', $es[3]->onclick);
        $dom2->clear();
        unset($dom2);
    }

    public function testMixedQuoteAttributes(): void
    {
        $str = '<div class=\'class0\' id="id0" aa=\'aa\' bb="bb" cc=\'"cc"\' dd="\'dd\'"></div>' . "\n";
        $this->dom->load($str, true, false);
        $this->assertEquals($str, (string) $this->dom);
        $this->assertEquals($str, $this->dom->save());
    }

    // -------------------------------------------------------------------------
    // Monkey tests (unusual / incomplete markup)

    /**
     * @return array<string, array{string}>
     */
    public static function monkeyStringProvider(): array
    {
        return [
            'just <'              => ["<\n"],
            'just < newline'      => ["<\n\n"],
            'newlines then <'     => ["\n\n<\n"],
            'incomplete tag'      => ["<a\n"],
            'tag followed by <'   => ["<a<\n"],
            'quad < + text'       => ["<<<<ab\n"],
            'quad < + text space' => ["<<<<ab  \n"],
            '<<><<> + text'       => ["<<><<>ab  \n"],
            'incomplete abc tag'  => ["<abc\n\n"],
            'bare >'              => [">\n"],
            // '(<1 mol%) \n' now round-trips correctly (tokeniser fix: '<digit' is plain text).
            // Tested with round-trip assertion in testChemistryFormula().
        ];
    }

    #[DataProvider('monkeyStringProvider')]
    public function testMonkeyStrings(string $str): void
    {
        $this->dom->load($str, true, false);
        $this->assertEquals($str, (string) $this->dom);
        $this->assertEquals($str, $this->dom->save());
    }

    /**
     * Strings containing '<' followed by a digit are plain text, not tag openers (HTML5 spec).
     * After the tokeniser fix, '<1 mol%' round-trips correctly.
     */
    public function testChemistryFormula(): void
    {
        $str = "(<1 mol%) \n";
        $this->dom->load($str, true, false);
        $this->assertEquals($str, (string) $this->dom);
        $this->assertEquals($str, $this->dom->save());
    }

    // -------------------------------------------------------------------------
    // Deterministic fuzz test (replaces the original mt_rand() random loop)

    /**
     * @return array<string, array{string}>
     */
    public static function fuzzStringProvider(): array
    {
        // Fixed-length strings chosen to cover 0..59 char lengths deterministically
        $charList = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890<>!?[]%^&*()';
        $strings = [];
        // Use a seeded PRNG pattern: pick chars by cycling through charList with a stride
        $len = strlen($charList);
        for ($i = 0; $i < 60; ++$i) {
            $s = '';
            for ($j = 0; $j < $i; ++$j) {
                $s .= $charList[($i * 7 + $j * 13) % $len];
            }
            $strings["length_$i"] = [$s];
        }
        return $strings;
    }

    #[DataProvider('fuzzStringProvider')]
    public function testFuzzString(string $str): void
    {
        $this->dom->load($str, false);
        $this->assertEquals($str, (string) $this->dom);
    }

    // -------------------------------------------------------------------------
    // Lowercase mode

    public function testLowercaseTagDefault(): void
    {
        $str = '<img class="class0" id="id0" src="src0">' . "\n";
        // Use stripRN=false to preserve the trailing newline for round-trip equality
        $this->dom->load($str, true, false);
        $this->assertCount(1, $this->dom->find('img'));
        $this->assertCount(1, $this->dom->find('IMG'));
        $this->assertTrue(isset($this->dom->find('img', 0)->class));
        $this->assertFalse(isset($this->dom->find('img', 0)->CLASS));
        $this->assertEquals('class0', $this->dom->find('img', 0)->class);
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testUppercaseTagForcedLower(): void
    {
        $str = '<IMG CLASS="class0" ID="id0" SRC="src0">' . "\n";
        $this->dom->load($str, true, false);
        $this->assertCount(1, $this->dom->find('img'));
        $this->assertCount(1, $this->dom->find('IMG'));
        $this->assertTrue(isset($this->dom->find('img', 0)->class));
        $this->assertFalse(isset($this->dom->find('img', 0)->CLASS));
        $this->assertEquals('class0', $this->dom->find('img', 0)->class);
        $this->assertEquals(strtolower($str), (string) $this->dom);
    }

    public function testLowercaseFalsePreservesCase(): void
    {
        $str = '<IMG CLASS="class0" ID="id0" SRC="src0">' . "\n";
        $this->dom->load($str, false, false);
        $this->assertCount(0, $this->dom->find('img'));
        $this->assertCount(1, $this->dom->find('IMG'));
        $this->assertTrue(isset($this->dom->find('IMG', 0)->CLASS));
        $this->assertFalse(isset($this->dom->find('IMG', 0)->class));
        $this->assertEquals('class0', $this->dom->find('IMG', 0)->CLASS);
        $this->assertEquals($str, (string) $this->dom);
    }
}
