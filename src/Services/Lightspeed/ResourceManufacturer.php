<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use TimothyDC\LightspeedRetailApi\Resource;

class ResourceManufacturer extends Resource
{
    public static string $resource = 'Manufacturer';
    public string $primaryKey = 'manufacturerID';

    public static string $name = 'name';
}
