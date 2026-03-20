<?php

declare(strict_types=1);

namespace SimpleHtmlDom;

/**
 * Backed integer enum replacing the HDOM_INFO_* global constants.
 *
 * The raw int values are preserved so that code using the global define()
 * aliases (e.g. HDOM_INFO_BEGIN) continues to work without modification.
 */
enum NodeInfo: int
{
    case Begin    = 0;
    case End      = 1;
    case Quote    = 2;
    case Space    = 3;
    case Text     = 4;
    case Inner    = 5;
    case Outer    = 6;
    case EndSpace = 7;
}
