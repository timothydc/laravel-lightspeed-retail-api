<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi;

use TimothyDC\LightspeedRetailApi\Services\ApiClient;

class LightspeedRetailApi
{
    private ApiClient $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function api(): ApiClient
    {
        return $this->apiClient;
    }

    public static function redirectToAuthorizationPortal(string $scope, string $state = null): \Illuminate\Http\RedirectResponse
    {
        $query = http_build_query([
            'response_type' => 'code',
            'scope' => $scope,
            'client_id' => config('lightspeed-retail.api.key'),
            'state' => $state
        ]);

        return redirect('https://cloud.merchantos.com/oauth/authorize.php' . '?' . $query);
    }
}
