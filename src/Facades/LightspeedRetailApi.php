<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Facades;

use Illuminate\Support\Facades\Facade;
use TimothyDC\LightspeedRetailApi\Services\ApiClient;

/**
 * Class LightspeedRetailApi
 *
 * @package TimothyDC\LightspeedRetailApi\Facades
 *
 * @method static ApiClient api
 * @method static ApiClient redirectToAuthorizationPortal(string $scopoe)
 */
class LightspeedRetailApi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'lightspeed-retail-api';
    }
}
