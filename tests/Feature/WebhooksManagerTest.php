<?php

use Illuminate\Support\Facades\Bus;
use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;
use JeffersonGoncalves\Webhooks\Facades\Webhooks;
use JeffersonGoncalves\Webhooks\Models\Webhook;
use JeffersonGoncalves\Webhooks\Models\WebhookLog;
use JeffersonGoncalves\Webhooks\Tests\Models\Post;
use JeffersonGoncalves\Webhooks\WebhooksManager;
use Spatie\WebhookServer\CallWebhookJob;

it('resolves the manager from the container and facade', function () {
    expect(app(WebhooksManager::class))->toBeInstanceOf(WebhooksManager::class)
        ->and(Webhooks::getFacadeRoot())->toBeInstanceOf(WebhooksManager::class);
});

it('queues the webhook job when queue is true', function () {
    Bus::fake();

    $webhook = Webhook::factory()->create(['url' => 'https://example.com/hook']);
    $post = new Post(['title' => 'Hi']);

    app(WebhooksManager::class)->dispatch($webhook, $post, WebhookEvent::Created, ['foo' => 'bar'], true);

    Bus::assertDispatched(CallWebhookJob::class, function (CallWebhookJob $job) {
        return $job->payload['data'] === ['foo' => 'bar']
            && $job->meta['event'] === 'created';
    });
});

it('dispatches synchronously when queue is false', function () {
    Bus::fake();

    $webhook = Webhook::factory()->create(['url' => 'https://example.com/hook']);
    $post = new Post(['title' => 'Hi']);

    app(WebhooksManager::class)->dispatch($webhook, $post, WebhookEvent::Created, ['foo' => 'bar'], false);

    Bus::assertDispatchedSync(CallWebhookJob::class);
});

it('signs the request only when a secret is present', function () {
    Bus::fake();

    $signed = Webhook::factory()->withSecret('top-secret')->create();
    app(WebhooksManager::class)->dispatch($signed, new Post(['title' => 'a']), WebhookEvent::Created, [], true);

    Bus::assertDispatched(CallWebhookJob::class);
});

it('persists a log entry when running a test dispatch', function () {
    $webhook = Webhook::factory()->create(['url' => 'https://example.com/hook']);

    // The dispatchSync path fires the success/failure event which the
    // listener records. We fake Bus to avoid a real HTTP request and assert
    // the test() method returns a WebhookLog instance.
    Bus::fake();

    $log = Webhooks::test($webhook);

    expect($log)->toBeInstanceOf(WebhookLog::class)
        ->and($log->url)->toBe('https://example.com/hook');

    Bus::assertDispatchedSync(CallWebhookJob::class);
});
