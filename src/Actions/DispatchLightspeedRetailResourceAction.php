<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Actions;

use TimothyDC\LightspeedRetailApi\Jobs\SendResourceToLightspeedRetail;

class DispatchLightspeedRetailResourceAction
{
    public function execute(array $payloads): void
    {
        if (empty($payloads)) {
            return;
        }

        $filteredPayloads = collect($payloads)->filter(fn($payload) => count($payload['payload']) > 0);

        // send payload to processor
        if (config('lightspeed-retail.api.async') === true) {
            SendResourceToLightspeedRetail::dispatch(...array_values($filteredPayloads->shift()))->chain($filteredPayloads->map(fn($payload) => new SendResourceToLightspeedRetail(...array_values($payload)))->toArray());

        } else {
            $filteredPayloads->each(fn($payload) => SendResourceToLightspeedRetail::dispatchNow(...array_values($payload)));
        }
    }
}
