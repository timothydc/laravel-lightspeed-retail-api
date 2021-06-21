<?php

namespace TimothyDC\LightspeedRetailApi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TimothyDC\LightspeedRetailApi\Models\LightspeedRetailResource;

class ResourceSendEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lightspeedRetailResource;
    public $payload;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(LightspeedRetailResource $lightspeedRetailResource, array $payload = [])
    {
        $this->lightspeedRetailResource = $lightspeedRetailResource;
        $this->payload = $payload;
    }
}
