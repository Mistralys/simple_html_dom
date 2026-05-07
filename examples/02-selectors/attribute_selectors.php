<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<nav>
    <a href="https://example.com">HTTPS link</a>
    <a href="http://insecure.example.com">HTTP link</a>
    <a href="/local/path">Local link</a>
    <a href="document.pdf">PDF download</a>
    <a href="report.pdf">Report PDF</a>
    <a href="archive.zip">ZIP archive</a>
    <button class="btn btn-primary">Save</button>
    <button class="btn btn-danger">Delete</button>
    <button class="icon-btn">Icon</button>
    <input type="text" name="username">
    <input type="submit" name="submit" disabled>
    <input type="reset" name="reset" disabled>
</nav>
HTML);

// --- Starts-with: [attr^=val] ---
section('Starts-With [href^=https]: secure links only');
foreach ($html->find('a[href^=https]') as $link) {
    echo '  ' . $link->href . PHP_EOL;
}

// --- Ends-with: [attr$=val] ---
section('Ends-With [href$=.pdf]: PDF links');
foreach ($html->find('a[href$=.pdf]') as $link) {
    echo '  ' . $link->plaintext . ' → ' . $link->href . PHP_EOL;
}

// --- Contains: [attr*=val] ---
section('Contains [class*=btn]: buttons with "btn" anywhere in class');
foreach ($html->find('[class*=btn]') as $el) {
    echo '  [' . $el->tag . '] class="' . $el->class . '" → ' . $el->plaintext . PHP_EOL;
}

// --- Attribute absent: [!attr] ---
section('Absent [!disabled]: inputs without disabled attribute');
foreach ($html->find('input[!disabled]') as $input) {
    echo '  name="' . $input->name . '" type="' . $input->type . '"' . PHP_EOL;
}

// --- Comma-separated groups ---
section('Comma Groups: a[href^=http], button');
foreach ($html->find('a[href^=http], button') as $el) {
    echo '  [' . $el->tag . '] ' . $el->plaintext . PHP_EOL;
}

$html->clear();
