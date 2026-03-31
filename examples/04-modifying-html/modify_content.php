<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<div id="page">
    <h1>Original Title</h1>
    <img src="logo.png" alt="Logo">
    <img src="banner.png" alt="Banner">
    <ul>
        <li>Keep me</li>
        <li class="remove">Remove me</li>
        <li>Keep me too</li>
    </ul>
    <input type="text" value="old">
    <input type="submit" value="Go">
    <p id="note">This is <strong>important</strong> text.</p>
</div>
HTML);

// --- Replace outertext to remove elements ---
section('Remove elements: set outertext to empty string');
foreach ($html->find('img') as $img) {
    echo '  Removing: ' . $img->src . PHP_EOL;
    $img->outertext = '';
}
// Verify: save() serialises the current state; img tags should be absent
$saved = $html->save();
echo '  Contains img after save: ' . (str_contains($saved, '<img') ? 'yes' : 'no') . PHP_EOL;

// --- Replace outertext to substitute elements ---
section('Replace elements: substitute input tags with placeholder text');
foreach ($html->find('input') as $input) {
    $input->outertext = '[INPUT:' . $input->type . ']';
}
$serialised = $html->save();
echo '  Placeholder in HTML: ' . (str_contains($serialised, '[INPUT:text]') ? 'yes' : 'no') . PHP_EOL;
echo '  Input tag removed  : ' . (str_contains($serialised, '<input') ? 'no' : 'yes') . PHP_EOL;

// --- Modify innertext (inner HTML) ---
section('Modify innertext: replace inner content of h1');
$h1 = $html->find('h1', 0);
echo '  Before: ' . $h1->innertext . PHP_EOL;
$h1->innertext = 'Updated Title';
echo '  After : ' . $h1->innertext . PHP_EOL;

// --- Set outertext (text) property on a node ---
section('Modify paragraph innertext: strip strong tag');
$note = $html->find('#note', 0);
echo '  Before: ' . $note->plaintext . PHP_EOL;
$note->innertext = 'Plain text only.';
echo '  After : ' . $note->plaintext . PHP_EOL;

// --- Remove specific elements by class ---
section('Remove by class: .remove list items');
foreach ($html->find('li.remove') as $li) {
    echo '  Removing: ' . $li->plaintext . PHP_EOL;
    $li->outertext = '';
}
// Verify via save() output
$serialised = $html->save();
echo '  "Remove me" still in output: ' . (str_contains($serialised, 'Remove me') ? 'yes' : 'no') . PHP_EOL;
echo '  "Keep me" still in output  : ' . (str_contains($serialised, 'Keep me') ? 'yes' : 'no') . PHP_EOL;

$html->clear();
