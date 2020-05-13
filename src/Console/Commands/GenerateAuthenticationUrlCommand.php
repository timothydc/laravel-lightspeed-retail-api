<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;
use TimothyDC\LightspeedRetailApi\Facades\LightspeedRetailApi;
use TimothyDC\LightspeedRetailApi\Scope;

class GenerateAuthenticationUrlCommand extends Command
{
    protected $signature = 'retail:auth';
    protected $description = 'Generate an URL to start the authentication process.';

    public function handle(): int
    {
        $scope = $this->choice('Define the OAuth scope', array_values((new ReflectionClass(Scope::class))->getConstants()));
        $this->line(LightspeedRetailApi::redirectToAuthorizationPortal($scope));

        return 0;
    }
}
