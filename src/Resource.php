<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi;

use Illuminate\Support\Collection;
use TimothyDC\LightspeedRetailApi\Exceptions\DuplicateResourceException;
use TimothyDC\LightspeedRetailApi\Services\ApiClient;

class Resource
{
    protected ApiClient $client;
    public static string $resource;

    public string $primaryKey;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    public function first(int $id, array $query = []): Collection
    {
        return collect($this->get($id, $query)->first());
    }

    public function get(int $id = null, array $query = []): Collection
    {
        return $this->client->get(static::$resource, $id, $query);
    }

    /**
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException
     */
    public function create(array $payload): Collection
    {
        try {
            // create new API resource
            return $this->client->post(static::$resource, $payload);
        } catch (DuplicateResourceException $e) {
            // request existing API resource
            return $this->client->get(
                static::$resource,
                null,
                collect($payload)
                    ->map(fn ($param) => ['value' => $param])
                    ->toArray()
            );
        }
    }

    /**
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException
     */
    public function update(int $id, array $payload): Collection
    {
        if (empty($payload)) {
            return collect([$this->primaryKey => $id] + $payload);
        }

        return $this->client->put(static::$resource, $id, $payload);
    }

    public function delete(int $id): Collection
    {
        return $this->client->delete(static::$resource, $id);
    }
}
