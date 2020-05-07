<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TimothyDC\LightspeedRetailApi\Facades\LightspeedRetailApi;

class SendResourceToLightspeedRetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $resource;
    private array $payload;
    /**
     * @var Model
     */
    private Model $model;

    public function __construct(Model $model, string $resource, array $payload)
    {
        $this->resource = $resource;
        $this->payload = $payload;
        $this->model = $model;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // TODO get resource ID from LightspeedRetailResource::class
        $retailId = $this->model->getKey();
        LightspeedRetailApi::api()->{$this->resource}->update($retailId, $this->payload);
    }
}
