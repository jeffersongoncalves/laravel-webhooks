<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;
use JeffersonGoncalves\Webhooks\Models\Webhook;
use JeffersonGoncalves\Webhooks\Tests\Models\CreatedOnlyPost;
use JeffersonGoncalves\Webhooks\Tests\Models\Post;
use Spatie\WebhookServer\CallWebhookJob;

it('dispatches a webhook when a model is created', function () {
    Bus::fake();

    Webhook::factory()->forEvents([WebhookEvent::Created])->create([
        'url' => 'https://example.com/hook',
        'model' => Post::class,
    ]);

    Post::create(['title' => 'Hello']);

    Bus::assertDispatched(CallWebhookJob::class, function (CallWebhookJob $job) {
        return $job->webhookUrl === 'https://example.com/hook'
            && $job->payload['event'] === 'created'
            && $job->payload['model'] === Post::class
            && $job->meta['webhook_id'] !== null;
    });
});

it('does not dispatch when no matching webhook is registered', function () {
    Bus::fake();

    Post::create(['title' => 'Hello']);

    Bus::assertNotDispatched(CallWebhookJob::class);
});

it('does not dispatch when the package is disabled', function () {
    config()->set('webhooks.enabled', false);
    Bus::fake();

    Webhook::factory()->forEvents([WebhookEvent::Created])->forModel(Post::class)->create();

    Post::create(['title' => 'Hello']);

    Bus::assertNotDispatched(CallWebhookJob::class);
});

it('only dispatches for events enabled on the model', function () {
    Bus::fake();

    Webhook::factory()->forEvents([WebhookEvent::Updated])->forModel(CreatedOnlyPost::class)->create();

    $post = CreatedOnlyPost::create(['title' => 'Hello']);
    $post->update(['title' => 'Changed']);

    // CreatedOnlyPost only enables the "created" event, so the update must not fire.
    Bus::assertNotDispatched(CallWebhookJob::class);
});

it('uses the event-specific payload override', function () {
    Bus::fake();

    Webhook::factory()->forEvents([WebhookEvent::Created])->forModel(CreatedOnlyPost::class)->create();

    $post = CreatedOnlyPost::create(['title' => 'Custom']);

    Bus::assertDispatched(CallWebhookJob::class, function (CallWebhookJob $job) use ($post) {
        return $job->payload['data'] === [
            'id' => $post->getKey(),
            'custom' => true,
            'title' => 'Custom',
        ];
    });
});

it('reports the events enabled by a model', function () {
    expect(Post::enabledWebhookEvents())->toBe([
        WebhookEvent::Created,
        WebhookEvent::Updated,
        WebhookEvent::Deleted,
    ])->and(CreatedOnlyPost::enabledWebhookEvents())->toBe([
        WebhookEvent::Created,
    ]);
});

it('respects the queue configuration', function () {
    config()->set('webhooks.queue', false);

    $post = new Post(['title' => 'x']);
    expect($post->shouldQueueWebhook())->toBeFalse();

    // The ShouldQueueWebhook trait forces queueing regardless of config.
    $queued = new CreatedOnlyPost(['title' => 'x']);
    expect($queued->shouldQueueWebhook())->toBeTrue();
});
