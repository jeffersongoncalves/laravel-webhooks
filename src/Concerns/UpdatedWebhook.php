<?php

namespace JeffersonGoncalves\Webhooks\Concerns;

/**
 * Dispatch webhooks when the model is updated.
 */
trait UpdatedWebhook
{
    use SendsWebhooks;

    /**
     * The payload sent when the model is updated. Override to customise.
     *
     * @return array<string, mixed>
     */
    public function webhookUpdatedPayload(): array
    {
        return $this->toArray();
    }
}
