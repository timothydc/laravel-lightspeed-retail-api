<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Traits;

use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceAccount;
use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceCategory;

trait RetailResources
{
    public function account(): ResourceAccount
    {
        return new ResourceAccount($this);
    }

    public function category(): ResourceCategory
    {
        return new ResourceCategory($this);
    }
}
