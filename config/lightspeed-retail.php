<?php
declare(strict_types=1);

return [
    'api' => [
        'key' => env('LIGHTSPEED_RETAIL_API_KEY'),
        'secret' => env('LIGHTSPEED_RETAIL_API_SECRET'),
        'async' => env('LIGHTSPEED_RETAIL_USE_ASYNC_QUEUE', true),
        'logging' => env('LIGHTSPEED_RETAIL_LOG_CALLS', false),
    ],
    'exceptions' => [
        /*
         * When this is set to TRUE, an exception will be thrown when calling the API when you have not authenticated your client.
         */
        'throw_on_unauthorized' => false,
    ]
];
