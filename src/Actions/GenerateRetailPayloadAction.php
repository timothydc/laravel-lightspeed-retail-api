<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GenerateRetailPayloadAction
{
    public function execute(Model $model, bool $forcePayload = false): array
    {
        if (method_exists($model, 'lightspeedRetailResourceMapping') === false || empty($model::lightspeedRetailResourceMapping())) {
            return [];
        }

        $mapping = $model::lightspeedRetailResourceMapping();

        $relationPayloads = [];
        $payloads = [];

        foreach ($mapping as $resource => $resourceMapping) {
            foreach ($resourceMapping as $apiColumn => $attribute) {

                $localAttribute = $attribute;
                $value = $attribute;

                // map the "dirty" column with the mutated value
                if (is_array($attribute)) {
                    $localAttribute = head($attribute);
                    $value = last($attribute);
                }

                if ($forcePayload === false && !in_array($localAttribute, $model->lsForceSyncFields ?? [], true) && $model->isDirty($localAttribute) === false) {
                    continue;
                }

                if (Str::contains($value, '.') === true) {
                    // mapping for relationship
                    [$relation, $relationValue] = explode('.', $value, 2);

                    // check that the method and relation exists
                    if (method_exists($model, $relation) === false || $model->$relation === null) {
                        continue;
                    }

                    // get related model
                    $freshRelation = $model->$relation()->first();

                    if (Str::contains($value, '.id') === true || is_null($freshRelation) === true) {
                        $resourceColumnValue = $value;

                    } else {
                        $resourceColumnValue = $freshRelation->$relationValue;
                    }

                    // check that the related resource was synchronised
                    if (Str::contains($value, '.id') === true && $freshRelation && $freshRelation->lightspeedRetailResource()->exists() === false) {
                        // resource was not synchronised, prepare payload
                        $relationPayloads = array_merge($relationPayloads, $this->execute($freshRelation, true));
                    }

                } else {
                    $resourceColumnValue = $model->$value;
                }

                // default mapping -> the order of these parameters is important
                $payloads[$resource]['model'] = $model->withoutRelations();
                $payloads[$resource]['resource'] = $resource;
                $payloads[$resource]['payload'][$apiColumn] = $resourceColumnValue;
            }
        }

        return array_merge($relationPayloads, $payloads);
    }
}
