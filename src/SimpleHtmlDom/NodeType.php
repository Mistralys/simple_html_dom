<?php

declare(strict_types=1);

namespace SimpleHtmlDom;

/**
 * Backed integer enum replacing the HDOM_TYPE_* global constants.
 *
 * The raw int values are preserved so that code using the global define()
 * aliases (e.g. HDOM_TYPE_ELEMENT) continues to work without modification.
 */
enum NodeType: int
{
    case Element = 1;
    case Comment = 2;
    case Text    = 3;
    case EndTag  = 4;
    case Root    = 5;
    case Unknown = 6;
}
