<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleHtmlDom\Error;

class ErrorTest extends TestCase
{
    public function testGetMessage(): void
    {
        $error = new Error('Parse error occurred', 1001);
        $this->assertSame('Parse error occurred', $error->getMessage());
    }

    public function testGetCode(): void
    {
        $error = new Error('Parse error occurred', 1001);
        $this->assertSame(1001, $error->getCode());
    }

    public function testConstructorStoresBothFields(): void
    {
        $error = new Error('Another error', 9999);
        $this->assertSame('Another error', $error->getMessage());
        $this->assertSame(9999, $error->getCode());
    }

    public function testReadonlyEnforcedOnMessage(): void
    {
        $error = new Error('original', 1);
        // Both fields are private readonly — PHP prevents any external write.
        // Private visibility means the property is not accessible at all from outside.
        // This test confirms an \Error is thrown when attempting to write.
        $this->expectException(\Error::class);
        // @phpstan-ignore-next-line
        $error->message = 'mutated';
    }

    public function testReadonlyEnforcedOnCode(): void
    {
        $error = new Error('original', 1);
        $this->expectException(\Error::class);
        // @phpstan-ignore-next-line
        $error->code = 999;
    }
}
