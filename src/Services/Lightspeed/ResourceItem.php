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
    public static string $shopId = 'shopId';
    public static string $defaultQty = 'defaultyQty';

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
        return parent::update($id, $this->formatPayload($payload, $id, true));
    }

    protected function formatPayload(array $payload, int $id = null, $updating = false): array
    {
        $payload = $this->adjustPricePayload($payload);
        $payload = $this->adjustQuantityPayload($payload, $updating, $id);
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

    private function adjustQuantityPayload(array $payload, $updating = false, $id = null): array
    {
        if (array_key_exists(self::$shopId, $payload) && array_key_exists(self::$defaultQty, $payload)) {
            if(!$updating) {
                $payload['ItemShops']['ItemShop'][] = [
                    'qoh' => $payload[self::$defaultQty],
                    'shopID' => $payload[self::$shopId],
                ];
            } else {
                $shopId = $this->getItemShopId($id, $payload[self::$shopId]);
                //If we can't find our shop id, don't try to update our quantity
                if($shopId === null) {
                    unset($payload[self::$defaultQty]);
                    unset($payload[self::$shopId]);
                    return $payload;
                }
                $payload['ItemShops']['ItemShop'][] = [
                    'itemShopID' => $shopId,
                    'qoh' => $payload[self::$defaultQty],
                ];
            }

            unset($payload[self::$defaultQty]);
            unset($payload[self::$shopId]);
        }

        return $payload;
    }

    public function getItemShopId($id, $shopId)
    {
        $item = $this->client->item()->get($id, ['load_relations' => ['ItemShops']]);
        foreach($item['ItemShops']['ItemShop'] as $itemShop) {
            if($itemShop['shopID'] == $shopId) {
                return $itemShop['itemShopID'];
            }
        }
        return null;
    }
}
