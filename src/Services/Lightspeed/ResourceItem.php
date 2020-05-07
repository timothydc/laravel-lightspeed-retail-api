<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use TimothyDC\LightspeedRetailApi\Traits\ResourceMethods;

class ResourceItem
{
    use ResourceMethods;

    private string $resource = 'Item';
    public string $primaryKey = 'itemID';
}
