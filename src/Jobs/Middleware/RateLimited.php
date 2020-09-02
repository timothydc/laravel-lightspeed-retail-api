<?php

declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Jobs\Middleware;

use Illuminate\Support\Facades\Redis;

class RateLimited
{
    private string $throttleKey;
    private int $every;
    private int $allow;
    private int $release;

    public function __construct(string $throttleKey, int $allow = 285, int $every = 300, int $release = 60)
    {
        $this->throttleKey = $throttleKey;
        $this->allow = $allow;
        $this->every = $every;
        $this->release = $release;
    }

    public function handle($job, $next): void
    {
        Redis::throttle($this->throttleKey)
            ->allow($this->allow)
            ->every($this->every)
            ->then(
                fn () => $next($job),
                fn () => $job->release($this->release),
            );
    }
}
