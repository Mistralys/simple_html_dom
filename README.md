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

## Revamped package

There is a revamped package from voku which improves the library a lot, but where the API has changed:

https://github.com/voku/simple_html_dom
