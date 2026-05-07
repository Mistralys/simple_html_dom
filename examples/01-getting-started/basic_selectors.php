<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Simple Page</title></head>
<body>
    <h1 id="main-title">Welcome to Simple HTML DOM</h1>
    <p class="intro">This is an introductory paragraph.</p>
    <p class="intro highlight">This paragraph has two classes.</p>
    <a href="https://example.com" target="_blank">External link</a>
    <a href="/about">Internal link</a>
    <ul>
        <li>Item one</li>
        <li>Item two</li>
        <li>Item three</li>
    </ul>
    <input type="text" name="username" value="john">
    <input type="email" name="email" value="john@example.com">
    <input type="submit" value="Submit">
</body>
</html>
HTML);

// --- Tag selector ---
section('Tag Selector: all li elements');
foreach ($html->find('li') as $item) {
    echo '  • ' . $item->plaintext . PHP_EOL;
}

// --- #id selector ---
section('#id Selector: h1#main-title');
$title = $html->find('h1#main-title', 0); // returns null if the element is absent
echo '  Title: ' . $title->plaintext . PHP_EOL;

// --- .class selector ---
section('.class Selector: elements with class="intro"');
foreach ($html->find('.intro') as $el) {
    echo '  • ' . $el->plaintext . PHP_EOL;
}

// --- Attribute value selector ---
section('Attribute Selector: input[type=text]');
$input = $html->find('input[type=text]', 0);
echo '  name  : ' . $input->name . PHP_EOL;
echo '  value : ' . $input->value . PHP_EOL;

// --- Attribute presence selector ---
section('Attribute Presence: a[target]');
foreach ($html->find('a[target]') as $link) {
    echo '  href   : ' . $link->href . PHP_EOL;
    echo '  target : ' . $link->target . PHP_EOL;
}

$html->clear();
