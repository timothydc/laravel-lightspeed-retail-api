<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Traits;

use Illuminate\Support\Collection;
use TimothyDC\LightspeedRetailApi\Services\ApiClient;

trait ResourceMethods
{
    private ApiClient $client;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    public function first(int $id = null): Collection
    {
        return $this->get($id, true);
    }

    public function get(int $id = null, bool $first = false): Collection
    {
        if ($first || $id) {
            return collect($this->client->get($this->resource, $id)->first());
        }

        return $this->client->get($this->resource, $id);
    }

    public function create(array $payload): Collection
    {
        return $this->client->post($this->resource, $payload);
    }

    public function update(int $id, array $payload): Collection
    {
        return $this->client->put($this->resource, $id, $payload);
    }

    public function delete(int $id): Collection
    {
        return $this->client->delete($this->resource, $id);
    }
}
