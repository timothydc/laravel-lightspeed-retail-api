<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\App;
use TimothyDC\LightspeedRetailApi\Actions\DispatchLightspeedRetailResourceAction;
use TimothyDC\LightspeedRetailApi\Actions\GenerateRetailPayloadAction;
use TimothyDC\LightspeedRetailApi\Models\LightspeedRetailResource;

trait HasLightspeedRetailResources
{
    // public static function lightspeedRetailResourceMapping() { return ['API resource column (case sensitive)' => 'Your model column']; }
    // public static array $lsRetailApiTriggerEvents = ['created', 'updated', 'deleted'];
    // public array $lsForceSyncFields = ['ean'];

    public static GenerateRetailPayloadAction $lsRetailPayloadAction;
    public static DispatchLightspeedRetailResourceAction $lsRetailDispatchAction;

    public static function bootHasLightspeedRetailResources(): void
    {
        static::$lsRetailPayloadAction = App::make(GenerateRetailPayloadAction::class);
        static::$lsRetailDispatchAction = App::make(DispatchLightspeedRetailResourceAction::class);

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

            if (in_array($event, static::$lsRetailApiTriggerEvents, true) === false) {
                continue;
            }

            $payloadAcion = static::$lsRetailPayloadAction;
            $dispatchAction = static::$lsRetailDispatchAction;

            static::$event(static function (Model $model) use ($event, $mapping, $payloadAcion, $dispatchAction) {
                if ($event === 'deleted') {
                    // TODO remove resource from LS retail
                    return;
                }

                if ($model->isDirty($mapping->flatten(2)->toArray()) === false) {
                    return;
                }

                $dispatchAction->execute($payloadAcion->execute($model));
            });
        }
    }

    public function lightspeedRetailResource(): MorphOne
    {
        return $this->morphOne(LightspeedRetailResource::class, 'resource');
    }
}
