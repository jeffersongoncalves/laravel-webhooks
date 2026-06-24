<?php

namespace JeffersonGoncalves\Webhooks\Listeners;

use JeffersonGoncalves\Webhooks\Models\WebhookLog;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

class LogWebhookCall
{
    public function handleSucceeded(WebhookCallSucceededEvent $event): void
    {
        $this->log($event, true);
    }

    public function handleFailed(WebhookCallFailedEvent $event): void
    {
        $this->log($event, false);
    }

    public function handleFinalFailed(FinalWebhookCallFailedEvent $event): void
    {
        $this->log($event, false);
    }

    protected function log(WebhookCallEvent $event, bool $success): void
    {
        if (! config('webhooks.logging.enabled', true)) {
            return;
        }

        /** @var class-string<WebhookLog> $logModel */
        $logModel = config('webhooks.models.webhook_log', WebhookLog::class);

        $logModel::query()->create([
            'webhook_id' => $event->meta['webhook_id'] ?? null,
            'event' => $event->meta['event'] ?? null,
            'url' => $event->webhookUrl,
            'payload' => is_array($event->payload) ? $event->payload : ['raw' => $event->payload],
            'response_code' => $event->response?->getStatusCode(),
            'response_body' => $event->response !== null ? (string) $event->response->getBody() : null,
            'success' => $success,
            'error_message' => $event->errorMessage,
            'attempt' => $event->attempt,
        ]);
    }
}
