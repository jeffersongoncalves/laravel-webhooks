<?php

use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;
use JeffersonGoncalves\Webhooks\Models\Webhook;
use JeffersonGoncalves\Webhooks\Models\WebhookLog;
use JeffersonGoncalves\Webhooks\Tests\Models\Post;

it('casts attributes correctly', function () {
    $webhook = Webhook::factory()->create([
        'events' => [WebhookEvent::Created->value, WebhookEvent::Updated->value],
        'is_active' => true,
    ]);

    expect($webhook->events)->toBe(['created', 'updated'])
        ->and($webhook->is_active)->toBeTrue();
});

it('scopes active webhooks', function () {
    Webhook::factory()->create();
    Webhook::factory()->inactive()->create();

    expect(Webhook::query()->active()->count())->toBe(1);
});

it('scopes webhooks for a given model including global ones', function () {
    Webhook::factory()->create(['model' => null]);
    Webhook::factory()->forModel(Post::class)->create();
    Webhook::factory()->forModel('App\\Models\\Other')->create();

    expect(Webhook::query()->forModel(Post::class)->count())->toBe(2);
});

it('scopes webhooks for a given event', function () {
    Webhook::factory()->forEvents([WebhookEvent::Created])->create();
    Webhook::factory()->forEvents([WebhookEvent::Updated])->create();

    expect(Webhook::query()->forEvent(WebhookEvent::Created)->count())->toBe(1)
        ->and(Webhook::query()->forEvent('updated')->count())->toBe(1);
});

it('detects which events it listens to', function () {
    $webhook = Webhook::factory()->forEvents([WebhookEvent::Created])->create();

    expect($webhook->listensTo(WebhookEvent::Created))->toBeTrue()
        ->and($webhook->listensTo(WebhookEvent::Deleted))->toBeFalse();
});

it('has many logs', function () {
    $webhook = Webhook::factory()->create();
    WebhookLog::factory()->count(2)->create(['webhook_id' => $webhook->id]);

    expect($webhook->logs)->toHaveCount(2)
        ->and($webhook->logs->first()->webhook->is($webhook))->toBeTrue();
});
