<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Console\Commands;

use Illuminate\Console\Command;
use TimothyDC\LightspeedRetailApi\Facades\LightspeedRetailApi;

class VerifyApiConnectionCommand extends Command
{
    protected $signature = 'retail:api';
    protected $description = 'Verify the Lightspeed API retail connection';

    public function handle(): int
    {
        $this->info(json_encode(LightspeedRetailApi::api()->category()->first(8)));

        return 0;
    }
}
