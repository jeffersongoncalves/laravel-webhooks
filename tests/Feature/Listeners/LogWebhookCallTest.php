<?php

use GuzzleHttp\Psr7\Response;
use JeffersonGoncalves\Webhooks\Models\Webhook;
use JeffersonGoncalves\Webhooks\Models\WebhookLog;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

function makeWebhookEvent(string $class, ?Response $response, ?string $error, array $meta): object
{
    return new $class(
        'POST',
        'https://example.com/hook',
        ['event' => 'created', 'data' => ['id' => 1]],
        [],
        $meta,
        [],
        1,
        $response,
        $error ? 'Error' : null,
        $error,
        'uuid-123',
        null,
    );
}

it('logs a successful webhook call', function () {
    $webhook = Webhook::factory()->create();

    event(makeWebhookEvent(
        WebhookCallSucceededEvent::class,
        new Response(200, [], 'OK'),
        null,
        ['webhook_id' => $webhook->id, 'event' => 'created'],
    ));

    $log = WebhookLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->webhook_id)->toBe($webhook->id)
        ->and($log->event)->toBe('created')
        ->and($log->success)->toBeTrue()
        ->and($log->response_code)->toBe(200)
        ->and($log->response_body)->toBe('OK');
});

it('logs a failed webhook call', function () {
    $webhook = Webhook::factory()->create();

    event(makeWebhookEvent(
        WebhookCallFailedEvent::class,
        new Response(500, [], 'Server Error'),
        'Something went wrong',
        ['webhook_id' => $webhook->id, 'event' => 'created'],
    ));

    $log = WebhookLog::query()->first();

    expect($log->success)->toBeFalse()
        ->and($log->response_code)->toBe(500)
        ->and($log->error_message)->toBe('Something went wrong');
});

it('logs a final failed webhook call', function () {
    $webhook = Webhook::factory()->create();

    event(makeWebhookEvent(
        FinalWebhookCallFailedEvent::class,
        null,
        'Gave up after retries',
        ['webhook_id' => $webhook->id, 'event' => 'created'],
    ));

    $log = WebhookLog::query()->first();

    expect($log->success)->toBeFalse()
        ->and($log->response_code)->toBeNull()
        ->and($log->error_message)->toBe('Gave up after retries');
});

it('does not log when logging is disabled', function () {
    config()->set('webhooks.logging.enabled', false);
    $webhook = Webhook::factory()->create();

    event(makeWebhookEvent(
        WebhookCallSucceededEvent::class,
        new Response(200, [], 'OK'),
        null,
        ['webhook_id' => $webhook->id, 'event' => 'created'],
    ));

    expect(WebhookLog::query()->count())->toBe(0);
});
