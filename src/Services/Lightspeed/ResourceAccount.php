<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use Illuminate\Support\Collection;
use TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException;
use TimothyDC\LightspeedRetailApi\Resource;

class ResourceAccount extends Resource
{
    public static string $resource = '';
    public string $primaryKey = 'accountID';

    public function get(int $id = null, array $query = []): Collection
    {
        return parent::get();
    }

    /**
     * @throws LightspeedRetailException
     */
    public function create(array $payload): Collection
    {
        throw new LightspeedRetailException(trans('Action not allowed for this resource.'));
    }

    /**
     * @throws LightspeedRetailException
     */
    public function update(int $id, array $payload): Collection
    {
        throw new LightspeedRetailException(trans('Action not allowed for this resource.'));
    }

    /**
     * @throws LightspeedRetailException
     */
    public function delete(int $id): Collection
    {
        throw new LightspeedRetailException(trans('Action not allowed for this resource.'));
    }
}
