<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

use SimpleHtmlDom\Error;
use SimpleHtmlDom\Settings;

// --- Error class: constructor and value-object methods ---
section('Error class: getMessage(), getCode(), __toString()');

$error = new Error('Example parse error', 1001);
echo '  getMessage() : ' . $error->getMessage() . PHP_EOL;
echo '  getCode()    : ' . $error->getCode() . PHP_EOL;
echo '  __toString() : ' . $error . PHP_EOL;   // calls __toString()

// --- Error condition 1: empty HTML string ---
section('Error 1001: empty HTML string passed to str_get_html()');

Settings::reset();

$result = str_get_html('');

if ($result === false) {
    /** @var Error|null $err */
    $err = Settings::get('__error');
    if ($err instanceof Error) {
        echo '  Caught error' . PHP_EOL;
        echo '  Code    : ' . $err->getCode() . PHP_EOL;
        echo '  Message : ' . $err->getMessage() . PHP_EOL;
        echo '  String  : ' . $err . PHP_EOL;
    }
} else {
    echo '  (no error — unexpected)' . PHP_EOL;
    $result->clear();
}

Settings::reset();
echo '  Settings cleared after demo.' . PHP_EOL;

// --- Error condition 2: HTML exceeds max filesize ---
section('Error 1002: HTML string exceeds max filesize');

Settings::reset();
Settings::setMaxFilesize(50);   // artificially low limit for demo

$bigHtml = '<html><body>' . str_repeat('<p>x</p>', 20) . '</body></html>';
echo '  Max filesize : ' . Settings::getMaxFilesize() . ' bytes' . PHP_EOL;
echo '  HTML size    : ' . strlen($bigHtml) . ' bytes' . PHP_EOL;

$result = str_get_html($bigHtml);

if ($result === false) {
    /** @var Error|null $err */
    $err = Settings::get('__error');
    if ($err instanceof Error) {
        echo '  Code    : ' . $err->getCode() . PHP_EOL;
        echo '  Message : ' . $err->getMessage() . PHP_EOL;
    }
} else {
    echo '  (no error — unexpected)' . PHP_EOL;
    $result->clear();
}

Settings::reset();
echo '  Settings reset: max filesize back to ' . Settings::getMaxFilesize() . PHP_EOL;

// --- Error condition 3: manually constructed Error for custom handling ---
section('Error value object: immutable, used for custom error wrapping');

$customError = new Error('Custom validation failed: bad nesting', 9001);
echo '  getCode()    : ' . $customError->getCode() . PHP_EOL;
echo '  getMessage() : ' . $customError->getMessage() . PHP_EOL;
echo '  String form  : ' . $customError . PHP_EOL;
Settings::reset();
