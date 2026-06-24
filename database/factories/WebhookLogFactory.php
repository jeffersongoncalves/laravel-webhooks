<?php

namespace JeffersonGoncalves\Webhooks\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;
use JeffersonGoncalves\Webhooks\Models\Webhook;
use JeffersonGoncalves\Webhooks\Models\WebhookLog;

/**
 * @extends Factory<WebhookLog>
 */
class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    public function definition(): array
    {
        return [
            'webhook_id' => Webhook::factory(),
            'event' => WebhookEvent::Created->value,
            'url' => fake()->url(),
            'payload' => ['event' => WebhookEvent::Created->value, 'data' => []],
            'response_code' => 200,
            'response_body' => 'OK',
            'success' => true,
            'error_message' => null,
            'attempt' => 1,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'response_code' => 500,
            'response_body' => 'Internal Server Error',
            'success' => false,
            'error_message' => 'The webhook endpoint returned an error.',
        ]);
    }
}
