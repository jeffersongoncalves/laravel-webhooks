<div class="filament-hidden">

![Laravel Webhooks](https://raw.githubusercontent.com/jeffersongoncalves/laravel-webhooks/main/art/jeffersongoncalves-laravel-webhooks.png)

</div>

# Laravel Webhooks

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-webhooks.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-webhooks)
[![Tests](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-webhooks/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-webhooks/actions/workflows/tests.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-webhooks/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/laravel-webhooks/actions/workflows/fix-php-code-style-issues.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-webhooks.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-webhooks)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/laravel-webhooks.svg?style=flat-square)](LICENSE.md)

Framework-agnostic outgoing webhooks for Laravel. Register webhook endpoints in your database and have them fire automatically whenever your Eloquent models are created, updated, or deleted. Payloads are fully customizable, requests can be signed with a secret, every call is logged, and delivery is handled by the battle-tested [`spatie/laravel-webhook-server`](https://github.com/spatie/laravel-webhook-server) (with retries and queue support).

This is the **core** package. A separate Filament admin layer is built on top of it.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Installation

```bash
composer require jeffersongoncalves/laravel-webhooks
```

The package uses Laravel's auto-discovery, so the service provider and the `Webhooks` facade are registered automatically.

### Publish the configuration

```bash
php artisan vendor:publish --tag=laravel-webhooks-config
```

### Publish and run the migrations

```bash
php artisan vendor:publish --tag=laravel-webhooks-migrations
php artisan migrate
```

The migrations create two tables: `webhooks` (the registered endpoints) and `webhook_logs` (the delivery log).

### Publish the translations (optional)

```bash
php artisan vendor:publish --tag=laravel-webhooks-translations
```

## Usage

### 1. Add a trait to the models you want to broadcast

Pick the trait that matches the lifecycle events you care about:

| Trait | Fires on |
|-------|----------|
| `JeffersonGoncalves\Webhooks\Concerns\CreatedWebhook` | `created` |
| `JeffersonGoncalves\Webhooks\Concerns\UpdatedWebhook` | `updated` |
| `JeffersonGoncalves\Webhooks\Concerns\DeletedWebhook` | `deleted` |
| `JeffersonGoncalves\Webhooks\Concerns\AllWebhooks` | `created`, `updated`, `deleted` |

```php
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Webhooks\Concerns\AllWebhooks;

class Order extends Model
{
    use AllWebhooks;
}
```

### 2. Register a webhook endpoint

Webhooks live in the database, so you can manage them however you like (seeders, an admin panel, the upcoming Filament layer, etc.):

```php
use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;
use JeffersonGoncalves\Webhooks\Models\Webhook;

Webhook::create([
    'name'      => 'Order notifications',
    'url'       => 'https://example.com/webhooks/orders',
    'secret'    => 'a-shared-secret',          // optional, enables signing
    'events'    => [WebhookEvent::Created->value, WebhookEvent::Updated->value],
    'model'     => Order::class,               // null = listen to every model
    'is_active' => true,
]);
```

From now on, creating or updating an `Order` automatically delivers an HTTP `POST` to the registered URL.

### Payload structure

The body sent to your endpoint always has the same envelope:

```json
{
    "event": "created",
    "model": "App\\Models\\Order",
    "data": {
        "id": 1,
        "total": 9990
    }
}
```

### Customizing the payload

By default the `data` key contains `$model->toArray()`. Override a single method to change it.

For a specific event, implement the matching method on your model:

```php
use JeffersonGoncalves\Webhooks\Concerns\CreatedWebhook;

class Order extends Model
{
    use CreatedWebhook;

    public function webhookCreatedPayload(): array
    {
        return [
            'id'     => $this->id,
            'total'  => $this->total,
            'status' => $this->status,
        ];
    }
}
```

The available overrides are `webhookCreatedPayload()`, `webhookUpdatedPayload()`, and `webhookDeletedPayload()`.

To change the payload for every event at once, override `webhookPayload()`:

```php
use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;

public function webhookPayload(WebhookEvent $event): array
{
    return [
        'id'    => $this->id,
        'event' => $event->value,
    ];
}
```

### Signing & secrets

If a webhook has a `secret`, every request is signed using
`spatie/laravel-webhook-server`. The signature is sent in the
`Signature` header, computed as an HMAC-SHA256 of the JSON body using the
secret as the key. Verify it on the receiving end before trusting the payload.

When a webhook has no secret, requests are sent **unsigned**.

### Logging

Every delivery (success or failure) is recorded in the `webhook_logs` table
when logging is enabled. Each log captures the URL, payload, response status
code, response body, the attempt number and any error message.

```php
use JeffersonGoncalves\Webhooks\Models\Webhook;

$webhook = Webhook::find(1);

$webhook->logs()->latest()->get();
```

Disable logging globally in the config file:

```php
'logging' => [
    'enabled' => false,
],
```

### Queueing

By default webhook requests are pushed onto the queue. Set `webhooks.queue`
to `false` to send them synchronously during the model event, or force a
specific model to always queue (regardless of the global setting) with the
`ShouldQueueWebhook` trait:

```php
use JeffersonGoncalves\Webhooks\Concerns\AllWebhooks;
use JeffersonGoncalves\Webhooks\Concerns\ShouldQueueWebhook;

class Order extends Model
{
    use AllWebhooks;
    use ShouldQueueWebhook;
}
```

### Sending a test webhook

Use the `Webhooks` facade to dispatch a sample payload synchronously and get
back the resulting log entry — handy for "Send test" buttons:

```php
use JeffersonGoncalves\Webhooks\Facades\Webhooks;
use JeffersonGoncalves\Webhooks\Models\Webhook;

$log = Webhooks::test(Webhook::find(1));
```

### Disabling everything

Set `webhooks.enabled` to `false` (or `WEBHOOKS_ENABLED=false`) to stop all
dispatching without removing the traits or registered webhooks.

## Compatibility

| Package | Laravel | PHP |
|---------|---------|-----|
| 1.x / `main` | 11, 12, 13 | 8.2+ |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security Vulnerabilities

If you discover any security-related issues, please email gerson.simao.92@gmail.com instead of using the issue tracker.

## Credits

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- Inspired by [dniccum/nova-webhooks](https://github.com/dniccum/nova-webhooks)
- Built on top of [spatie/laravel-webhook-server](https://github.com/spatie/laravel-webhook-server)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
