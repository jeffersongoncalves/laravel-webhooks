<?php

namespace JeffersonGoncalves\Webhooks\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Webhooks\Database\Factories\WebhookFactory;
use JeffersonGoncalves\Webhooks\Enums\WebhookEvent;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string|null $secret
 * @property array<int, string> $events
 * @property string|null $model
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, WebhookLog> $logs
 */
class Webhook extends Model
{
    /** @use HasFactory<WebhookFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'model',
        'is_active',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('webhooks.tables.webhooks', 'webhooks');
    }

    /**
     * @return HasMany<WebhookLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class, 'webhook_id');
    }

    /**
     * Determine whether this webhook listens to the given event.
     */
    public function listensTo(WebhookEvent|string $event): bool
    {
        $value = $event instanceof WebhookEvent ? $event->value : $event;

        return in_array($value, $this->events ?? [], true);
    }

    /**
     * @param  Builder<Webhook>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Webhook>  $query
     */
    public function scopeForModel(Builder $query, string $class): void
    {
        $query->where(function (Builder $query) use ($class) {
            $query->whereNull('model')->orWhere('model', $class);
        });
    }

    /**
     * @param  Builder<Webhook>  $query
     */
    public function scopeForEvent(Builder $query, WebhookEvent|string $event): void
    {
        $value = $event instanceof WebhookEvent ? $event->value : $event;

        $query->whereJsonContains('events', $value);
    }

    protected static function newFactory(): WebhookFactory
    {
        return WebhookFactory::new();
    }
}
