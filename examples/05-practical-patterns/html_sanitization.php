<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

/**
 * Sanitize an HTML string by removing dangerous elements and attributes.
 *
 * WARNING — EDUCATIONAL DEMO ONLY. This is NOT a production-safe sanitizer.
 * It intentionally omits several important steps to keep the example focused:
 *   - Does NOT strip javascript: URI schemes from href, src, or action attributes
 *   - Does NOT remove <object>, <embed>, <base>, <meta>, <form>, or <link> elements
 *   - Does NOT strip the style attribute (CSS expression injection is possible)
 * For production use, consider a dedicated library such as HTMLPurifier or a
 * strict DOM-based allowlist approach.
 *
 * Removes: <script>, <style>, <iframe> elements
 * Removes: on* event-handler attributes (onclick, onload, onerror, etc.)
 */
function sanitize_html(string $dirty): string
{
    $html = str_get_html($dirty);
    if ($html === false) {
        return '';
    }

    // Remove dangerous elements entirely
    foreach ($html->find('script, style, iframe') as $el) {
        $el->outertext = '';
    }

    // Strip event-handler attributes from all remaining elements
    foreach ($html->nodes as $el) {
        if ($el->nodetype !== HDOM_TYPE_ELEMENT) {
            continue;
        }
        foreach (array_keys($el->getAllAttributes()) as $attr) {
            if (str_starts_with(strtolower((string) $attr), 'on')) {
                $el->removeAttribute($attr);
            }
        }
    }

    $clean = $html->save();
    $html->clear();

    return $clean;
}

// --- Demo: malicious HTML input ---
section('Input: HTML with dangerous elements and event attributes');

$malicious = <<<'HTML'
<div>
    <p onclick="alert('xss')">Click me</p>
    <img src="x" onerror="fetch('https://evil.example.com')">
    <script>document.cookie = 'stolen';</script>
    <style>body { background: url('javascript:evil()'); }</style>
    <iframe src="https://phishing.example.com"></iframe>
    <a href="/safe" onmouseover="steal()">Safe looking link</a>
    <p>Legitimate content.</p>
</div>
HTML;

echo '  Input lines  : ' . substr_count($malicious, "\n") . PHP_EOL;
echo '  Has script   : ' . (str_contains($malicious, '<script') ? 'yes' : 'no') . PHP_EOL;
echo '  Has iframe   : ' . (str_contains($malicious, '<iframe') ? 'yes' : 'no') . PHP_EOL;
echo '  Has onclick  : ' . (str_contains($malicious, 'onclick') ? 'yes' : 'no') . PHP_EOL;
echo '  Has onerror  : ' . (str_contains($malicious, 'onerror') ? 'yes' : 'no') . PHP_EOL;

section('Output: after sanitization');

$clean = sanitize_html($malicious);

echo '  Has script   : ' . (str_contains($clean, '<script') ? 'FAIL' : 'PASS – no script') . PHP_EOL;
echo '  Has style    : ' . (str_contains($clean, '<style') ? 'FAIL' : 'PASS – no style') . PHP_EOL;
echo '  Has iframe   : ' . (str_contains($clean, '<iframe') ? 'FAIL' : 'PASS – no iframe') . PHP_EOL;
echo '  Has onclick  : ' . (str_contains($clean, 'onclick') ? 'FAIL' : 'PASS – no onclick') . PHP_EOL;
echo '  Has onerror  : ' . (str_contains($clean, 'onerror') ? 'FAIL' : 'PASS – no onerror') . PHP_EOL;
echo '  Has onmouseover: ' . (str_contains($clean, 'onmouseover') ? 'FAIL' : 'PASS – no onmouseover') . PHP_EOL;
echo '  Kept safe link : ' . (str_contains($clean, '/safe') ? 'yes' : 'no') . PHP_EOL;
echo '  Kept text      : ' . (str_contains($clean, 'Legitimate content') ? 'yes' : 'no') . PHP_EOL;
