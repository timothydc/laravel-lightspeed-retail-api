<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Actions;

use Illuminate\Database\Eloquent\Model;
use TimothyDC\LightspeedRetailApi\Jobs\RemoveResourceFromLightspeedRetail;

class RemoveResourceFromLightspeedRetailAction
{
    public function execute(Model $model, string $resource, int $lightspeedId): void
    {
        // send to processor
        if (config('lightspeed-retail.api.async') === true) {
            RemoveResourceFromLightspeedRetail::dispatch($model, $resource, $lightspeedId);

        } else {
            RemoveResourceFromLightspeedRetail::dispatchNow($model, $resource, $lightspeedId);
        }
    }
}
