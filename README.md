# LaraHog

[![Latest Version on Packagist](https://img.shields.io/packagist/v/temperbit/larahog.svg?style=flat-square)](https://packagist.org/packages/temperbit/larahog)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/temperbit/larahog/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/temperbit/larahog/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/temperbit/larahog/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/temperbit/larahog/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/temperbit/larahog.svg?style=flat-square)](https://packagist.org/packages/temperbit/larahog)

The Laravel-native PostHog experience. LaraHog wraps the [PostHog PHP SDK](https://github.com/PostHog/posthog-php) into a first-class Laravel package with multi-connection support, queue-based dispatch, and Octane compatibility out of the box.

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13

## Support us

We invest a lot of resources into creating awesome software. You can support us by [sponsoring us on GitHub](https://github.com/sponsors/temperbit).

## Installation

```bash
composer require temperbit/larahog
```

Publish the config file:

```bash
php artisan vendor:publish --tag="larahog-config"
```

Add your PostHog project token to `.env`:

```env
POSTHOG_PROJECT_TOKEN=phc_your_project_token
```

## Configuration

The published config file will be at `config/larahog.php`.

| Variable | Default | Description |
|---|---|---|
| `POSTHOG_CONNECTION` | `default` | Default connection name |
| `POSTHOG_ENABLED` | `true` | Enable/disable the default connection |
| `POSTHOG_PROJECT_TOKEN` | `""` | Your PostHog project API key |
| `POSTHOG_HOST` | `https://us.i.posthog.com` | PostHog instance URL |
| `POSTHOG_DISPATCH_MODE` | `sync` | `sync` or `queue` |
| `POSTHOG_QUEUE_CONNECTION` | `null` | Laravel queue connection (when using queue mode) |
| `POSTHOG_QUEUE_NAME` | `default` | Laravel queue name (when using queue mode) |

### Multi-connection support

You can define multiple PostHog connections for different projects:

```php
// config/larahog.php
'connections' => [
    'default' => [
        'project_token' => env('POSTHOG_PROJECT_TOKEN'),
        // ...
    ],
    'marketing' => [
        'project_token' => env('POSTHOG_MARKETING_TOKEN'),
        // ...
    ],
],
```

Then target a specific connection:

```php
LaraHog::connection('marketing')->capture('user-123', 'campaign_clicked');
```

## Usage

### Capturing events

```php
use TemperBit\LaraHog\Facades\LaraHog;

// Basic event
LaraHog::capture('user-123', 'page_viewed');

// With properties
LaraHog::capture('user-123', 'purchase_completed', [
    'amount' => 49.99,
    'currency' => 'USD',
]);

// With group association
LaraHog::capture('user-123', 'report_exported', [], [
    'company' => 'company-456',
]);

// Anonymous event
LaraHog::capture(null, 'landing_page_viewed');
```

### Identifying users

```php
LaraHog::identify('user-123', [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'plan' => 'enterprise',
]);
```

### Aliasing identities

```php
LaraHog::alias('user-123', 'anonymous-session-abc');
```

### Group identities

```php
LaraHog::groupIdentify('company', 'company-456', [
    'name' => 'Acme Corp',
    'industry' => 'SaaS',
]);
```

### Flushing

LaraHog automatically flushes pending events when the application terminates. You can also flush manually:

```php
LaraHog::flush();    // Flush the default connection
LaraHog::flushAll(); // Flush all connections
```

### Checking status

```php
if (LaraHog::isEnabled()) {
    // ...
}
```

## Dispatch Modes

### Sync (default)

Events are buffered in memory by the PostHog SDK and sent in batches at the end of the request lifecycle. This is the simplest setup and works well for most applications.

```env
POSTHOG_DISPATCH_MODE=sync
```

### Queue

Events are dispatched to a Laravel queue for asynchronous processing. This moves PostHog API calls out of the request path entirely.

```env
POSTHOG_DISPATCH_MODE=queue
POSTHOG_QUEUE_CONNECTION=redis
POSTHOG_QUEUE_NAME=analytics
```

## Octane Compatibility

LaraHog uses a `PostHog\Client` instance (not the static `PostHog::init()` method), so each request gets a clean state. Multi-connection support is handled through the `LaraHogManager` singleton, which lazily resolves connections. This design is safe to use with Laravel Octane.

## Artisan Commands

### `larahog:status`

Displays the current configuration and tests connectivity to PostHog:

```bash
php artisan larahog:status
php artisan larahog:status --connection=marketing
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [TemperBit](https://github.com/temperbit)
- [Anand Capur](https://github.com/arcdigital)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
