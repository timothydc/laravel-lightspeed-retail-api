<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Models;

use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    protected $table = 'api_tokens';

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $guarded = [];

}
