## Description

This is a fork of samac's package for some quality of life enhancements.

Original package: https://github.com/samacs/simple_html_dom

## Features

This package adds the following things:

  * Entirely backwards compatible
  * Possibility to access error messages when loading a file or string fails
  * Possibility to set the maximum file size setting after loading

## Usage

To use this fork, use the following settings in your `composer.json`:

```json
"repositories":[
    {
        "type":"vcs",
        "url":"https://github.com/Mistralys/simple_html_dom.git"
    }
],
"require": {
    "shark/simple_html_dom" : "dev-master"
}
```

## Development

### Requirements

- PHP 8.4+
- Composer

### Installing Dev Dependencies

```bash
composer install
```

### Running Tests

```bash
composer test
```

Or directly via PHPUnit (PHPUnit 12.x):

```bash
vendor/bin/phpunit
```

### Test Suites

Tests are organised into four named suites, each mapped to a subdirectory under `tests/`:

| Suite | Directory | Contents |
|---|---|---|
| `unit` | `tests/Unit/` | Pure unit tests for namespaced classes (`Parser`, `Node`, `Settings`, `Error`, `TextConverter`, `SelectorParser`) |
| `parsing` | `tests/Parsing/` | Parsing fidelity via the legacy bridge API |
| `selectors` | `tests/Selectors/` | CSS selector engine tests |
| `dom` | `tests/DOM/` | DOM-level integration tests |

Run a specific suite:

```bash
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite parsing
vendor/bin/phpunit --testsuite selectors
vendor/bin/phpunit --testsuite dom
```

---

## Revamped package

There is a revamped package from voku which improves the library a lot, but where the API has changed:

https://github.com/voku/simple_html_dom
