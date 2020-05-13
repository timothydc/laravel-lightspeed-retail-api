<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Actions;

use TimothyDC\LightspeedRetailApi\Models\LightspeedRetailResource;

class SaveLightspeedRetailResourceAction
{
    public function execute(array $data, LightspeedRetailResource $resource = null): LightspeedRetailResource
    {
        if ($resource) {
            return $this->update($data, $resource);
        }

        return $this->create($data);
    }

    private function create(array $data): LightspeedRetailResource
    {
        $resource = new LightspeedRetailResource($data);
        $resource->save();

        return $resource;
    }

    private function update(array $data, LightspeedRetailResource $resource): LightspeedRetailResource
    {
        $resource->update($data);

        return $resource;
    }
}
