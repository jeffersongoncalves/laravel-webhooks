<?php

namespace JeffersonGoncalves\Webhooks;

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\Webhooks\Listeners\LogWebhookCall;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

class WebhooksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-webhooks')
            ->hasConfigFile()
            ->hasMigrations([
                'create_webhooks_table',
                'create_webhook_logs_table',
            ])
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WebhooksManager::class, fn () => new WebhooksManager);
    }

    public function packageBooted(): void
    {
        if (config('webhooks.logging.enabled', true)) {
            Event::listen(WebhookCallSucceededEvent::class, [LogWebhookCall::class, 'handleSucceeded']);
            Event::listen(WebhookCallFailedEvent::class, [LogWebhookCall::class, 'handleFailed']);
            Event::listen(FinalWebhookCallFailedEvent::class, [LogWebhookCall::class, 'handleFinalFailed']);
        }
    }
}
