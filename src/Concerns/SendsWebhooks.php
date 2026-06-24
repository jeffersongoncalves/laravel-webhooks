<?php

namespace JeffersonGoncalves\Webhooks\Concerns;

use Illuminate\Support\Str;
use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;
use JeffersonGoncalves\Webhooks\Models\Webhook;
use JeffersonGoncalves\Webhooks\WebhooksManager;

/**
 * Base trait that wires a model's lifecycle events to outgoing webhooks.
 *
 * Models should not use this trait directly. Instead, use one of the
 * event-specific traits (CreatedWebhook, UpdatedWebhook, DeletedWebhook)
 * or AllWebhooks, which all compose this trait.
 */
trait SendsWebhooks
{
    public static function bootSendsWebhooks(): void
    {
        foreach (static::enabledWebhookEvents() as $event) {
            static::registerModelEvent($event->value, function ($model) use ($event) {
                /** @var static $model */
                $model->dispatchWebhooks($event);
            });
        }
    }

    /**
     * Resolve the set of webhook events enabled on this model based on the
     * event-specific traits it uses.
     *
     * @return array<int, WebhookEvent>
     */
    public static function enabledWebhookEvents(): array
    {
        $traits = class_uses_recursive(static::class);

        $events = [];

        if (in_array(CreatedWebhook::class, $traits, true)) {
            $events[] = WebhookEvent::Created;
        }

        if (in_array(UpdatedWebhook::class, $traits, true)) {
            $events[] = WebhookEvent::Updated;
        }

        if (in_array(DeletedWebhook::class, $traits, true)) {
            $events[] = WebhookEvent::Deleted;
        }

        return $events;
    }

    /**
     * Dispatch every registered webhook that matches this model and event.
     */
    public function dispatchWebhooks(WebhookEvent $event): void
    {
        if (! config('webhooks.enabled', true)) {
            return;
        }

        /** @var class-string<Webhook> $webhookModel */
        $webhookModel = config('webhooks.models.webhook', Webhook::class);

        $webhooks = $webhookModel::query()
            ->active()
            ->forModel(static::class)
            ->forEvent($event)
            ->get();

        if ($webhooks->isEmpty()) {
            return;
        }

        $payload = $this->resolveWebhookPayload($event);
        $queue = $this->shouldQueueWebhook();

        /** @var WebhooksManager $manager */
        $manager = app(WebhooksManager::class);

        foreach ($webhooks as $webhook) {
            $manager->dispatch($webhook, $this, $event, $payload, $queue);
        }
    }

    /**
     * Resolve the payload for the given event, preferring an event-specific
     * override method (e.g. webhookCreatedPayload) when available.
     *
     * @return array<string, mixed>
     */
    protected function resolveWebhookPayload(WebhookEvent $event): array
    {
        $method = 'webhook'.Str::studly($event->value).'Payload';

        if (method_exists($this, $method)) {
            /** @var array<string, mixed> */
            return $this->{$method}();
        }

        return $this->webhookPayload($event);
    }

    /**
     * The default payload for any event. Override to customise.
     *
     * @return array<string, mixed>
     */
    public function webhookPayload(WebhookEvent $event): array
    {
        return $this->toArray();
    }

    /**
     * Whether the webhook HTTP request should be queued. Override via the
     * ShouldQueueWebhook trait (or this method) to force queueing.
     */
    public function shouldQueueWebhook(): bool
    {
        if (property_exists($this, 'forceQueueWebhook') && $this->forceQueueWebhook) {
            return true;
        }

        return (bool) config('webhooks.queue', true);
    }
}
