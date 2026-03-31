<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<ul>
    <li>First item</li>
    <li>Second item</li>
    <li>Third item</li>
    <li>Fourth item</li>
    <li>Last item</li>
</ul>
HTML);

// --- First match: idx 0 ---
section('First Match: find("li", 0)');
$first = $html->find('li', 0);
echo '  ' . $first->plaintext . PHP_EOL;

// --- Last match: idx -1 ---
section('Last Match: find("li", -1)');
$last = $html->find('li', -1);
echo '  ' . $last->plaintext . PHP_EOL;

// --- Second-to-last: idx -2 ---
section('Second-to-Last: find("li", -2)');
$secondLast = $html->find('li', -2);
echo '  ' . $secondLast->plaintext . PHP_EOL;

// --- Nth by positive index ---
section('Third Item: find("li", 2)');
$third = $html->find('li', 2);
echo '  ' . $third->plaintext . PHP_EOL;

// --- Out-of-bounds returns null ---
section('Out-of-Bounds: find("li", 100) returns null');
$outOfBounds = $html->find('li', 100);
echo '  Result: ' . ($outOfBounds === null ? 'null (no match)' : $outOfBounds->plaintext) . PHP_EOL;

// --- No match at all returns null ---
section('No Match: find("table", 0) returns null');
$noMatch = $html->find('table', 0);
echo '  Result: ' . ($noMatch === null ? 'null (no match)' : $noMatch->plaintext) . PHP_EOL;

// --- All results: null idx returns array ---
section('All Matches: find("li") returns array');
$all = $html->find('li');
echo '  Count: ' . count($all) . PHP_EOL;
foreach ($all as $i => $li) {
    echo '  [' . $i . '] ' . $li->plaintext . PHP_EOL;
}

$html->clear();
