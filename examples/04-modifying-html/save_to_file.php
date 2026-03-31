<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

// --- Build and save HTML ---
section('Save modified HTML to a temp file');

$html = str_get_html(<<<'HTML'
<html>
<head><title>Save Demo</title></head>
<body>
    <h1 id="title">Original Heading</h1>
    <p id="content">Original content.</p>
</body>
</html>
HTML);

// Modify content before saving
$html->find('#title', 0)->innertext   = 'Saved Heading';
$html->find('#content', 0)->innertext = 'This content was saved to disk.';

$tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'simple_html_dom_save_example.html';

// save() writes the file and returns the serialised HTML
$saved = $html->save($tempFile);
$html->clear();

echo '  Temp file : ' . $tempFile . PHP_EOL;
echo '  File exists: ' . (file_exists($tempFile) ? 'true' : 'false') . PHP_EOL;
echo '  File size  : ' . filesize($tempFile) . ' bytes' . PHP_EOL;

// --- Read back and verify ---
section('Read back and verify');

$reload = str_get_html((string) file_get_contents($tempFile));

$heading = $reload->find('#title', 0);
$content = $reload->find('#content', 0);

echo '  Heading : ' . ($heading ? $heading->plaintext : 'NOT FOUND') . PHP_EOL;
echo '  Content : ' . ($content ? $content->plaintext : 'NOT FOUND') . PHP_EOL;

$headingMatches = $heading && $heading->plaintext === 'Saved Heading';
$contentMatches = $content && $content->plaintext === 'This content was saved to disk.';

echo '  Heading correct: ' . ($headingMatches ? 'PASS' : 'FAIL') . PHP_EOL;
echo '  Content correct: ' . ($contentMatches ? 'PASS' : 'FAIL') . PHP_EOL;

$reload->clear();

// --- Cleanup ---
section('Cleanup: remove temp file');

unlink($tempFile);

echo '  File exists after unlink: ' . (file_exists($tempFile) ? 'true (ERROR)' : 'false (OK)') . PHP_EOL;
