# Split tests into manageable chunks

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vicklr/laravel-test-splitter.svg?style=flat-square)](https://packagist.org/packages/vicklr/laravel-test-splitter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/vicklr/laravel-test-splitter/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/vicklr/laravel-test-splitter/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/vicklr/laravel-test-splitter/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/vicklr/laravel-test-splitter/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vicklr/laravel-test-splitter.svg?style=flat-square)](https://packagist.org/packages/vicklr/laravel-test-splitter)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require vicklr/laravel-test-splitter
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-test-splitter-config"
```

This is the contents of the published config file:

```php
return [
    'executable' => 'pest',
];
```
## Usage

```
php artisan laravel-test-splitter --chunks=[number of chunks] 
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Jan Keller](https://github.com/4309700+MithrandirDK)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
