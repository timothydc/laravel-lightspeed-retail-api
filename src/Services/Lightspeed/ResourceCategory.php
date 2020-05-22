<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use TimothyDC\LightspeedRetailApi\Resource;

class ResourceCategory extends Resource
{
    public static string $resource = 'Category';
    public string $primaryKey = 'categoryID';

    public static string $name = 'name';
    public static string $parentId = 'parentID';
    public static string $fullPathName = 'fullPathName';
}
