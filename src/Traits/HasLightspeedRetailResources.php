<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use TimothyDC\LightspeedRetailApi\Jobs\SendResourceToLightspeedRetail;
use TimothyDC\LightspeedRetailApi\Models\LightspeedRetailResource;

trait HasLightspeedRetailResources
{
    // public static function lightspeedRetailResourceMapping() { return ['API resource column (case sensitive)' => 'Your model column']; }
    // public static array $lsRetailApiTriggerEvents = ['created', 'updated', 'deleted'];

    public static function bootHasLightspeedRetailResources(): void
    {
        static::listenToResourceEvents();
    }

    public static function listenToResourceEvents(): void
    {
        if (method_exists(self::class, 'lightspeedRetailResourceMapping') === false || empty(self::lightspeedRetailResourceMapping())) {
            return;
        }

        if (property_exists(self::class, 'lsRetailApiTriggerEvents') === false || empty(self::$lsRetailApiTriggerEvents)) {
            return;
        }

        $mapping = collect(self::lightspeedRetailResourceMapping());

        foreach (self::$lsRetailApiTriggerEvents as $event) {

            if (method_exists(self::class, $event) === false) {
                continue;
            }

            static::$event(function ($model) use ($event, $mapping) {
                if ($event === 'deleted') {
                    // TODO remove resource from LS retail
                    return;
                }

                if ($model->isDirty($mapping->flatten(2)->toArray()) === false) {
                    return;
                }

                // support event specific columns
                if (in_array($event, ['created', 'updated'])) {
                    $mapping = $mapping->get($event, $mapping);
                }

                $payloads = [];

                foreach ($mapping as $resource => $resourceMapping) {
                    foreach ($resourceMapping as $apiColumn => $value) {

                        // default mapping -> the order of these parameters is important
                        $payloads[$resource]['model'] = $model;
                        $payloads[$resource]['resource'] = $resource;
                        $payloads[$resource]['payload'][$apiColumn] = $model->$value;

                        if (Str::contains($value, '.id') === true) {
                            // mapping for foreignkeys
                            $payloads[$resource]['model'] = $model;
                            $payloads[$resource]['payload'][$apiColumn] = $value;

                        } elseif (Str::contains($value, '.') === true) {
                            // mapping for relationship
                            [$relation, $relationValue] = explode('.', $value, 2);

                            // check that the relation exists and isn't NULL
                            if (method_exists($model, $relation) === false || $model->$relation === null) {
                                continue;
                            }

                            $payloads[$resource]['model'] = $model->$relation;
                            $payloads[$resource]['payload'][$apiColumn] = $model->$relation->$relationValue;
                        }
                    }
                }

                $model->sendResourceToLightspeedRetail($payloads);
            });
        }
    }

    protected function sendResourceToLightspeedRetail(array $payloads)
    {
        $payloads = collect($payloads);
        $initialPaylaod = $payloads->shift();

        SendResourceToLightspeedRetail::withChain($payloads
            ->map(fn($payload) => new SendResourceToLightspeedRetail($payload['model'], $payload['resource'], $payload['payload']))
            ->toArray()
        )->dispatch(...array_values($initialPaylaod));
    }

    public function lightspeedRetailResource(): MorphOne
    {
        return $this->morphOne(LightspeedRetailResource::class, 'resource');
    }
}
