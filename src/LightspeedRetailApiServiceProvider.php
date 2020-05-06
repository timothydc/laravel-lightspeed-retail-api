<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi;

use Illuminate\Support\ServiceProvider;
use TimothyDC\LightspeedRetailApi\Console\Commands\VerifyApiConnectionCommand;
use TimothyDC\LightspeedRetailApi\Repositories\TokenRepository;
use TimothyDC\LightspeedRetailApi\Services\ApiClient;

class LightspeedRetailApiServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
//        $this->loadMigrationsFrom(__DIR__.'/path/to/migrations');

        $this->publishes([
            __DIR__ . '/../config/lightspeed-retail.php' => config_path('lightspeed-retail.php'),
        ], ['lightspeed-api', 'lightspeed-api:config']);

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], ['lightspeed-api', 'lightspeed-api:migrations']);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/lightspeed-retail.php', 'lightspeed-retail');

        $this->app->bind('command.retail:api', VerifyApiConnectionCommand::class);

        $this->commands([
            'command.retail:api',
        ]);

        $this->app->singleton(LightspeedRetailApi::class, function ($app) {
            return new LightspeedRetailApi($app->make(ApiClient::class));
        });

        $this->app->alias(LightspeedRetailApi::class, 'lightspeed-retail-api');

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['lightspeed-retail-api'];
    }
}
