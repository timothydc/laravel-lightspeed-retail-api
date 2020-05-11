<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
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
                        $payloads[$resource][$apiColumn] = $model->$value;
                    }
                }

                foreach ($payloads as $resource => $payload) {
                    SendResourceToLightspeedRetail::dispatchNow($model, $resource, $payload);
                }

            });
        }
    }

    public function lightspeedRetailResource(): MorphOne
    {
        return $this->morphOne(LightspeedRetailResource::class, 'resource');
    }
}
