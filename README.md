# Exo

## Usage

```
→ compose require "assertchris/exo:dev-master"
```

## ...Because It's Still Private

```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/assertchris/exo"
        }
    ],
    "require": {
        "assertchris/exo": "dev-master"
    },
    "extra": {
        "exo": {
            "parameters": {
                "phpcs": "--standard=PSR2"
            },
            "paths": {
                "src": "src",
                "tests": "tests",
                "coverage": "coverage",
                "phpunit": "phpunit.xml",
                "phpcs": "vendor/bin/phpcs",
                "travis": ".travis.yml",
                "scrutinizer": ".scrutinizer.yml",
                "gitignore": ".gitignore",
                "editorconfig": ".editorconfig",
                "license": "license.md"
            }
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Starting with this `composer.json` will work better than requiring it straight up.
