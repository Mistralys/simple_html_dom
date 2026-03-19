<?php

declare(strict_types=1);

namespace Tests\DOM;

use PHPUnit\Framework\TestCase;

class CallbackTest extends TestCase
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

    public function testCallbackRemovesImgTags(): void
    {
        $str = '<img src="src0"><p>foo</p><img src="src2">';
        $this->dom->load($str);
        $this->dom->set_callback(function ($e) {
            if ($e->tag === 'img') {
                $e->outertext = '';
            }
        });
        $this->assertEquals('<p>foo</p>', (string) $this->dom);
    }

    public function testCallbackModifiesInnertext(): void
    {
        $str = '<img src="src0"><p>foo</p><img src="src2">';
        $this->dom->load($str);
        $this->dom->set_callback(function ($e) {
            if ($e->tag === 'p') {
                $e->innertext = 'bar';
            }
        });
        $this->assertEquals('<img src="src0"><p>bar</p><img src="src2">', (string) $this->dom);
    }

    public function testCallbackModifiesAttributes(): void
    {
        $str = '<img src="src0"><p>foo</p><img src="src2">';
        $this->dom->load($str);
        $this->dom->set_callback(function ($e) {
            if ($e->tag === 'img') {
                $e->src = 'foo';
            }
        });
        $this->assertEquals('<img src="foo"><p>foo</p><img src="foo">', (string) $this->dom);
    }

    public function testCallbackAddAttribute(): void
    {
        $str = '<img src="src0"><p>foo</p><img src="src2">';
        $this->dom->load($str);

        // Apply first callback to set src='foo' on all img elements
        $this->dom->set_callback(function ($e) {
            if ($e->tag === 'img') {
                $e->src = 'foo';
            }
        });
        // Trigger rendering so the first callback materialises the attribute changes
        (string) $this->dom;

        // Apply second callback that adds id='foo' — DOM already has src='foo'
        $this->dom->set_callback(function ($e) {
            if ($e->tag === 'img') {
                $e->id = 'foo';
            }
        });
        $this->assertEquals('<img src="foo" id="foo"><p>foo</p><img src="foo" id="foo">', (string) $this->dom);
    }

    public function testRemoveCallbackAndManualEdit(): void
    {
        $str = '<img src="src0"><p>foo</p><img src="src2">';
        $this->dom->load($str);
        $this->dom->remove_callback();
        $this->dom->find('img', 0)->id = 'foo';
        $this->assertEquals('<img src="src0" id="foo"><p>foo</p><img src="src2">', (string) $this->dom);

        // Callback that removes id from the img whose src is still 'src0'
        $this->dom->set_callback(function ($e) {
            if ($e->src === 'src0') {
                unset($e->id);
            }
        });
        $this->assertEquals($str, (string) $this->dom);
    }
}
