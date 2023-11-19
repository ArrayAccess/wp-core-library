## WP Core Library

This is a library for WordPress plugins. It provides a set of useful functions and classes.

The library is designed to be used with [Composer](https://getcomposer.org/). It is not a plugin itself, but a set of classes and functions that can be used in plugins.

### Installation

Add the following to your `composer.json` file:

```json
{
    "require": {
        "arrayaccess/wp-core-library": "dev-master"
    }
}
```

### Usage

To use the library, you need to include the Composer autoloader in your plugin:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

### Requirements

- php >= 8.0
- composer >= 2.0
- WordPress >= 6.4

## Features

- PSR2 Coding Standard
- Service-based library architecture
- Database abstraction layer using doctrine/dbal with WordPress caching implementation
- Object-based WordPress options
- WordPress Hooks API with option service
- Stateless Cookie API with hash verification

## License

GPLv3 or later see [LICENSE](LICENSE).
