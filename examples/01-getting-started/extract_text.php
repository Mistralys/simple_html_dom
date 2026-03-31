<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Article Example</title></head>
<body>
    <article>
        <h1>Understanding Autoloading</h1>
        <p>Autoloading is a <strong>core PHP feature</strong> that loads
        class files on demand, eliminating manual <code>require</code> calls.</p>
        <blockquote>
            "Composer's autoloader follows the PSR-4 standard."
        </blockquote>
        <ul>
            <li>No more manual requires</li>
            <li>Faster development</li>
            <li>Cleaner code</li>
        </ul>
    </article>
    <footer>
        <p>Copyright 2026 Example Inc.</p>
    </footer>
</body>
</html>
HTML);

// --- Full document plaintext ---
section('Full Document Plaintext');
// plaintext preserves inter-node whitespace from the source; trim() gives cleaner CLI output
echo trim($html->plaintext) . PHP_EOL;

// --- Single element plaintext ---
section('Single Element: article plaintext');
$article = $html->find('article', 0);
echo trim($article->plaintext) . PHP_EOL;

// --- Per-paragraph plaintext ---
section('Per-Paragraph: inline tags stripped');
foreach ($html->find('p') as $i => $p) {
    echo '  [' . $i . '] ' . trim($p->plaintext) . PHP_EOL;
}

// --- List items plaintext ---
section('List Items: tags stripped via plaintext');
foreach ($html->find('li') as $li) {
    echo '  • ' . trim($li->plaintext) . PHP_EOL;
}

$html->clear();
