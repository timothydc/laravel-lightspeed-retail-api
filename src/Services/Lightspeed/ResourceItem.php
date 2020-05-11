<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use Illuminate\Support\Collection;
use TimothyDC\LightspeedRetailApi\Resource;

class ResourceItem extends Resource
{
    public static string $resource = 'Item';
    public string $primaryKey = 'itemID';

    public static string $description = 'description';
    public static string $ean = 'ean';
    public static string $price = 'amount';

    public function get(int $id = null, array $query = []): Collection
    {
        if ($query) {
            $query = collect($query)->only([$this->primaryKey, 'upc', 'ean']);
        }

        return parent::get($id, $query->mapWithKeys(fn($param) => [
            'itemCode' => ['value' => $param['value'], 'operator' => '='],
        ])->toArray());
    }

    public function update(int $id, array $payload): Collection
    {
        if (array_key_exists(self::$price, $payload)) {
            $payload['Prices']['ItemPrice'][] = [
                'amount' => $payload[self::$price],
                'useTypeID' => 1,
                'useType' => 'Default',
            ];

            unset($payload[self::$price]);
        }

        return parent::update($id, $payload);
    }
}
