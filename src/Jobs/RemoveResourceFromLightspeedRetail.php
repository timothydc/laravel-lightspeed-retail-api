<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TimothyDC\LightspeedRetailApi\Exceptions\AuthenticationException;
use TimothyDC\LightspeedRetailApi\Exceptions\IncorrectModelConfigurationException;
use TimothyDC\LightspeedRetailApi\Exceptions\MissingLightspeedResourceException;
use TimothyDC\LightspeedRetailApi\Facades\LightspeedRetailApi;
use TimothyDC\LightspeedRetailApi\Traits\HasLightspeedRetailResources;

class RemoveResourceFromLightspeedRetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
     * Times the job will be retried
     */
    public $tries = 5;

    /*
     * The number of seconds to wait before retrying the job
     */
    public $retryAfter = 10;

    private Model $model;
    private string $resource;
    private int $lightspeedId;

    public function __construct(Model $model, string $resource, int $lightspeedId)
    {
        $this->model = $model;
        $this->resource = $resource;
        $this->lightspeedId = $lightspeedId;
    }

    /**
     * @throws MissingLightspeedResourceException
     * @throws IncorrectModelConfigurationException
     * @throws AuthenticationException
     */
    public function handle(): void
    {
        if ($this->validateRequest() === false) {
            return;
        }

        $this->getApiClientobject()->delete($this->lightspeedId);
    }

    private function getApiClientobject(): \TimothyDC\LightspeedRetailApi\Resource
    {
        return LightspeedRetailApi::api()->{strtolower($this->resource)}();
    }

    /**
     * @return bool
     * @throws AuthenticationException
     * @throws IncorrectModelConfigurationException
     * @throws MissingLightspeedResourceException
     */
    private function validateRequest(): bool
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
            return false;
        }

        // check if API resource method exists
        if (method_exists(LightspeedRetailApi::api(), strtolower($this->resource)) === false) {
            throw new MissingLightspeedResourceException('Lightspeed resource [' . $this->resource . '] not defined');
        }

        return true;
    }
}
