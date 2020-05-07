<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi;

use Illuminate\Support\Collection;
use TimothyDC\LightspeedRetailApi\Services\ApiClient;

class Resource
{
    private ApiClient $client;
    protected string $resource;

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
            return $this->client->get($this->resource, $id);
        }

        return $this->client->get($this->resource, $id, $query);
    }

    /**
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\DuplicateResourceException
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException
     */
    public function create(array $payload): Collection
    {
        return $this->client->post($this->resource, $payload);
    }

    /**
     * @throws \TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException
     */
    public function update(int $id, array $payload): Collection
    {
        return $this->client->put($this->resource, $id, $payload);
    }

    public function delete(int $id): Collection
    {
        return $this->client->delete($this->resource, $id);
    }
}
