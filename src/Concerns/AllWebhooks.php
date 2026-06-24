<?php

namespace JeffersonGoncalves\Webhooks\Concerns;

/**
 * Dispatch webhooks for every lifecycle event (created, updated, deleted).
 */
trait AllWebhooks
{
    use CreatedWebhook;
    use DeletedWebhook;
    use UpdatedWebhook;
}
