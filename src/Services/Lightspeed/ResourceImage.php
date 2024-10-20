<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use TimothyDC\LightspeedRetailApi\Resource;

class ResourceImage extends Resource
{
    public static string $resource = 'Image';
    public string $primaryKey = 'imageID';

    public static string $description = 'description';
    public static string $filename = 'filename';
    public static string $publicID = 'publicID';
    public static string $itemID = 'itemID';
    public static string $Item = 'Item';
    public static string $ItemMatrix = 'ItemMatrix';
}
