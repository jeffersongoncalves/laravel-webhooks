<?php

namespace JeffersonGoncalves\Webhooks\Enums;

enum WebhookEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';

    public function label(): string
    {
        return __('webhooks::webhooks.events.'.$this->value);
    }

    /**
     * @return array<WebhookEvent>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
