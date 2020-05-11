<?php
declare(strict_types=1);

return [
    'api' => [
        'key' => env('LIGHTSPEED_RETAIL_API_KEY'),
        'secret' => env('LIGHTSPEED_RETAIL_API_SECRET'),
    ],
    'exceptions' => [
        /*
         * When this is set to TRUE, an exception will be thrown when calling the API when you have not authenticated your client.
         */
        'throw_on_unauthorized' => false,
    ]
];
