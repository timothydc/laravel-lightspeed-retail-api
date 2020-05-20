<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use TimothyDC\LightspeedRetailApi\Actions\GenerateRetailPayloadAction;
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
        if (property_exists(self::class, 'lsRetailApiTriggerEvents') === false || empty(self::$lsRetailApiTriggerEvents)) {
            return;
        }

        $mapping = collect(self::lightspeedRetailResourceMapping());

        foreach (self::$lsRetailApiTriggerEvents as $event) {

            if (method_exists(self::class, $event) === false) {
                continue;
            }

            static::$event(function (Model $model) use ($event, $mapping) {
                if ($event === 'deleted') {
                    // TODO remove resource from LS retail
                    return;
                }

                if ($model->isDirty($mapping->flatten(2)->toArray()) === false) {
                    return;
                }

                $payloads = (new GenerateRetailPayloadAction())->execute($model);

                if (empty($payloads)) {
                    return;
                }

                $payloads = collect($payloads)->filter(fn($payload) => count($payload['payload']) > 0);

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
