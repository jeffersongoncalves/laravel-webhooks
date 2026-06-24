<?php

namespace JeffersonGoncalves\Webhooks\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\Webhooks\WebhooksManager;

/**
 * @method static void dispatch(\JeffersonGoncalves\Webhooks\Models\Webhook $webhook, \Illuminate\Database\Eloquent\Model $model, \JeffersonGoncalves\Webhooks\Enums\WebhookEvent $event, array $payload, bool $queue = true)
 * @method static \JeffersonGoncalves\Webhooks\Models\WebhookLog test(\JeffersonGoncalves\Webhooks\Models\Webhook $webhook)
 *
 * @see WebhooksManager
 */
class Webhooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WebhooksManager::class;
    }
}
