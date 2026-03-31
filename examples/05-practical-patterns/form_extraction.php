<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<form id="signup" method="post" action="/register">
    <input type="text"     name="username"  value="john_doe" placeholder="Username">
    <input type="email"    name="email"     value="john@example.com">
    <input type="password" name="password"  value="">
    <input type="number"   name="age"       value="28">

    <select name="country">
        <option value="">-- Choose --</option>
        <option value="us" selected>United States</option>
        <option value="ca">Canada</option>
        <option value="gb">United Kingdom</option>
    </select>

    <textarea name="bio" rows="4">Hello, I am John.</textarea>

    <fieldset>
        <legend>Gender</legend>
        <input type="radio" name="gender" value="male"   checked> Male
        <input type="radio" name="gender" value="female"> Female
        <input type="radio" name="gender" value="other">  Other
    </fieldset>

    <fieldset>
        <legend>Interests</legend>
        <input type="checkbox" name="interests[]" value="php"    checked> PHP
        <input type="checkbox" name="interests[]" value="js">           JavaScript
        <input type="checkbox" name="interests[]" value="python" checked> Python
    </fieldset>

    <input type="hidden"  name="_token" value="abc123"><!-- placeholder; real tokens must use bin2hex(random_bytes(32)) -->
    <input type="submit"  value="Register">
</form>
HTML);

// --- Extract text/email/password/number inputs ---
section('Text-like inputs (text, email, password, number)');
$textTypes = ['text', 'email', 'password', 'number'];
foreach ($html->find('input') as $input) {
    if (in_array($input->type, $textTypes, true)) {
        $display = $input->type === 'password' ? '(hidden)' : ($input->value ?: '(empty)');
        echo '  [' . $input->type . '] ' . $input->name . ' = ' . $display . PHP_EOL;
    }
}

// --- Extract select / option ---
section('Select fields and their options');
foreach ($html->find('select') as $select) {
    echo '  field: ' . $select->name . PHP_EOL;
    foreach ($select->find('option') as $option) {
        $selected = $option->selected ? ' *' : '';
        $label    = trim($option->plaintext);
        if ($label === '' || $option->value === '') {
            continue;
        }
        echo '    [' . $option->value . '] ' . $label . $selected . PHP_EOL;
    }
}

// --- Extract textarea ---
section('Textarea fields');
foreach ($html->find('textarea') as $ta) {
    echo '  [textarea] ' . $ta->name . ' = ' . trim($ta->innertext) . PHP_EOL;
}

// --- Extract radio buttons ---
section('Radio buttons (checked value)');
$radioGroups = [];
foreach ($html->find('input[type=radio]') as $radio) {
    $radioGroups[$radio->name][] = [
        'value'   => $radio->value,
        'checked' => (bool) $radio->checked,
    ];
}
foreach ($radioGroups as $name => $options) {
    $checked = array_filter($options, fn(array $o) => $o['checked']);
    $checkedValue = !empty($checked) ? reset($checked)['value'] : '(none)';
    $allValues    = array_column($options, 'value');
    echo '  ' . $name . ': ' . $checkedValue . ' (options: ' . implode(', ', $allValues) . ')' . PHP_EOL;
}

// --- Extract checkboxes ---
section('Checkboxes (checked values)');
$checkboxGroups = [];
foreach ($html->find('input[type=checkbox]') as $cb) {
    $baseName = rtrim($cb->name, '[]');
    $checkboxGroups[$baseName][] = [
        'value'   => $cb->value,
        'checked' => (bool) $cb->checked,
    ];
}
foreach ($checkboxGroups as $name => $options) {
    $checked = array_column(array_filter($options, fn(array $o) => $o['checked']), 'value');
    echo '  ' . $name . ': [' . implode(', ', $checked) . ']' . PHP_EOL;
}

// --- Extract hidden fields ---
section('Hidden fields');
foreach ($html->find('input[type=hidden]') as $hidden) {
    echo '  ' . $hidden->name . ' = ' . $hidden->value . PHP_EOL;
}

$html->clear();
