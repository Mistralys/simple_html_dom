<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

// --- Descendant selector ---
section('Descendant Selector: div div div');
$html = str_get_html(<<<'HTML'
<div>
    <div>
        <div class="deep">Found it!</div>
    </div>
</div>
HTML);
$node = $html->find('div div div', 0);
echo '  Result: ' . $node->plaintext . PHP_EOL;
$html->clear();

// --- Comma-separated selector ---
section('Comma-Separated Selector: h1, h2, h3');
$html = str_get_html(<<<'HTML'
<h1>Main Heading</h1>
<p>Introduction paragraph.</p>
<h2>Sub Heading</h2>
<p>Body paragraph.</p>
<h3>Section Heading</h3>
HTML);
foreach ($html->find('h1, h2, h3') as $heading) {
    echo '  [' . $heading->tag . '] ' . $heading->plaintext . PHP_EOL;
}
$html->clear();

// --- Nested find() ---
section('Nested find(): items per list');
$html = str_get_html(<<<'HTML'
<ul id="fruits">
    <li>Apple</li>
    <li>Banana</li>
</ul>
<ul id="veggies">
    <li>Carrot</li>
    <li>Broccoli</li>
</ul>
HTML);
foreach ($html->find('ul') as $ul) {
    echo '  List #' . $ul->id . ':' . PHP_EOL;
    foreach ($ul->find('li') as $li) {
        echo '    - ' . $li->plaintext . PHP_EOL;
    }
}
$html->clear();

// --- Boolean attribute selector ---
section('Attribute Selector: input[type=checkbox] checked state');
$html = str_get_html(<<<'HTML'
<form>
    <input type="checkbox" name="terms" value="yes" checked>
    <input type="checkbox" name="newsletter" value="yes">
    <input type="checkbox" name="promo" value="yes" checked>
</form>
HTML);
foreach ($html->find('input[type=checkbox]') as $cb) {
    // Boolean attrs return true when present, false when absent — truthy ternary is correct here
    $state = $cb->checked ? 'checked' : 'unchecked';
    echo '  ' . $cb->name . ': ' . $state . PHP_EOL;
}
$html->clear();

// --- Index access ---
section('Index Access: find("a", 1) — second anchor');
$html = str_get_html(<<<'HTML'
<nav>
    <a href="/home">Home</a>
    <a href="/about">About</a>
    <a href="/contact">Contact</a>
</nav>
HTML);
$second = $html->find('a', 1);
echo '  Second link: ' . $second->plaintext . ' → ' . $second->href . PHP_EOL;
$html->clear();
