<?php

declare(strict_types=1);

/**
 * Shared bootstrap for all Simple HTML DOM examples.
 *
 * - Resolves the project root via dirname(__DIR__) and loads Composer autoload.
 * - Defines the section() helper for readable CLI output.
 *
 * Usage: require __DIR__ . '/../_bootstrap.php'; (from any category subdirectory)
 *    or: require __DIR__ . '/_bootstrap.php';    (when required directly)
 */

$projectRoot = dirname(__DIR__);
$autoload    = $projectRoot . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    fwrite(STDERR, 'Autoloader not found. Run "composer install" first.' . PHP_EOL);
    exit(1);
}

require $autoload;

/**
 * Print a formatted CLI section divider.
 *
 * Example output:
 *   --- Basic Selectors ---
 */
function section(string $title): void
{
    echo PHP_EOL . '--- ' . $title . ' ---' . PHP_EOL . PHP_EOL;
}
