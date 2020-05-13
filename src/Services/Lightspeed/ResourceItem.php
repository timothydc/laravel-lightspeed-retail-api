<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use Illuminate\Support\Collection;
use TimothyDC\LightspeedRetailApi\Exceptions\DuplicateResourceException;
use TimothyDC\LightspeedRetailApi\Resource;

class ResourceItem extends Resource
{
    public static string $resource = 'Item';
    public string $primaryKey = 'itemID';

    public static string $description = 'description';
    public static string $ean = 'ean';
    public static string $defaultPrice = 'amount';
    public static string $manufacturerId = 'manufacturerID';
    public static string $upc = 'upc';

    /**
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException
     */
    public function create(array $payload): Collection
    {
        try {
            // create new API resource
            return $this->client->post(static::$resource, $this->formatPayload($payload));

        } catch (DuplicateResourceException $e) {
            // request existing API resource
            return $this->client->get(static::$resource, null, collect($this->formatPayload($payload))
                ->only([$this->primaryKey, self::$upc, self::$ean])
                ->mapWithKeys(fn($param) => ['itemCode' => ['operator' => '=', 'value' => $param['value']]])
                ->toArray())
                ->first();
        }
    }

    public function update(int $id, array $payload): Collection
    {
        return parent::update($id, $this->formatPayload($payload));
    }

    protected function formatPayload(array $payload): array
    {
        $payload = $this->adjustPricePayload($payload);
        return $payload;
    }

    private function adjustPricePayload(array $payload): array
    {
        if (array_key_exists(self::$defaultPrice, $payload)) {
            $payload['Prices']['ItemPrice'][] = [
                'amount' => $payload[self::$defaultPrice],
                'useTypeID' => 1,
                'useType' => 'Default',
            ];

            unset($payload[self::$defaultPrice]);
        }

        return $payload;
    }
}
