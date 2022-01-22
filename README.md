# Alternative Laravel Cache Driver for DynamoDB with tags support

[![Latest Version on Packagist](https://img.shields.io/packagist/v/asteroidstudio/laravel-dynamodb-tagged-cache-driver.svg?style=flat-square)](https://packagist.org/packages/asteroidstudio/laravel-dynamodb-tagged-cache-driver)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/asteroidstudio/laravel-dynamodb-tagged-cache-driver/run-tests?label=tests)](https://github.com/asteroidstudio/laravel-dynamodb-tagged-cache-driver/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/asteroidstudio/laravel-dynamodb-tagged-cache-driver/Check%20&%20fix%20styling?label=code%20style)](https://github.com/asteroidstudio/laravel-dynamodb-tagged-cache-driver/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/asteroidstudio/laravel-dynamodb-tagged-cache-driver.svg?style=flat-square)](https://packagist.org/packages/asteroidstudio/laravel-dynamodb-tagged-cache-driver)

## Installation

You can install the package via composer:

```bash
composer require asteroidstudio/laravel-dynamodb-tagged-cache-driver
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-dynamodb-tagged-cache-driver-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-dynamodb-tagged-cache-driver-config"
```

This is the contents of the published config file:

```php
return [
];
```


## Usage

```php
$laravelDynamodbTaggedCacheDriver = new AsteroidStudio\LaravelDynamodbTaggedCacheDriver();
echo $laravelDynamodbTaggedCacheDriver->echoPhrase('Hello, AsteroidStudio!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Asteroid Studio](https://github.com/asteroidstudio)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
