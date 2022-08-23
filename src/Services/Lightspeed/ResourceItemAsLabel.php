<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use Illuminate\Support\Collection;
use TimothyDC\LightspeedRetailApi\Resource;
use TimothyDC\LightspeedRetailApi\Services\ApiClient;

class ResourceItemAsLabel extends Resource
{
    public static string $resource = 'DisplayTemplate/ItemAsLabel';
    public string $primaryKey = 'labelID';

    public static string $itemId = 'itemId';
    public static string $template = 'template';

    public function getLabelById(int $id, string $template = null, $asHtml = false)
    {
        $extension = ($asHtml) ? 'html' : 'json';
        $response = $this->client->get(
            self::$resource, $id,
            [
                'template' => $template,
            ],
            [
                'Accept' => '*/*',
                'Content-Type' => 'text/' . $extension
            ],
            $extension,
            'Label'
        );

        return $response;
    }
}
