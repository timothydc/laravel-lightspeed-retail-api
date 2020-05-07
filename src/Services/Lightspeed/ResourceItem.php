<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use Illuminate\Support\Collection;
use TimothyDC\LightspeedRetailApi\Resource;

class ResourceItem extends Resource
{
    protected string $resource = 'Item';
    public string $primaryKey = 'itemID';

    public function get(int $id = null, array $query = []): Collection
    {
        if ($query) {
            $query = collect($query)->only([$this->primaryKey, 'upc', 'ean']);
        }

        return parent::get($id, $query->mapWithKeys(fn($param) => [
            'itemCode' => ['value' => $param['value'], 'operator' => '='],
        ])->toArray());
    }
}
