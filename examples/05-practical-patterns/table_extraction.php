<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<table>
    <thead>
        <tr>
            <th>Framework</th>
            <th>Language</th>
            <th>Stars</th>
            <th>License</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Laravel</td>
            <td>PHP</td>
            <td>77000</td>
            <td>MIT</td>
        </tr>
        <tr>
            <td>Symfony</td>
            <td>PHP</td>
            <td>29000</td>
            <td>MIT</td>
        </tr>
        <tr>
            <td>Rails</td>
            <td>Ruby</td>
            <td>55000</td>
            <td>MIT</td>
        </tr>
        <tr>
            <td>Django</td>
            <td>Python</td>
            <td>79000</td>
            <td>BSD</td>
        </tr>
    </tbody>
</table>
HTML);

// --- Extract headers from th elements ---
$headers = [];
foreach ($html->find('th') as $th) {
    $headers[] = trim($th->plaintext);
}

section('Extracted headers');
foreach ($headers as $h) {
    echo '  - ' . $h . PHP_EOL;
}

// --- Extract data rows; skip header row (rows with th children) ---
$rows = [];
foreach ($html->find('tr') as $tr) {
    if (count($tr->find('th')) > 0) {
        continue; // skip header row
    }
    $cells = $tr->find('td');
    if (count($cells) === 0) {
        continue;
    }
    $row = [];
    foreach ($cells as $i => $td) {
        $col = $headers[$i] ?? 'col_' . $i;
        $row[$col] = trim($td->plaintext);
    }
    $rows[] = $row;
}

section('Structured PHP array (one entry per row)');
foreach ($rows as $i => $row) {
    echo '  [' . $i . '] ';
    $pairs = [];
    foreach ($row as $key => $val) {
        $pairs[] = $key . '=' . $val;
    }
    echo implode('  ', $pairs) . PHP_EOL;
}

section('Filter: PHP frameworks only');
$phpFrameworks = array_filter($rows, fn(array $r) => ($r['Language'] ?? '') === 'PHP');
foreach ($phpFrameworks as $row) {
    echo '  ' . $row['Framework'] . ' (' . $row['Stars'] . ' stars)' . PHP_EOL;
}

section('Sort by Stars descending');
usort($rows, fn(array $a, array $b) => (int) $b['Stars'] - (int) $a['Stars']);
foreach ($rows as $row) {
    echo '  ' . str_pad($row['Framework'], 10) . $row['Stars'] . PHP_EOL;
}

$html->clear();
