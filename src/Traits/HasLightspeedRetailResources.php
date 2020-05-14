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

                // support event specific columns
                if (in_array($event, ['created', 'updated'])) {
                    $mapping = $mapping->get($event, $mapping);
                }

                if ($model->isDirty($mapping->flatten(2)->toArray()) === false) {
                    return;
                }

                $payloads = [];

                // convert mapping to Lightspeed Retail paylaod
                foreach ($mapping as $resource => $resourceMapping) {
                    foreach ($resourceMapping as $apiColumn => $value) {

                        // map the "dirty" column with the mutated value
                        if (is_array($value)) {
                            $value = last($value);
                        }

                        if (Str::contains($value, '.') === true) {
                            // mapping for relationship
                            [$relation, $relationValue] = explode('.', $value, 2);

                            // check that the relation exists and isn't NULL
                            if (method_exists($model, $relation) === false || $model->$relation === null) {
                                continue;
                            }

                            $payloads[$resource]['model'] = $model->$relation;
                            $payloads[$resource]['resource'] = $resource;
                            $payloads[$resource]['payload'][$apiColumn] = Str::contains($value, '.id') === true ? $value : $model->$relation->$relationValue;

                        } else {
                            // default mapping -> the order of these parameters is important
                            $payloads[$resource]['model'] = $model;
                            $payloads[$resource]['resource'] = $resource;
                            $payloads[$resource]['payload'][$apiColumn] = $model->$value;
                        }
                    }
                }

                if (empty($payloads)) {
                    return;
                }

                $payloads = collect($payloads);

                // send payload to processor
                if (config('lightspeed-retail.api.async') === true) {
                    SendResourceToLightspeedRetail::dispatch(...array_values($payloads->shift()))->chain($payloads->map(fn($payload) => new SendResourceToLightspeedRetail(...array_values($payload)))->toArray());

                } else {
                    $payloads->each(fn($payload) => SendResourceToLightspeedRetail::dispatchNow(...array_values($payload)));
                }
            });
        }
    }

    public function lightspeedRetailResource(): MorphOne
    {
        return $this->morphOne(LightspeedRetailResource::class, 'resource');
    }
}
