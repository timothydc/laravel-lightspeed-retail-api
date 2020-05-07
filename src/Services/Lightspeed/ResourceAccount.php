<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use TimothyDC\LightspeedRetailApi\Services\ApiClient;

class ResourceAccount
{
    private ApiClient $client;
    public string $primaryKey = 'accountID';

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    public function get()
    {
        return $this->client->get();
    }
}
