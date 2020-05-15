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
    public static string $vendorId = 'defaultVendorID';
    public static string $upc = 'upc';
    public static string $archived = 'archived';

    /**
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException
     */
    public function create(array $payload): Collection
    {
        try {
            $originalPayload = $payload;

            // create new API resource
            $response = $this->client->post(static::$resource, $this->formatPayload($payload));

            // instantly archive item if needed
            if (array_key_exists(self::$archived, $originalPayload) && $originalPayload[self::$archived] === true) {
                $this->delete((int)$response->get($this->primaryKey));
            }

            return $response;

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
        return parent::update($id, $this->formatPayload($payload, $id));
    }

    protected function formatPayload(array $payload, int $id = null): array
    {
        $payload = $this->filterOutArchive($id, $payload);
        $payload = $this->adjustPricePayload($payload);
        return $payload;
    }

    private function filterOutArchive(?int $id, array $payload): array
    {
        if (array_key_exists(self::$archived, $payload) && $payload[self::$archived] === true) {
            // remove archive parameter from payload
            unset($payload[self::$archived]);

            if ($id) {
                $this->delete($id);
            }
        }

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
