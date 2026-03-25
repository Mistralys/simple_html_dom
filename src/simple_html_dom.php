<?php
/**
 * Website: http://sourceforge.net/projects/simplehtmldom/
 * Acknowledge: Jose Solorzano (https://sourceforge.net/projects/php-html/)
 * Contributions by:
 *     Yousuke Kumakura (Attribute filters)
 *     Vadim Voituk (Negative indexes supports of "find" method)
 *     Antcs (Constructor with automatically load contents either text or file/url)
 *
 * all affected sections have comments starting with "PaperG"
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author S.C. Chen <me578022@gmail.com>
 * @author John Schlick
 * @author Rus Carroll
 * @version 2.0
 * @package PlaceLocalInclude
 * @subpackage simple_html_dom
 *
 * BRIDGE FILE: This file provides backward-compatible global constants, class aliases,
 * and procedural functions that delegate to the namespaced SimpleHtmlDom\ classes.
 */

/**
 * All of the Defines for the classes below.
 * @author S.C. Chen <me578022@gmail.com>
 */
define('HDOM_TYPE_ELEMENT', \SimpleHtmlDom\NodeType::Element->value);
define('HDOM_TYPE_COMMENT', \SimpleHtmlDom\NodeType::Comment->value);
define('HDOM_TYPE_TEXT',    \SimpleHtmlDom\NodeType::Text->value);
define('HDOM_TYPE_ENDTAG',  \SimpleHtmlDom\NodeType::EndTag->value);
define('HDOM_TYPE_ROOT',    \SimpleHtmlDom\NodeType::Root->value);
define('HDOM_TYPE_UNKNOWN', \SimpleHtmlDom\NodeType::Unknown->value);
define('HDOM_QUOTE_DOUBLE', \SimpleHtmlDom\QuoteStyle::Double->value);
define('HDOM_QUOTE_SINGLE', \SimpleHtmlDom\QuoteStyle::Single->value);
define('HDOM_QUOTE_NO',     \SimpleHtmlDom\QuoteStyle::None->value);
define('HDOM_INFO_BEGIN',   \SimpleHtmlDom\NodeInfo::Begin->value);
define('HDOM_INFO_END',     \SimpleHtmlDom\NodeInfo::End->value);
define('HDOM_INFO_QUOTE',   \SimpleHtmlDom\NodeInfo::Quote->value);
define('HDOM_INFO_SPACE',   \SimpleHtmlDom\NodeInfo::Space->value);
define('HDOM_INFO_TEXT',    \SimpleHtmlDom\NodeInfo::Text->value);
define('HDOM_INFO_INNER',   \SimpleHtmlDom\NodeInfo::Inner->value);
define('HDOM_INFO_OUTER',   \SimpleHtmlDom\NodeInfo::Outer->value);
define('HDOM_INFO_ENDSPACE',\SimpleHtmlDom\NodeInfo::EndSpace->value);
define('DEFAULT_TARGET_CHARSET', 'UTF-8');
define('DEFAULT_BR_TEXT', "\r\n");
define('DEFAULT_SPAN_TEXT', " ");
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 600000);
}

// ---------------------------------------------------------------------------
// Class aliases — map legacy global class names to the namespaced equivalents.
// Existing consumer code using `new simple_html_dom()` or type-hints like
// `\simple_html_dom` continues to work without any modification.
// ---------------------------------------------------------------------------
class_alias(\SimpleHtmlDom\Parser::class,   'simple_html_dom');
class_alias(\SimpleHtmlDom\Node::class,     'simple_html_dom_node');
class_alias(\SimpleHtmlDom\Settings::class, 'simple_html_dom_settings');
class_alias(\SimpleHtmlDom\Error::class,    'simple_html_dom_error');

// ---------------------------------------------------------------------------
// Procedural API — must remain globally callable.
// ---------------------------------------------------------------------------

/**
 * Get html dom from file or URL.
 * $maxlen is defined in the code as PHP_STREAM_COPY_ALL which is defined as -1.
 */
function file_get_html(string $url, bool $use_include_path = false, mixed $context = null, int $offset = -1, int $maxLen = -1, bool $lowercase = true, bool $forceTagsClosed = true, string $target_charset = DEFAULT_TARGET_CHARSET, bool $stripRN = true, string $defaultBRText = DEFAULT_BR_TEXT, string $defaultSpanText = DEFAULT_SPAN_TEXT): \SimpleHtmlDom\Parser|false
{
    // We DO force the tags to be terminated.
    $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);

    $redirectHops = 0;
    do {
        $repeat = false;
        if ($context !== null) {
            // Test if "Accept-Encoding: gzip" has been set in $context
            $params = stream_context_get_params($context);
            if (isset($params['options']['http']['header']) && preg_match('/gzip/', $params['options']['http']['header']) !== false) {
                $contents = file_get_contents('compress.zlib://' . $url, $use_include_path, $context, $offset);
            } else {
                $contents = file_get_contents($url, $use_include_path, $context, $offset);
            }
        } else {
            $contents = file_get_contents($url, $use_include_path, null, $offset);
        }

        // test if the URL doesn't return a 200 status
        // http_get_last_response_headers() requires PHP 8.4; replaces the deprecated $http_response_header superglobal.
        $response_headers = http_get_last_response_headers() ?? [];
        if (!empty($response_headers) && strpos($response_headers[0], '200') === false) {
            // has a 301 redirect header been sent?
            $pattern          = "/^Location:\s*(.*)$/i";
            $location_headers = preg_grep($pattern, $response_headers);

            if (!empty($location_headers) && preg_match($pattern, array_values($location_headers)[0], $matches)) {
                // set the URL to that returned via the redirect header and repeat this loop
                $url    = $matches[1];
                if (++$redirectHops < 5) {
                    $repeat = true;
                }
            }
        }
    } while ($repeat);

    // stop processing if the header isn't a good response
    $response_headers = http_get_last_response_headers() ?? [];
    if (!empty($response_headers) && strpos($response_headers[0], '200') === false) {
        simple_html_dom_settings::set(
            '__error',
            new simple_html_dom_error(
                sprintf(
                    'Wrong response code [%s] returned while loading the document from [%s]',
                    $response_headers[0],
                    $url
                ),
                1003
            )
        );

        return false;
    }

    if (empty($contents)) {
        $dom->clear();

        simple_html_dom_settings::set(
            '__error',
            new simple_html_dom_error(
                'Empty HTML string',
                1001
            )
        );

        return false;
    }

    $maxSize = simple_html_dom_settings::getMaxFilesize();
    if (strlen($contents) > $maxSize) {
        $dom->clear();

        simple_html_dom_settings::set(
            '__error',
            new simple_html_dom_error(
                sprintf(
                    'The HTML string extends the max size of [%s]. This can be increased using simple_html_dom_settings::setMaxFilesize().',
                    $maxSize
                ),
                1002
            )
        );

        return false;
    }

    // The second parameter can force the selectors to all be lowercase.
    $dom->load($contents, $lowercase, $stripRN);
    return $dom;
}

/**
 * @param string $str The HTML to parse
 * @param bool   $lowercase
 * @param bool   $forceTagsClosed
 * @param string $target_charset
 * @param bool   $stripRN
 * @param string $defaultBRText
 * @param string $defaultSpanText
 * @return simple_html_dom|false
 */
function str_get_html(string $str, bool $lowercase = true, bool $forceTagsClosed = true, string $target_charset = DEFAULT_TARGET_CHARSET, bool $stripRN = true, string $defaultBRText = DEFAULT_BR_TEXT, string $defaultSpanText = DEFAULT_SPAN_TEXT): \SimpleHtmlDom\Parser|false
{
    $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
    if (empty($str)) {
        $dom->clear();

        simple_html_dom_settings::set(
            '__error',
            new simple_html_dom_error(
                'Empty HTML string',
                1001
            )
        );

        return false;
    }

    $maxSize = simple_html_dom_settings::getMaxFilesize();
    if (strlen($str) > $maxSize) {
        $dom->clear();

        simple_html_dom_settings::set(
            '__error',
            new simple_html_dom_error(
                sprintf(
                    'The HTML string extends the max size of [%s]. This can be increased using simple_html_dom_settings::setMaxFilesize().',
                    $maxSize
                ),
                1002
            )
        );

        return false;
    }

    $dom->load($str, $lowercase, $stripRN);
    return $dom;
}

/**
 * Retrieves information about the last error that occurred, if any.
 *
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 * @return simple_html_dom_error|null
 */
function simple_html_dom_get_error(): \SimpleHtmlDom\Error|null
{
    return simple_html_dom_settings::get('__error');
}

/**
 * Dump html dom tree (debug helper).
 */
function dump_html_tree(\SimpleHtmlDom\Node $node, bool $show_attr = true, int $deep = 0): void
{
    $node->dump($show_attr, $deep);
}
