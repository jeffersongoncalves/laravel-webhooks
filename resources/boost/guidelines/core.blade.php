## Laravel Webhooks Package

The `jeffersongoncalves/laravel-webhooks` package dispatches outgoing HTTP webhooks whenever Eloquent models are created, updated, or deleted. It is framework-agnostic (no Filament) and is the core layer a separate Filament admin package builds on.

### Package Namespace

All classes live under `JeffersonGoncalves\Webhooks`.

### Architecture

- **Facade**: `JeffersonGoncalves\Webhooks\Facades\Webhooks` resolves to `WebhooksManager`.
- **Manager**: `JeffersonGoncalves\Webhooks\WebhooksManager` builds and dispatches the HTTP call via `spatie/laravel-webhook-server`.
- **Models**: `Webhook` (registered endpoint) and `WebhookLog` (delivery log). Table names and model classes are configurable via `config/webhooks.php`.
- **Enum**: `JeffersonGoncalves\Webhooks\Enums\WebhookEvent` with cases `Created`, `Updated`, `Deleted` (string-backed: `created`, `updated`, `deleted`).
- **Concern traits** (added to the consumer's models): `CreatedWebhook`, `UpdatedWebhook`, `DeletedWebhook`, `AllWebhooks`, `ShouldQueueWebhook`, and the base `SendsWebhooks`.
- **Listener**: `JeffersonGoncalves\Webhooks\Listeners\LogWebhookCall` records `WebhookLog` rows from the spatie success/failure events.

### Key Conventions

- A model opts in by using one of the event traits. `SendsWebhooks` registers the Eloquent observers and only fires for events the model enabled (`enabledWebhookEvents()`).
- On a model event, the package queries `Webhook::active()->forModel(static::class)->forEvent($event)` and dispatches each match.
- The `forModel` scope matches webhooks whose `model` column is `null` (global) **or** equal to the model class.
- The delivered body is always `['event' => ..., 'model' => FQCN, 'data' => $payload]`.
- Default `data` is `$model->toArray()`; override `webhookPayload(WebhookEvent $event)` or the event-specific `webhook{Created,Updated,Deleted}Payload()` methods.
- Webhooks with a `secret` are signed (HMAC-SHA256 in the `Signature` header); without a secret, requests are sent unsigned.
- Queueing follows `config('webhooks.queue')`; the `ShouldQueueWebhook` trait forces queueing per-model.
- Set `config('webhooks.enabled')` to `false` to disable all dispatching.

### Adding webhooks to a model

@verbatim
<code-snippet name="Model broadcasting all lifecycle events" lang="php">
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Webhooks\Concerns\AllWebhooks;

class Order extends Model
{
    use AllWebhooks;
}
</code-snippet>
@endverbatim

### Registering an endpoint

@verbatim
<code-snippet name="Registering a webhook" lang="php">
use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;
use JeffersonGoncalves\Webhooks\Models\Webhook;

Webhook::create([
    'name'   => 'Order notifications',
    'url'    => 'https://example.com/hooks/orders',
    'secret' => 'shared-secret',
    'events' => [WebhookEvent::Created->value],
    'model'  => Order::class,
]);
</code-snippet>
@endverbatim

### Sending a test webhook

@verbatim
<code-snippet name="Dispatching a test webhook" lang="php">
use JeffersonGoncalves\Webhooks\Facades\Webhooks;

$log = Webhooks::test($webhook);
</code-snippet>
@endverbatim
