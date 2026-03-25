<?php

declare(strict_types=1);

namespace SimpleHtmlDom;

/**
 * Backed integer enum replacing the HDOM_QUOTE_* global constants.
 *
 * The raw int values are preserved so that code using the global define()
 * aliases (e.g. HDOM_QUOTE_DOUBLE) continues to work without modification.
 */
enum QuoteStyle: int
{
    case Double = 0;
    case Single = 1;
    case None   = 3; // Value 2 is intentionally skipped to match the legacy HDOM_QUOTE_NO constant
}
