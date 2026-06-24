<?php

namespace JeffersonGoncalves\Webhooks\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Webhooks\Concerns\AllWebhooks;

class Post extends Model
{
    use AllWebhooks;

    protected $table = 'posts';

    protected $fillable = ['title', 'body'];
}
