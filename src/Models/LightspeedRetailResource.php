<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LightspeedRetailResource extends Model
{
    protected $casts = [
        'resource_id' => 'integer',
        'lightspeed_id' => 'integer',
    ];

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

    public function getRetailLink(): string
    {
        return strtolower(sprintf('https://us.merchantos.com/?name=%s.views.%s&form_name=view&id=%d', $this->lightspeed_type, $this->lightspeed_type, $this->lightspeed_id));
    }
}
