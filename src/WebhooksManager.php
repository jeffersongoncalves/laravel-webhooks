<?php

namespace JeffersonGoncalves\Webhooks;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;
use JeffersonGoncalves\Webhooks\Models\Webhook;
use JeffersonGoncalves\Webhooks\Models\WebhookLog;
use Spatie\WebhookServer\WebhookCall;

class WebhooksManager
{
    /**
     * Dispatch a single webhook for the given model and event.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Webhook $webhook, Model $model, WebhookEvent $event, array $payload, bool $queue = true): void
    {
        $call = $this->buildCall(
            webhook: $webhook,
            url: $webhook->url,
            payload: $this->buildPayload($model, $event, $payload),
            meta: [
                'webhook_id' => $webhook->getKey(),
                'event' => $event->value,
            ],
        );

        if ($queue) {
            $call->dispatch();

            return;
        }

        $call->dispatchSync();
    }

    /**
     * Send a test webhook synchronously and return the persisted log entry.
     */
    public function test(Webhook $webhook): WebhookLog
    {
        $event = WebhookEvent::Created;

        $payload = [
            'event' => $event->value,
            'model' => $webhook->model,
            'data' => [
                'message' => 'This is a test webhook dispatched from '.config('app.name', 'Laravel').'.',
                'timestamp' => now()->format(config('webhooks.date_format', 'Y-m-d H:i:s')),
            ],
        ];

        $this->buildCall(
            webhook: $webhook,
            url: $webhook->url,
            payload: $payload,
            meta: [
                'webhook_id' => $webhook->getKey(),
                'event' => $event->value,
                'test' => true,
            ],
        )->dispatchSync();

        /** @var class-string<WebhookLog> $logModel */
        $logModel = config('webhooks.models.webhook_log', WebhookLog::class);

        return $logModel::query()
            ->where('webhook_id', $webhook->getKey())
            ->latest('id')
            ->first()
            ?? new $logModel([
                'webhook_id' => $webhook->getKey(),
                'event' => $event->value,
                'url' => $webhook->url,
                'payload' => $payload,
                'success' => false,
            ]);
    }

    /**
     * Build the final payload sent over the wire, including metadata.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function buildPayload(Model $model, WebhookEvent $event, array $payload): array
    {
        return [
            'event' => $event->value,
            'model' => $model::class,
            'data' => $payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $meta
     */
    protected function buildCall(Webhook $webhook, string $url, array $payload, array $meta): WebhookCall
    {
        $call = WebhookCall::create()
            ->url($url)
            ->payload($payload)
            ->meta($meta);

        if (filled($webhook->secret)) {
            $call->useSecret($webhook->secret);
        } else {
            $call->doNotSign();
        }

        return $call;
    }
}
