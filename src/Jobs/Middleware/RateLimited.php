<?php

declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Jobs\Middleware;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redis;

class RateLimited
{
    private string $throttleKey;
    private int $every;
    private int $allow;
    private int $release;

    public function __construct(string $throttleKey, int $allow = 3, int $every = 2, int $release = 10)
    {
        $this->throttleKey = $throttleKey;
        $this->allow = $allow;
        $this->every = $every;
        $this->release = $release;
    }

    public function handle($job, $next): void
    {
        if (App::runningUnitTests()) {
            $next($job);

            return;
        }

        $release = $this->release;

        Redis::throttle($this->throttleKey)
            ->allow($this->allow)
            ->every($this->every)
            ->then(
                function () use ($next, $job) {
                    $next($job);
                },
                function () use ($release, $job) {
                    $job->release($release);
                }
            );
    }
}
