# Client Logger for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/emargareten/client-logger-laravel.svg?style=flat-square)](https://packagist.org/packages/emargareten/client-logger-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/emargareten/client-logger-laravel/run-tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/emargareten/client-logger-laravel/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/emargareten/client-logger-laravel/fix-php-code-style-issues.yml?branch=master&label=code%20style&style=flat-square)](https://github.com/emargareten/client-logger-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/emargareten/client-logger-laravel.svg?style=flat-square)](https://packagist.org/packages/emargareten/client-logger-laravel)

The Client Logger is a Laravel package designed to log HTTP client requests and responses. It is user-friendly and offers a high degree of customization.

## Installation

To install the package, use composer:

```bash
composer require emargareten/client-logger-laravel
```

## Usage

After installing the package, you may publish the configuration file with the following command (optional):

```bash
php artisan vendor:publish --provider="Emargareten\ClientLogger\ClientLoggerServiceProvider"
```

The package adds a `log` method to the PendingRequest class. This method can be used to log the request and response of an HTTP request:

```php
use Illuminate\Support\Facades\Http;

$response = Http::log('Example message...')->get('https://example.com');
```

This will create a log entry with the following information (in the context):
- `method`: The HTTP method of the request.
- `uri`: The URI of the request.
- `headers`: The headers of the request.
- `payload`: The payload of the request.
- `response_status`: The status code of the response.
- `response_headers`: The headers of the response.
- `response`: The body of the response.

## Customization

For more information on how to customize the package, please refer to the configuration file.

## Testing

To run the tests, use the following command:

```bash
composer test
```

## Code Analysis

To analyse the code, use the following command:

```bash
composer analyse
```

## Code Formatting

To format the code, use the following command:

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
