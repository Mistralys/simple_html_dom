<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<article>
    <h1>Title Text</h1>
    <p>First paragraph with <strong>bold</strong> and <em>italic</em> words.</p>
    <p>Second paragraph.</p>
</article>
HTML);

// --- Find all text nodes ---
section('find("text"): all text nodes in document');
$textNodes = $html->find('text');
echo '  Total text nodes found: ' . count($textNodes) . PHP_EOL;
foreach ($textNodes as $i => $node) {
    $content = trim($node->plaintext);
    if ($content !== '') { // text nodes between HTML tags are often whitespace-only; skip them
        echo '  [' . $i . '] ' . json_encode($content) . PHP_EOL;
    }
}

$html->clear();

// --- Text node manipulation ---
section('Text Node Manipulation: replace content of text nodes');
$html = str_get_html(<<<'HTML'
<p>Hello, world!</p>
<p>Goodbye, world!</p>
HTML);

// Show original
echo '  Before:' . PHP_EOL;
foreach ($html->find('p') as $p) {
    echo '    ' . $p->plaintext . PHP_EOL;
}

// Replace text content inside each paragraph's first text node
foreach ($html->find('text') as $node) {
    if (trim($node->plaintext) !== '') {
        $node->innertext = strtoupper($node->innertext);
    }
}

echo '  After (uppercased):' . PHP_EOL;
foreach ($html->find('p') as $p) {
    echo '    ' . $p->plaintext . PHP_EOL;
}

$html->clear();

// --- Scoped text nodes within an element ---
section('Scoped Text Nodes: find("text") on a single element');
$html = str_get_html(<<<'HTML'
<div>
    <span>Outer text</span>
    <div>
        <span>Inner text</span>
    </div>
    Trailing text
</div>
HTML);

$outerDiv = $html->find('div', 0);
$textNodes = $outerDiv->find('text');
echo '  Text nodes inside outer div: ' . count($textNodes) . PHP_EOL;
foreach ($textNodes as $node) {
    $content = trim($node->plaintext);
    if ($content !== '') {
        echo '  > ' . json_encode($content) . PHP_EOL;
    }
}

$html->clear();
