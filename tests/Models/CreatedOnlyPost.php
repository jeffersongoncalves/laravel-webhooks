<?php

namespace JeffersonGoncalves\Webhooks\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Webhooks\Concerns\CreatedWebhook;
use JeffersonGoncalves\Webhooks\Concerns\ShouldQueueWebhook;

class CreatedOnlyPost extends Model
{
    use CreatedWebhook;
    use ShouldQueueWebhook;

    protected $table = 'posts';

    protected $fillable = ['title', 'body'];

    /**
     * @return array<string, mixed>
     */
    public function webhookCreatedPayload(): array
    {
        return [
            'id' => $this->getKey(),
            'custom' => true,
            'title' => $this->title,
        ];
    }
}
