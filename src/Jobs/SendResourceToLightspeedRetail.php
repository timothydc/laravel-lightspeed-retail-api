<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use TimothyDC\LightspeedRetailApi\Actions\SaveLightspeedRetailResourceAction;
use TimothyDC\LightspeedRetailApi\Events\ResourceSendEvent;
use TimothyDC\LightspeedRetailApi\Exceptions\AuthenticationException;
use TimothyDC\LightspeedRetailApi\Exceptions\IncorrectModelConfigurationException;
use TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException;
use TimothyDC\LightspeedRetailApi\Exceptions\MissingLightspeedResourceException;
use TimothyDC\LightspeedRetailApi\Exceptions\WaitingForSynchronisationException;
use TimothyDC\LightspeedRetailApi\Facades\LightspeedRetailApi;
use TimothyDC\LightspeedRetailApi\Jobs\Middleware\RateLimited;
use TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceItem;
use TimothyDC\LightspeedRetailApi\Traits\HasLightspeedRetailResources;

class SendResourceToLightspeedRetail implements ShouldQueue
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
    private array $payload;

    public function __construct(Model $model, string $resource, array $payload)
    {
        $this->onQueue(config('lightspeed-retail.queue'));

        $this->model = $model;
        $this->resource = $resource;
        $this->payload = $payload;
    }

    /**
     * @throws MissingLightspeedResourceException
     * @throws IncorrectModelConfigurationException
     * @throws AuthenticationException
     * @throws LightspeedRetailException
     * @throws WaitingForSynchronisationException
     */
    public function handle(SaveLightspeedRetailResourceAction $saveLightspeedRetailResourceAction): void
    {
        if ($this->validateRequest() === false) {
            return;
        }

        $this->loadRelationship();

        // check if Lightspeed resource exists
        if ($this->model->lightspeedRetailResource()->exists() === false) {

            // filter out empty data when creating new resources
            if ($this->validatePayloadForCreateRequest() === false) {
                return;
            }

            $archiveItem = false;
            if ($this->allowedToArchiveItems()) {
                if (config('lightspeed-retail.behavior.allow_archive_on_create') === true) {
                    $archiveItem = true;
                } else {
                    return;
                }
            }

            // archive not allowed on create
            unset($this->payload[ResourceItem::$archived]);

            // create new API resource
            $lsResource = $this->getApiClientobject()->create($this->payload);

            // archive item of need be
            if ($archiveItem) {
                $this->getApiClientobject()->delete((int)$lsResource->get($this->getApiClientobject()->primaryKey));
            }

            // save API resource
            $saveLightspeedRetailResourceAction->execute([
                'resource_id' => $this->model->getKey(),
                'resource_type' => $this->model->getMorphClass(),
                'lightspeed_type' => $this->resource,
                'lightspeed_id' => $lsResource->get($this->getApiClientobject()->primaryKey),
            ]);
        } else {
            if ($this->allowedToArchiveItems()) {
                $this->getApiClientobject()->delete($this->model->lightspeedRetailResource->lightspeed_id);
                unset($this->payload[ResourceItem::$archived]);

                if (empty($this->payload)) {
                    return;
                }
            }

            // update API resource
            $this->getApiClientobject()->update($this->model->lightspeedRetailResource->lightspeed_id, $this->payload);

            // bump timestamp
            $this->model->lightspeedRetailResource->updated_at = now();
            $this->model->lightspeedRetailResource->save();
        }

        event(new ResourceSendEvent($this->model->lightspeedRetailResource));
    }

    private function getApiClientobject(): \TimothyDC\LightspeedRetailApi\Resource
    {
        return LightspeedRetailApi::api()->{strtolower($this->resource)}();
    }

    private function validatePayloadForCreateRequest(): bool
    {
        $this->payload = collect($this->payload)->filter(fn ($data) => $data !== null)->toArray();

        if (empty($this->payload)) {
            return false;
        }

        return true;
    }

    private function allowedToArchiveItems(): bool
    {
        return $this->resource === ResourceItem::$resource
            && array_key_exists(ResourceItem::$archived, $this->payload)
            && $this->payload[ResourceItem::$archived] === true;
    }

    /**
     * @throws WaitingForSynchronisationException
     */
    private function loadRelationship(): void
    {
        // replace relationships
        $relations = collect($this->payload)->filter(fn ($item) => is_string($item) && Str::contains($item, '.id'));

        foreach ($relations as $lightspeedForeignKey => $localeForeignKey) {
            [$relatedObject] = explode('.id', $localeForeignKey);

            // when we unlink a resource
            if (is_null($this->model->$relatedObject()->first()) === true) {
                $this->payload[$lightspeedForeignKey] = null;

                continue;
            }

            // when we are waiting for a related resource to synchronise
            if (is_null($this->model->$relatedObject()->first()->lightspeedRetailResource) === true) {
                throw new WaitingForSynchronisationException('Waiting for ' . $relatedObject . ':' . $this->model->$relatedObject()->first()->id . ' to synchronise.');
            }

            // when we are golden; add the related resource Lightspeed ID to the payload
            $this->payload[$lightspeedForeignKey] = $this->model->$relatedObject()->first()->lightspeedRetailResource->lightspeed_id;
        }
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

    public function middleware(): array
    {
        if (App::runningUnitTests()) {
            return [];
        }

        return [
            new RateLimited('ls-retail-api-throttle'),
        ];
    }
}
