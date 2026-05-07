<?php

declare(strict_types=1);

namespace Tests\DOM;

use PHPUnit\Framework\TestCase;

class MiscTest extends TestCase
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

    public function testLastElementFound(): void
    {
        $str = <<<HTML
        <img class="class0" id="id0" src="src0">
        <img class="class1" id="id1" src="src1">
        <img class="class2" id="id2" src="src2">
        HTML;

        $this->dom->load($str, true, false);
        $es = $this->dom->find('img');

        $this->assertCount(3, $es);
        $this->assertEquals('src0', $es[0]->src);
        $this->assertEquals('src1', $es[1]->src);
        $this->assertEquals('src2', $es[2]->src);
        $this->assertEquals('', $es[0]->innertext);
        $this->assertEquals('', $es[1]->innertext);
        $this->assertEquals('', $es[2]->innertext);
        $this->assertEquals('<img class="class0" id="id0" src="src0">', $es[0]->outertext);
        $this->assertEquals('<img class="class1" id="id1" src="src1">', $es[1]->outertext);
        $this->assertEquals('<img class="class2" id="id2" src="src2">', $es[2]->outertext);
        $this->assertEquals('src0', $this->dom->find('img', 0)->src);
        $this->assertEquals('src1', $this->dom->find('img', 1)->src);
        $this->assertEquals('src2', $this->dom->find('img', 2)->src);
        $this->assertNull($this->dom->find('img', 3));
        $this->assertNull($this->dom->find('img', 99));
        $this->assertEquals($str, $this->dom->save());
    }

    public function testErrorTagHandling(): void
    {
        $str = <<<HTML
        <img class="class0" id="id0" src="src0"><p>p1</p>
        <img class="class1" id="id1" src="src1"><p>
        <img class="class2" id="id2" src="src2"></a></div>
        HTML;

        $this->dom = str_get_html($str, true, true, 'UTF-8', false);
        $es = $this->dom->find('img');

        $this->assertCount(3, $es);
        $this->assertEquals('src0', $es[0]->src);
        $this->assertEquals('src1', $es[1]->src);
        $this->assertEquals('src2', $es[2]->src);

        $es = $this->dom->find('p');
        $this->assertEquals('p1', $es[0]->innertext);
        $this->assertEquals($str, (string) $this->dom);
    }
}
