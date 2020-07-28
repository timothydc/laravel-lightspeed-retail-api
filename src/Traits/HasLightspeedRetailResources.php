<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\App;
use TimothyDC\LightspeedRetailApi\Actions\DispatchLightspeedRetailResourceAction;
use TimothyDC\LightspeedRetailApi\Actions\GenerateRetailPayloadAction;
use TimothyDC\LightspeedRetailApi\Actions\RemoveResourceFromLightspeedRetailAction;
use TimothyDC\LightspeedRetailApi\Models\LightspeedRetailResource;

trait HasLightspeedRetailResources
{
    // public array $lsForceSyncFields = ['ean'];

    public static GenerateRetailPayloadAction $lsRetailPayloadAction;
    public static DispatchLightspeedRetailResourceAction $lsRetailDispatchAction;
    public static RemoveResourceFromLightspeedRetailAction $lsRetailRemoveAction;

    public static function bootHasLightspeedRetailResources(): void
    {
        static::$lsRetailPayloadAction = App::make(GenerateRetailPayloadAction::class);
        static::$lsRetailDispatchAction = App::make(DispatchLightspeedRetailResourceAction::class);
        static::$lsRetailRemoveAction = App::make(RemoveResourceFromLightspeedRetailAction::class);

        static::listenToResourceEvents();
    }

    public static function listenToResourceEvents(): void
    {
        if (method_exists(self::class, 'getLightspeedRetailApiTriggerEvents') === false || empty(self::getLightspeedRetailApiTriggerEvents())) {
            return;
        }

        $mapping = collect(self::getLightspeedRetailResourceMapping());

        foreach (self::getLightspeedRetailApiTriggerEvents() as $event) {
            if (method_exists(self::class, $event) === false) {
                continue;
            }

            if (in_array($event, static::getLightspeedRetailApiTriggerEvents(), true) === false) {
                continue;
            }

            $payloadAcion = static::$lsRetailPayloadAction;
            $dispatchAction = static::$lsRetailDispatchAction;
            $removeAction = static::$lsRetailRemoveAction;

            static::$event(static function (Model $model) use ($event, $mapping, $payloadAcion, $dispatchAction, $removeAction) {
                // handle delete events
                if ($event === 'deleted') {
                    if ($model->lightspeedRetailResource()->exists() && $model->lightspeedRetailResource->lightspeed_id) {
                        $removeAction->execute($model, $model->getLightspeedRetailResourceName(), $model->lightspeedRetailResource->lightspeed_id);
                    }

                    return;
                }

                // handle other events - check that the model was updated
                if ($model->isDirty($mapping->flatten(2)->toArray()) === false) {
                    return;
                }

                // generate payload and start processing
                $dispatchAction->execute($payloadAcion->execute($model));
            });
        }
    }

    public function lightspeedRetailResource(): MorphOne
    {
        return $this->morphOne(LightspeedRetailResource::class, 'resource');
    }
}
