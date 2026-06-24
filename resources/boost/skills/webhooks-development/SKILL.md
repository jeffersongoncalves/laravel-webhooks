---
name: webhooks-development
description: Build outgoing webhook features using the jeffersongoncalves/laravel-webhooks package
---

# Webhooks Development

## When to use this skill

Use this skill when adding outgoing webhooks to a Laravel application with the
`jeffersongoncalves/laravel-webhooks` package: broadcasting Eloquent model
events (created/updated/deleted) to external URLs, customizing payloads,
signing requests, logging deliveries, and queueing.

## Setup

```bash
composer require jeffersongoncalves/laravel-webhooks
php artisan vendor:publish --tag=laravel-webhooks-config
php artisan vendor:publish --tag=laravel-webhooks-migrations
php artisan migrate
```

## Core concepts

- **Webhook model** (`JeffersonGoncalves\Webhooks\Models\Webhook`): a registered
  endpoint with `name`, `url`, `secret`, `events` (array), `model` (FQCN or
  `null` for all), and `is_active`.
- **WebhookLog model**: one row per delivery attempt with response code/body,
  success flag, attempt number and error message.
- **Concern traits**: add to your models to opt in.
  - `CreatedWebhook`, `UpdatedWebhook`, `DeletedWebhook` — single events.
  - `AllWebhooks` — all three.
  - `ShouldQueueWebhook` — force queueing regardless of config.
- **WebhookEvent enum**: `Created`, `Updated`, `Deleted` (values `created`,
  `updated`, `deleted`).
- **Webhooks facade** / `WebhooksManager`: low-level `dispatch()` and a
  `test()` helper that sends a sample payload synchronously and returns the log.

## Common patterns

### Broadcast a model

```php
use JeffersonGoncalves\Webhooks\Concerns\AllWebhooks;

class Order extends Model
{
    use AllWebhooks;
}
```

### Customize the payload

Override the event-specific method or the generic one:

```php
public function webhookCreatedPayload(): array
{
    return ['id' => $this->id, 'total' => $this->total];
}

// or, for every event:
public function webhookPayload(\JeffersonGoncalves\Webhooks\Enums\WebhookEvent $event): array
{
    return $this->only(['id', 'status']);
}
```

The final body is always `['event' => ..., 'model' => FQCN, 'data' => <your payload>]`.

### Signing

Set a `secret` on the webhook to sign requests (HMAC-SHA256, `Signature`
header). No secret means unsigned requests.

### Queue control

`config('webhooks.queue')` defaults to `true`. Use the `ShouldQueueWebhook`
trait to force a model's webhooks onto the queue even when the global value is
`false`.

## Troubleshooting

- **Nothing is dispatched**: confirm `config('webhooks.enabled')` is `true`,
  the model uses an event trait, and a matching active webhook exists
  (`Webhook::active()->forModel(Model::class)->forEvent($event)`).
- **`model` filtering**: a webhook with `model = null` listens to every model;
  set the FQCN to scope it.
- **No logs are written**: ensure `config('webhooks.logging.enabled')` is
  `true`. Logs are written by `LogWebhookCall`, which listens to the
  `spatie/laravel-webhook-server` success/failure events, so the
  `WebhookServerServiceProvider` must be registered (auto-discovered).
- **Testing without real HTTP**: fake the bus with `Bus::fake()` and assert on
  `Spatie\WebhookServer\CallWebhookJob`, or dispatch the spatie events directly
  to exercise the logging listener.
