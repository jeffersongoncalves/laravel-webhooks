<?php

namespace JeffersonGoncalves\Webhooks\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;
use JeffersonGoncalves\Webhooks\Models\Webhook;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'url' => fake()->url(),
            'secret' => null,
            'events' => [WebhookEvent::Created->value],
            'model' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withSecret(string $secret = 'super-secret'): static
    {
        return $this->state(fn () => ['secret' => $secret]);
    }

    /**
     * @param  array<int, WebhookEvent|string>  $events
     */
    public function forEvents(array $events): static
    {
        return $this->state(fn () => [
            'events' => array_map(
                fn ($event) => $event instanceof WebhookEvent ? $event->value : $event,
                $events,
            ),
        ]);
    }

    public function forModel(string $class): static
    {
        return $this->state(fn () => ['model' => $class]);
    }
}
