<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleHtmlDom\Settings;

class SettingsTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset all settings to avoid cross-test contamination.
        Settings::reset();
    }

    public function testSetAndGet(): void
    {
        Settings::set('test-key', 'hello');
        $this->assertSame('hello', Settings::get('test-key'));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $this->assertNull(Settings::get('nonexistent-key-xyz'));
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $this->assertSame('default-value', Settings::get('nonexistent-key-xyz', 'default-value'));
    }

    public function testSetMaxFilesizeAndGet(): void
    {
        Settings::setMaxFilesize(123456);
        $this->assertSame(123456, Settings::getMaxFilesize());
        // Reset to default after test
        Settings::set('max-filesize', null);
    }

    public function testGetMaxFilesizeDefaultsFallsBackToConstant(): void
    {
        // Clear the max-filesize key so getMaxFilesize falls back to MAX_FILE_SIZE.
        Settings::set('max-filesize', null);
        // MAX_FILE_SIZE is defined as 600000 in the bridge file.
        $this->assertSame(600000, Settings::getMaxFilesize());
    }

    public function testErrorStorage(): void
    {
        $error = new \SimpleHtmlDom\Error('Something went wrong', 1001);
        Settings::set('__error', $error);
        $retrieved = Settings::get('__error');
        $this->assertInstanceOf(\SimpleHtmlDom\Error::class, $retrieved);
        $this->assertSame('Something went wrong', $retrieved->getMessage());
        $this->assertSame(1001, $retrieved->getCode());
    }
}
