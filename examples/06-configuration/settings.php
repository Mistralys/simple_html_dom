<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

use SimpleHtmlDom\Settings;

// --- Default max filesize ---
section('Settings::getMaxFilesize(): default value');

Settings::reset();
echo '  Default max filesize: ' . Settings::getMaxFilesize() . ' bytes' . PHP_EOL;

// --- setMaxFilesize() / getMaxFilesize() ---
section('Settings::setMaxFilesize() and getMaxFilesize()');

Settings::setMaxFilesize(1_000_000);
echo '  After setMaxFilesize(1000000): ' . Settings::getMaxFilesize() . PHP_EOL;

Settings::setMaxFilesize(5_000_000);
echo '  After setMaxFilesize(5000000): ' . Settings::getMaxFilesize() . PHP_EOL;

Settings::reset();
echo '  After reset(): ' . Settings::getMaxFilesize() . ' (back to default)' . PHP_EOL;

// --- set() / get() for custom keys ---
section('Settings::set() and get(): custom configuration keys');

Settings::set('my-app.debug', true);
Settings::set('my-app.timeout', 30);
Settings::set('my-app.user-agent', 'MyApp/1.0');

echo '  my-app.debug      : ' . (Settings::get('my-app.debug') ? 'true' : 'false') . PHP_EOL;
echo '  my-app.timeout    : ' . Settings::get('my-app.timeout') . PHP_EOL;
echo '  my-app.user-agent : ' . Settings::get('my-app.user-agent') . PHP_EOL;

// --- get() with default value ---
section('Settings::get() with default fallback');

echo '  missing-key (no default)      : ' . var_export(Settings::get('missing-key'), true) . PHP_EOL;
echo '  missing-key (default = 42)    : ' . Settings::get('missing-key', 42) . PHP_EOL;
echo '  missing-key (default string)  : ' . Settings::get('missing-key', 'fallback') . PHP_EOL;

// --- Update and read back ---
section('Overwrite an existing key');

Settings::set('my-app.timeout', 60);
echo '  my-app.timeout after update: ' . Settings::get('my-app.timeout') . PHP_EOL;

// --- reset(): clears everything ---
section('Settings::reset(): clear all settings');

echo '  Before reset – my-app.debug: ' . (Settings::get('my-app.debug') ? 'set' : 'null') . PHP_EOL;
Settings::reset();
echo '  After reset  – my-app.debug: ' . var_export(Settings::get('my-app.debug'), true) . PHP_EOL;
echo '  After reset  – max filesize : ' . Settings::getMaxFilesize() . ' (default restored)' . PHP_EOL;
