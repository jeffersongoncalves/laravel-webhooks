<?php

use JeffersonGoncalves\Webhooks\Models\Webhook;
use JeffersonGoncalves\Webhooks\Models\WebhookLog;

return [

    /*
    |--------------------------------------------------------------------------
    | Master Switch
    |--------------------------------------------------------------------------
    |
    | When disabled, no webhooks will be dispatched, regardless of how many
    | webhooks are registered or which model traits are used. Useful for
    | turning the whole feature off in a specific environment.
    |
    */

    'enabled' => env('WEBHOOKS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | When logging is enabled, every webhook call (success or failure) is
    | persisted to the webhook_logs table so you can audit deliveries and
    | inspect response codes/bodies and error messages.
    |
    */

    'logging' => [
        'enabled' => env('WEBHOOKS_LOGGING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Date Format
    |--------------------------------------------------------------------------
    |
    | The date format used when serializing timestamps inside webhook
    | payloads and metadata.
    |
    */

    'date_format' => 'Y-m-d H:i:s',

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | When enabled, webhook HTTP requests are pushed onto the queue
    | (via spatie/laravel-webhook-server). When disabled, the request is
    | sent synchronously during the model event. Individual models may
    | override this through the ShouldQueueWebhook trait.
    |
    */

    'queue' => env('WEBHOOKS_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | The database table names used by the package. Adjust these if you need
    | to avoid collisions with existing tables in your application.
    |
    */

    'tables' => [
        'webhooks' => 'webhooks',
        'webhook_logs' => 'webhook_logs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | The Eloquent models used by the package. You may swap these for your own
    | implementations as long as they extend the shipped models.
    |
    */

    'models' => [
        'webhook' => Webhook::class,
        'webhook_log' => WebhookLog::class,
    ],

];
