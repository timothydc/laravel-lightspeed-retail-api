<?php

declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TimothyDC\LightspeedRetailApi\LightspeedRetailApiServiceProvider;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LightspeedRetailApiServiceProvider::class,
        ];
    }
}
