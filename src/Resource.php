<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi;

use Illuminate\Support\Collection;
use TimothyDC\LightspeedRetailApi\Services\ApiClient;

class Resource
{
    private ApiClient $client;
    public static string $resource;

    public string $primaryKey;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    public function first(int $id): Collection
    {
        return collect($this->get($id)->first());
    }

    public function get(int $id = null, array $query = []): Collection
    {
        if ($id) {
            return $this->client->get(static::$resource, $id);
        }

        return $this->client->get(static::$resource, $id, $query);
    }

    /**
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\DuplicateResourceException
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException
     */
    public function create(array $payload): Collection
    {
        return $this->client->post(static::$resource, $payload);
    }

    /**
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException
     */
    public function update(int $id, array $payload): Collection
    {
        return $this->client->put(static::$resource, $id, $payload);
    }

    public function delete(int $id): Collection
    {
        return $this->client->delete(self::$resource, $id);
    }
}
