<?php

namespace JeffersonGoncalves\Webhooks\Concerns;

/**
 * Dispatch webhooks when the model is deleted.
 */
trait DeletedWebhook
{
    use SendsWebhooks;

    /**
     * The payload sent when the model is deleted. Override to customise.
     *
     * @return array<string, mixed>
     */
    public function webhookDeletedPayload(): array
    {
        return $this->toArray();
    }
}
