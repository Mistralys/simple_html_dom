<?php

declare(strict_types=1);

namespace SimpleHtmlDom;

/**
 * Stateless charset-conversion and UTF-8 detection helper.
 *
 * Extracted from simple_html_dom_node::convert_text() and ::is_utf8().
 *
 * @package SimpleHtmlDom
 */
class TextConverter
{
    /**
     * Convert $text from $sourceCharset to $targetCharset, stripping UTF-8 BOM
     * markers if the target is UTF-8.
     */
    public static function convert(string $text, string $sourceCharset, string $targetCharset): string
    {
        $converted = $text;

        if (!empty($sourceCharset) && !empty($targetCharset) && (strcasecmp($sourceCharset, $targetCharset) !== 0)) {
            // Check if the reported encoding could have been incorrect and the text is actually already UTF-8
            if ((strcasecmp($targetCharset, 'UTF-8') === 0) && self::is_utf8($text)) {
                $converted = $text;
            } else {
                $result = iconv($sourceCharset, $targetCharset, $text);
                if ($result !== false) {
                    $converted = $result;
                }
            }
        }

        // Strip UTF-8 BOM from the output if the target charset is UTF-8.
        if (strcasecmp($targetCharset, 'UTF-8') === 0) {
            if (substr($converted, 0, 3) === "\xef\xbb\xbf") {
                $converted = substr($converted, 3);
            }
            if (substr($converted, -3) === "\xef\xbb\xbf") {
                $converted = substr($converted, 0, -3);
            }
        }

        return $converted;
    }

    /**
     * Returns true if $str is valid UTF-8 and false otherwise.
     *
     * @param mixed $str String to be tested
     */
    public static function is_utf8(mixed $str): bool
    {
        $c = 0;
        $b = 0;
        $bits = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c >= 128) {
                if (($c >= 254)) {
                    return false;
                } elseif ($c >= 252) {
                    $bits = 6;
                } elseif ($c >= 248) {
                    $bits = 5;
                } elseif ($c >= 240) {
                    $bits = 4;
                } elseif ($c >= 224) {
                    $bits = 3;
                } elseif ($c >= 192) {
                    $bits = 2;
                } else {
                    return false;
                }
                if (($i + $bits) > $len) {
                    return false;
                }
                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bits--;
                }
            }
        }
        return true;
    }
}
