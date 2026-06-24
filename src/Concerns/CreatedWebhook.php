<?php

namespace JeffersonGoncalves\Webhooks\Concerns;

/**
 * Dispatch webhooks when the model is created.
 */
trait CreatedWebhook
{
    use SendsWebhooks;

    /**
     * The payload sent when the model is created. Override to customise.
     *
     * @return array<string, mixed>
     */
    public function webhookCreatedPayload(): array
    {
        return $this->toArray();
    }
}
