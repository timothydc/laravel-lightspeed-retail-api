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
        LightspeedRetailApi::api()->put('Category', 7, ['name' => 'Electronics v3']);
        return 0;
    }
}
