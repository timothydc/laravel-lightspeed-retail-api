<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LightspeedRetailResource extends Model
{
    protected $fillable = [
        'resource_id',
        'resource_type',
        'lightspeed_id',
        'lightspeed_type',
    ];

    public function resource(): MorphTo
    {
        return $this->morphTo();
    }
}
