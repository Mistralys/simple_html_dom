<?php

declare(strict_types=1);

namespace SimpleHtmlDom;

/**
 * Error value object returned when parsing fails.
 *
 * Replaces the legacy global class simple_html_dom_error.
 * The bridge file registers: class_alias(Error::class, 'simple_html_dom_error')
 *
 * @package SimpleHtmlDom
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class Error
{
    public function __construct(
        private readonly string $message,
        private readonly int $code
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return "[{$this->code}] {$this->message}";
    }
}
