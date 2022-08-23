<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Traits;

use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceAccount;
use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceCategory;
use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceCustomer;
use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceItem;
use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceItemAsLabel;
use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceManufacturer;
use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceSale;
use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceVendor;

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

    public function customer(): ResourceCustomer
    {
        return new ResourceCustomer($this);
    }

    public function item(): ResourceItem
    {
        return new ResourceItem($this);
    }

    public function itemAsLabel(): ResourceItemAsLabel
    {
        return new ResourceItemAsLabel($this);
    }

    public function manufacturer(): ResourceManufacturer
    {
        return new ResourceManufacturer($this);
    }

    public function sale(): ResourceSale
    {
        return new ResourceSale($this);
    }

    public function vendor(): ResourceVendor
    {
        return new ResourceVendor($this);
    }
}
