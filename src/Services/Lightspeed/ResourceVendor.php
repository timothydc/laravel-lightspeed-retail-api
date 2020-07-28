<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use TimothyDC\LightspeedRetailApi\Resource;

class ResourceVendor extends Resource
{
    public static string $resource = 'Vendor';
    public string $primaryKey = 'vendorID';

    public static string $name = 'name';
}
