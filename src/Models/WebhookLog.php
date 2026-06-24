<?php

namespace JeffersonGoncalves\Webhooks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Webhooks\Database\Factories\WebhookLogFactory;

/**
 * @property int $id
 * @property int|null $webhook_id
 * @property string|null $event
 * @property string $url
 * @property array<string, mixed>|null $payload
 * @property int|null $response_code
 * @property string|null $response_body
 * @property bool $success
 * @property string|null $error_message
 * @property int $attempt
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Webhook|null $webhook
 */
class WebhookLog extends Model
{
    /** @use HasFactory<WebhookLogFactory> */
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event',
        'url',
        'payload',
        'response_code',
        'response_body',
        'success',
        'error_message',
        'attempt',
    ];

    protected $casts = [
        'payload' => 'array',
        'success' => 'boolean',
        'response_code' => 'integer',
        'attempt' => 'integer',
    ];

    public function getTable(): string
    {
        return config('webhooks.tables.webhook_logs', 'webhook_logs');
    }

    /**
     * @return BelongsTo<Webhook, $this>
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class, 'webhook_id');
    }

    protected static function newFactory(): WebhookLogFactory
    {
        return WebhookLogFactory::new();
    }
}
