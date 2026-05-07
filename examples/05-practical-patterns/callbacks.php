<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

// --- set_callback(): invoked during save() / __toString() per node ---
section('set_callback(): transform elements during serialisation');

$html = str_get_html(<<<'HTML'
<div>
    <a href="https://example.com">External link</a>
    <a href="/local">Local link</a>
    <img src="photo.jpg" alt="Photo">
    <input type="text" name="q">
    <p>Regular paragraph</p>
</div>
HTML);

$log = [];

$html->set_callback(function (\SimpleHtmlDom\Node $node) use (&$log): void {
    if ($node->tag === 'img') {
        $node->outertext = '[IMG:' . $node->src . ']';
        $log[] = 'img replaced';
    } elseif ($node->tag === 'input') {
        $node->outertext = '[INPUT:' . $node->type . ']';
        $log[] = 'input replaced';
    }
});

$result = $html->save();

echo '  Callback fired for: ' . implode(', ', $log) . PHP_EOL;
echo '  Contains [IMG:   : ' . (str_contains($result, '[IMG:') ? 'yes' : 'no') . PHP_EOL;
echo '  Contains [INPUT: : ' . (str_contains($result, '[INPUT:') ? 'yes' : 'no') . PHP_EOL;
echo '  Original img tag : ' . (str_contains($result, '<img') ? 'yes' : 'no') . PHP_EOL;

$html->clear();

// --- remove_callback(): stop firing for subsequent save() calls ---
section('remove_callback(): disable the callback');

// Fresh fixture: register a callback then remove it BEFORE save() to prove it never fires.
$html2 = str_get_html(<<<'HTML'
<div>
    <a href="https://example.com">External link</a>
    <img src="photo.jpg" alt="Photo">
    <p>Regular paragraph</p>
</div>
HTML);

$removeCount = 0;

$html2->set_callback(function (\SimpleHtmlDom\Node $node) use (&$removeCount): void {
    $removeCount++;
});

// Remove the callback before save() — it must never fire
$html2->remove_callback();

$html2->save();

echo '  Callback fire count after remove_callback(): ' . $removeCount . PHP_EOL;
echo '  Callback suppressed:                         ' . ($removeCount === 0 ? 'yes' : 'no') . PHP_EOL;

$html2->clear();

// --- Closure callback with external state ---
section('Closure callback: count elements by tag during save()');

$html = str_get_html(<<<'HTML'
<article>
    <h1>Title</h1>
    <p>First paragraph.</p>
    <p>Second paragraph.</p>
    <a href="/one">Link one</a>
    <a href="/two">Link two</a>
    <a href="/three">Link three</a>
</article>
HTML);

$counts = [];
$html->set_callback(function (\SimpleHtmlDom\Node $node) use (&$counts): void {
    $tag = $node->tag;
    $counts[$tag] = ($counts[$tag] ?? 0) + 1;
});

$html->save(); // triggers callback for every node

foreach ($counts as $tag => $count) {
    echo '  ' . $tag . ': ' . $count . PHP_EOL;
}

$html->clear();
