<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Traits;

use TimothyDC\LightspeedRetailApi\Jobs\SendResourceToLightspeedRetail;

trait HasLightspeedRetailResources
{
    // public static array $lsRetailApiResourceMapping = ['API resource column (case sensitive)' => 'Your model column'];
    public static array $lsRetailApiTriggerEvents = ['created', 'updated', 'deleted'];

    public static function bootHasLightspeedRetailResources(): void
    {
        static::listenToResourceEvents();
    }

    public static function listenToResourceEvents(): void
    {
        if (property_exists(self::class, 'lsRetailApiTriggerEvents') === false || empty(self::$lsRetailApiTriggerEvents)) {
            return;
        }

        if (property_exists(self::class, 'lsRetailApiResourceMapping') === false || empty(self::$lsRetailApiResourceMapping)) {
            return;
        }

        $mapping = self::$lsRetailApiResourceMapping;

        foreach (self::$lsRetailApiTriggerEvents as $event) {

            static::$event(function ($model) use ($event, $mapping) {
                if ($event === 'deleted') {
                    // TODO remove resource from LS retail
                    return;
                }

                // support event specific columns
                if (in_array($event, ['created', 'updated'])) {
                    $mapping = $mapping[$event] ?? $mapping;
                }

                if ($model->isDirty($mapping) === false) {
                    return;
                }

                $payloads = [];

                foreach ($mapping as $key => $value) {
                    [$resource, $apiColumn] = explode('.', $key);
                    $payloads[$resource][$apiColumn] = $model->$value;
                }

                foreach ($payloads as $resource => $payload) {
                    SendResourceToLightspeedRetail::dispatch($model, $resource, $payload);
                }

            });
        }
    }
}