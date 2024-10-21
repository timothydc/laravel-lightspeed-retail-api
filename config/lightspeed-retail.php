<?php
declare(strict_types=1);

return [
    'api' => [
        'key' => env('LIGHTSPEED_RETAIL_API_KEY'),
        'secret' => env('LIGHTSPEED_RETAIL_API_SECRET'),
        'async' => env('LIGHTSPEED_RETAIL_USE_ASYNC_QUEUE', true),
        'logging' => env('LIGHTSPEED_RETAIL_LOG_CALLS', false),
    ],
    'behavior' => [
        /*
         * When you create a new Lightspeed Retail Item resource that has the initial status of "archived = true",
         * you can choose to not create this Item. This might give you some time to set up the resource before synchronising it to your POS.
         * TRUE = will create the Item in Retail and immediately archive it
         * FALSE = will not create a new Item as long as "archive" is true
         */
        'allow_archive_on_create' => true,
    ],
    'exceptions' => [
        /*
         * When this is set to TRUE, an exception will be thrown when calling the API when you have not authenticated your client.
         */
        'throw_on_unauthorized' => false,
    ],
    /*
     * Set the queue on which the jobs are send
     */
    'queue' => env('LIGHTSPEED_RETAIL_QUEUE', 'default'),
    /*
     * Set the type of cache store to use when processing the API leaking bucket.
     * You can choose a Cache Store value from your cache.php file
     */
    'cache_store' => env('LIGHTSPEED_RETAIL_CACHE_STORE', 'redis'),
];
