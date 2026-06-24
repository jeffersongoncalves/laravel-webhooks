<?php

namespace JeffersonGoncalves\Webhooks\Concerns;

/**
 * Force webhook HTTP requests for this model to always be queued,
 * regardless of the global `webhooks.queue` configuration value.
 */
trait ShouldQueueWebhook
{
    protected bool $forceQueueWebhook = true;
}
