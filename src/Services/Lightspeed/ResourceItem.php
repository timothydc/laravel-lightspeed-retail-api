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
    public static string $categoryId = 'categoryID';
    public static string $taxId = 'taxClassID';
    public static string $manufacturerSku = 'manufacturerSku';
    public static string $customSku = 'customSku';
    public static string $modelYear = 'modelYear';
    public static string $tax = 'tax';
    public static string $defaultCost = 'defaultCost';
    public static string $note = 'Note';
    public static string $customFields = 'CustomFieldValues';

    /**
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException
     */
    public function create(array $payload): Collection
    {
        $payload = $this->formatPayload($payload);

        try {
            // create new API resource
            $response = $this->client->post(static::$resource, $payload);

            // instantly archive item if needed
            if (array_key_exists(self::$archived, $payload) && $payload[self::$archived] === true) {
                $this->delete((int)$response->get($this->primaryKey));
            }

            return $response;
        } catch (DuplicateResourceException $e) {
            // request existing API resource
            return $this->client->get(
                static::$resource,
                null,
                collect($payload)
                    ->only([$this->primaryKey, self::$upc, self::$ean])
                    ->mapWithKeys(fn ($param) => ['itemCode' => ['operator' => '=', 'value' => $param]])
                    ->toArray()
            );
        }
    }

    public function update(int $id, array $payload): Collection
    {
        return parent::update($id, $this->formatPayload($payload, $id));
    }

    protected function formatPayload(array $payload, int $id = null): array
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
