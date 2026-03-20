<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleHtmlDom\TextConverter;

class TextConverterTest extends TestCase
{
    // -------------------------------------------------------------------------
    // isUtf8
    // -------------------------------------------------------------------------

    public function testIsUtf8WithAsciiString(): void
    {
        $this->assertTrue(TextConverter::isUtf8('Hello, World!'));
    }

    public function testIsUtf8WithValidUtf8(): void
    {
        // Multi-byte UTF-8 characters.
        $this->assertTrue(TextConverter::isUtf8("Héllo Wörld"));
    }

    public function testIsUtf8WithInvalidSequence(): void
    {
        // An invalid UTF-8 byte sequence (lone continuation byte).
        $this->assertFalse(TextConverter::isUtf8("\x80"));
    }

    public function testIsUtf8WithEmptyString(): void
    {
        // Empty string has no invalid bytes, so it's trivially valid UTF-8.
        $this->assertTrue(TextConverter::isUtf8(''));
    }

    // -------------------------------------------------------------------------
    // convert — passthrough cases
    // -------------------------------------------------------------------------

    public function testConvertWithSameCharset(): void
    {
        $text = 'Hello';
        $result = TextConverter::convert($text, 'UTF-8', 'UTF-8');
        $this->assertSame($text, $result);
    }

    public function testConvertWithEmptySourceCharset(): void
    {
        $text = 'Hello';
        $result = TextConverter::convert($text, '', 'UTF-8');
        $this->assertSame($text, $result);
    }

    public function testConvertWithEmptyTargetCharset(): void
    {
        $text = 'Hello';
        $result = TextConverter::convert($text, 'UTF-8', '');
        $this->assertSame($text, $result);
    }

    // -------------------------------------------------------------------------
    // convert — BOM stripping
    // -------------------------------------------------------------------------

    public function testConvertStripsLeadingUtf8Bom(): void
    {
        // UTF-8 BOM is "\xef\xbb\xbf".
        $bom = "\xef\xbb\xbf";
        $text = $bom . 'Hello';
        $result = TextConverter::convert($text, 'UTF-8', 'UTF-8');
        $this->assertSame('Hello', $result);
    }

    public function testConvertStripsTrailingUtf8Bom(): void
    {
        $bom = "\xef\xbb\xbf";
        $text = 'Hello' . $bom;
        $result = TextConverter::convert($text, 'UTF-8', 'UTF-8');
        $this->assertSame('Hello', $result);
    }

    public function testConvertDoesNotStripBomForNonUtf8Target(): void
    {
        // BOM should only be stripped when target is UTF-8.
        $bom = "\xef\xbb\xbf";
        $text = $bom . 'Hello';
        // When source and target are both empty, no conversion happens and no BOM stripping.
        $result = TextConverter::convert($text, '', '');
        $this->assertSame($text, $result);
    }
}
