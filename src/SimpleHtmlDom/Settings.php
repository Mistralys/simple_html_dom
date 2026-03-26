<?php

declare(strict_types=1);

namespace SimpleHtmlDom;

/**
 * Static key/value store for library-global settings.
 *
 * Replaces the legacy global class simple_html_dom_settings.
 * The bridge file registers: class_alias(Settings::class, 'simple_html_dom_settings')
 *
 * @package SimpleHtmlDom
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class Settings
{
    /** @var array<string, mixed> */
    protected static array $settings = [];

    public static function setMaxFilesize(int $bytes): void
    {
        self::set('max-filesize', $bytes);
    }

    public static function getMaxFilesize(): int
    {
        return (int) self::get('max-filesize', MAX_FILE_SIZE);
    }

    public static function set(string $name, mixed $value): void
    {
        self::$settings[$name] = $value;
    }

    public static function get(string $name, mixed $default = null): mixed
    {
        if (isset(self::$settings[$name])) {
            return self::$settings[$name];
        }

        return $default;
    }

    public static function reset(): void
    {
        self::$settings = [];
    }
}
