<?php

use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;

it('exposes the expected string values', function () {
    expect(WebhookEvent::Created->value)->toBe('created')
        ->and(WebhookEvent::Updated->value)->toBe('updated')
        ->and(WebhookEvent::Deleted->value)->toBe('deleted');
});

it('returns a translated label', function () {
    expect(WebhookEvent::Created->label())->toBe('Created')
        ->and(WebhookEvent::Updated->label())->toBe('Updated')
        ->and(WebhookEvent::Deleted->label())->toBe('Deleted');
});

it('lists all cases', function () {
    expect(WebhookEvent::all())->toBe([
        WebhookEvent::Created,
        WebhookEvent::Updated,
        WebhookEvent::Deleted,
    ]);
});
