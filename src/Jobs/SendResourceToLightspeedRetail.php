<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TimothyDC\LightspeedRetailApi\Actions\SaveLightspeedRetailResourceAction;
use TimothyDC\LightspeedRetailApi\Exceptions\AuthenticationException;
use TimothyDC\LightspeedRetailApi\Exceptions\DuplicateResourceException;
use TimothyDC\LightspeedRetailApi\Exceptions\IncorrectModelConfigurationException;
use TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException;
use TimothyDC\LightspeedRetailApi\Exceptions\MissingLightspeedResourceException;
use TimothyDC\LightspeedRetailApi\Facades\LightspeedRetailApi;
use TimothyDC\LightspeedRetailApi\Resource;
use TimothyDC\LightspeedRetailApi\Traits\HasLightspeedRetailResources;

class SendResourceToLightspeedRetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Model $model;
    private string $resource;
    private array $payload;

    public function __construct(Model $model, string $resource, array $payload)
    {
        $this->model = $model;
        $this->resource = $resource;
        $this->payload = $payload;
    }

    /**
     * @throws MissingLightspeedResourceException
     * @throws IncorrectModelConfigurationException
     * @throws AuthenticationException
     * @throws LightspeedRetailException
     */
    public function handle(SaveLightspeedRetailResourceAction $saveLightspeedRetailResourceAction): void
    {
        // check if morph method exists
        if (method_exists($this->model, 'lightspeedRetailResource') === false) {
            throw new IncorrectModelConfigurationException('Trait [' . HasLightspeedRetailResources::class . '] not found on model: ' . $this->model->getMorphClass());
        }

        // check if the client is configured
        if (LightspeedRetailApi::isApiClientConfigured() === false) {
            if (config('lightspeed-retail.exceptions.throw_on_unauthorized') === true) {
                throw new AuthenticationException('Client not authenticated. No API token found.');
            }

            // silently fail
            return;
        }

        // check if API resource method exists
        if (method_exists(LightspeedRetailApi::api(), strtolower($this->resource)) === false) {
            throw new MissingLightspeedResourceException('Lightspeed resource [' . $this->resource . '] not defined');
        }

        /** @var Resource $apiClientObject */
        $apiClientObject = LightspeedRetailApi::api()->{strtolower($this->resource)}();

        // check if Lightspeed resource exists
        if ($this->model->lightspeedRetailResource()->exists() === false) {

            try {
                // create new API resource
                $lsResource = $apiClientObject->create($this->payload);

            } catch (DuplicateResourceException $e) {
                $lsResource = collect($apiClientObject->get(null, collect($this->payload)
                    ->map(fn($param) => ['value' => $param])
                    ->toArray())->first());
            }

            // save API resource
            $saveLightspeedRetailResourceAction->execute([
                'resource_id' => $this->model->getKey(),
                'resource_type' => $this->model->getMorphClass(),
                'lightspeed_type' => $this->resource,
                'lightspeed_id' => $lsResource->get($apiClientObject->primaryKey),
            ]);

        } else {
            // update API resource
            $apiClientObject->update($this->model->lightspeedRetailResource->lightspeed_id, $this->payload);
        }
    }
}
