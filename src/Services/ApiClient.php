<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TimothyDC\LightspeedRetailApi\Actions\ParseJwtAction;
use TimothyDC\LightspeedRetailApi\Exceptions\AuthenticationException;
use TimothyDC\LightspeedRetailApi\Exceptions\DuplicateResourceException;
use TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException;
use TimothyDC\LightspeedRetailApi\Repositories\TokenRepository;
use TimothyDC\LightspeedRetailApi\Traits\QueryBuilder;
use TimothyDC\LightspeedRetailApi\Traits\RetailResources;

class ApiClient
{
    use RetailResources, QueryBuilder;

    public const API_RESULT_LIMIT = 100;
    public const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';
    public const GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';

    private string $baseUrl = 'https://api.lightspeedapp.com/API/V3/Account/';

    private string $client_id;
    private string $client_secret;
    private TokenRepository $tokenRepository;

    protected array $bucket = [];
    protected string $bucketCacheKey;
    protected string $cacheStore;

    public function __construct(TokenRepository $tokenRepository)
    {
        $this->client_id = config('lightspeed-retail.api.key');
        $this->client_secret = config('lightspeed-retail.api.secret');
        $this->cacheStore = config('lightspeed-retail.cache_store');
        $this->tokenRepository = $tokenRepository;

        $this->bucketCacheKey = $this->client_id . '_retail_api_bucket';

        // set initial bucket if none is found
        if (Cache::store($this->cacheStore)->has($this->bucketCacheKey) === false) {
            Cache::store($this->cacheStore)->set($this->bucketCacheKey, [
                'drip' => 1,
                'size' => 60,
                'level' => 0,
                'available' => 60,
                'timestamp' => now(),
            ]);
        }
    }

    public function isConfigured(): bool
    {
        return $this->tokenRepository->exists();
    }

    public function getAll(string $resource = null, int $id = null, array $query = []): Collection
    {
        $results = collect();
        while (($result = $this->getWithPagination($resource, $id, $query))->count() > 0) {
            $result = Collection::unwrap($result);

            // merge resource results
            $results = $results->merge($result[$resource]);

            // prepare next page
            if (isset($result['@attributes']['after']) && $result['@attributes']['after'] !== '') {
                $query['after'] = $result['@attributes']['after'];
            } else {
                // exit while loop when there is no "next" page
                break;
            }
        }

        return $results;
    }

    public function get(string $resource = null, int $id = null, array $query = [], bool $withPagination = false): Collection
    {
        $responseObject = Http::withHeaders(['Accept' => 'application/json'])
            ->withOptions(['handler' => $this->createHandlerStack()])
            ->get($this->getUrl($resource, $id) . $this->buildQueryString($query));

        $this->logAction('GET ' . $this->getUrl($resource, $id), ['params' => func_get_args(), 'status' => $responseObject->status()]);

        $response = $responseObject->json();

        // unstructured way of requesting the "Account" resource
        if (! $resource) {
            return collect($response['Account']);
        }

        // fix Lightspeed unstructured way of returning an array when a multidimensional array is expected
        if (isset($response['@attributes']['count']) && $response['@attributes']['count'] === 1) {
            $response[$resource] = [$response[$resource]];
        }
        
        // for pagination, add @attributes meta data to result
        if ($withPagination) {
            $response['@attributes'] = $this->extractAttributes($response);
            $response[$resource] = collect($response[$resource] ?? []);

            return collect($response);
        }

        return collect($response[$resource] ?? []);
    }

    public function getWithPagination(string $resource = null, int $id = null, array $query = []): Collection
    {
        return $this->get($resource, $id, $query, true);
    }

    /**
     * @throws DuplicateResourceException
     * @throws LightspeedRetailException
     */
    public function post(string $resource, array $payload): Collection
    {
        $responseObject = Http::withHeaders(['Accept' => 'application/json'])
            ->withOptions(['handler' => $this->createHandlerStack()])
            ->post($this->getUrl($resource), $payload);

        $this->logAction('POST ' . $this->getUrl($resource), ['params' => func_get_args(), 'status' => $responseObject->status()]);

        $response = $responseObject->json();
        if ($responseObject->clientError() || $responseObject->serverError()) {

            // catch already existing resource error
            if (Str::contains($response['message'], 'already exists')) {
                throw new DuplicateResourceException($response['message'], $responseObject->status());
            }

            Log::error($response['message'], ['method' => 'put', 'url' => $this->getUrl($resource), 'payload' => $payload]);

            throw new LightspeedRetailException($response['message'], $responseObject->status());
        }

        return collect($response[$resource]);
    }

    /**
     * @throws LightspeedRetailException
     */
    public function put(string $resource, int $id, array $payload): Collection
    {
        $responseObject = Http::withHeaders(['Accept' => 'application/json'])
            ->withOptions(['handler' => $this->createHandlerStack()])
            ->put($this->getUrl($resource, $id), $payload);

        $this->logAction('PUT ' . $this->getUrl($resource, $id), ['params' => func_get_args(), 'status' => $responseObject->status()]);

        $response = $responseObject->json();
        if ($responseObject->clientError() || $responseObject->serverError()) {
            Log::error($response['message'], ['method' => 'put', 'url' => $this->getUrl($resource, $id), 'payload' => $payload]);

            throw new LightspeedRetailException($response['message'], $responseObject->status());
        }

        return collect($response);
    }

    public function delete(string $resource, int $id): Collection
    {
        $responseObject = Http::withHeaders(['Accept' => 'application/json'])
            ->withOptions(['handler' => $this->createHandlerStack()])
            ->delete($this->getUrl($resource, $id));

        $this->logAction('DELETE ' . $this->getUrl($resource, $id), ['params' => func_get_args(), 'status' => $responseObject->status()]);

        $response = $responseObject->json();

        return collect($response);
    }

    private function logAction(string $method, array $data): void
    {
        if (config('lightspeed-retail.api.logging')) {
            Log::debug('Lightspeed Retail: ' . $method, $data);
        }
    }

    private function getUrl(string $resource = null, int $id = null): string
    {
        if (! $resource) {
            return $this->baseUrl;
        }

        return $this->baseUrl . $this->tokenRepository->getAccountId() . ($resource ? '/' . $resource : '') . ($id ? '/' . $id : '');
    }

    /**
     * @param string $code
     * @throws AuthenticationException
     */
    public function startUpClient(string $code): void
    {
        // trade temp code for access and refresh code
        $this->storeInitialAccessToken($code);

        // save account id
        $this->tokenRepository->saveToken(['account_id' => $this->account()->get()->get('accountID')]);
    }

    /**
     * @param string $code
     * @throws AuthenticationException
     */
    protected function storeInitialAccessToken(string $code): void
    {
        $responseObject = $this->requestAccessToken($code);

        $response = $responseObject->json();
        if ($responseObject->clientError() || $responseObject->serverError()) {
            throw new AuthenticationException(implode(': ', $response), $responseObject->status());
        }

        // convert access token to JWT data
        $tokenData = resolve(ParseJwtAction::class)->execute($response['access_token']);

        $this->tokenRepository->saveToken([
            'scope' => $tokenData['scope'] ?? null,
            'expires_in' => $response['expires_in'],
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
        ]);
    }

    protected function requestRefreshToken(string $code, string $scope): Response
    {
        return $this->requestToken($code, self::GRANT_TYPE_REFRESH_TOKEN, $scope);
    }

    protected function requestAccessToken(string $code): Response
    {
        return $this->requestToken($code, self::GRANT_TYPE_AUTHORIZATION_CODE);
    }

    protected function requestToken($code, string $grantType, ?string $scope = null): Response
    {
        $postFields = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $grantType,
        ];

        if ($scope) {
            $postFields += ['scope' => $this->filterInvalidScopes($scope)];
        }

        switch ($grantType) {
            case self::GRANT_TYPE_AUTHORIZATION_CODE:
                $postFields += ['code' => $code];

                break;
            case self::GRANT_TYPE_REFRESH_TOKEN:
                $postFields += ['refresh_token' => $code];

                break;
        }

        return Http::post('https://cloud.lightspeedapp.com/auth/oauth/token', $postFields);
    }

    protected function createHandlerStack(): HandlerStack
    {
        $stack = HandlerStack::create(new CurlHandler());

        // RetryMiddleware handles errors (including token refresh)
        $stack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));

        // Add Authorization header with current access token
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request->withHeader('Authorization', 'Bearer ' . $this->tokenRepository->getAccessToken());
        }));

        // Check bucket before sending
        $stack->push(Middleware::mapRequest($this->checkBucket()));

        // After response, get bucket state
        $stack->push(Middleware::mapResponse($this->getBucket()));

        return $stack;
    }

    /**
     * A middleware method to decide when to retry requests.
     *
     * This will run even for sucessful requests. We want to retry up to 5 times
     * on connection errors (which can sometimes come back as 502, 503 or 504
     * HTTP errors) and 429 Too Many Requests errors.
     * For 401 Unautorized responses, we refresh the access token and retry once.
     *
     * @return callable
     */
    protected function retryDecider(): callable
    {
        return function (
            $retries,
            RequestInterface $request,
            ResponseInterface $response = null,
            RequestException $exception = null
        ) {
            // Limit the number of retries to 5
            if ($retries >= 5) {
                return false;
            }

            $should_retry = false;
            $refresh = false;
            $log_message = null;

            // Retry connection exceptions
            if ($exception instanceof ConnectException) {
                $should_retry = true;
                $log_message = 'Connection Error: ' . $exception->getMessage();
            }

            if ($response) {
                $code = $response->getStatusCode();
                if ($code >= 400) {
                    Log::debug(self::class . ' HTTP Error ' . $code, json_decode($response->getBody()->getContents(), true));
                }

                // 401: Refresh access token and try again once
                if ($code === 401 && $retries <= 1) {
                    $refresh = true;
                    $should_retry = true;
                }

                // 429, 502, 503, 504: try again
                if (in_array($code, [429, 502, 503, 504], true)) {
                    $should_retry = true;
                }
            }

            if ($log_message) {
                Log::debug(self::class . ' ' . $log_message);
            }

            if ($refresh) {
                Log::debug(self::class . ' refreshing access token.');
                $this->refreshToken();
            }

            if ($should_retry && $retries > 0) {
                Log::debug(self::class . ' Retry ' . $retries . '…');
            }

            return $should_retry;
        };
    }

    /**
     * A middleware method to decide how long to wait before retrying.
     *
     * For 401 and 429 errors, we don't wait.
     * For connection errors we wait 1 second before the first retry, 2 seconds
     * before the second, and so on.
     *
     * @return callable
     */
    protected function retryDelay(): callable
    {
        return static function ($numberOfRetries, ResponseInterface $response = null) {
            // No delay for 401 or 429 responses
            if ($response) {
                $code = $response->getStatusCode();
                if (in_array($code, [401, 429])) {
                    return 0;
                }
            }

            // Increasing delay otherwise
            return 1000 * $numberOfRetries;
        };
    }

    /**
     * A middleware method to check the bucket state before each request.
     *
     * GET requests cost 1 point; PUT, POST and DELETE cost 10. If this request
     * will push us over the limit, we wait until there's enough room before
     * sending it.
     * Takes into account the time passed since the last request.
     *
     * @return callable
     */
    protected function checkBucket(): callable
    {
        return function (RequestInterface $request) {

            // if bucket is empty - get from redis
            if ($this->bucket === []) {
                $this->bucket = Cache::store($this->cacheStore)->get($this->bucketCacheKey);
            }

            $cost = strtolower($request->getMethod()) === 'get' ? 1 : 10;
            $remainingCost = $cost - $this->bucket['available'];

            if ($remainingCost > 0) {
                $sleep_time = $remainingCost / $this->bucket['drip'];

                // check if the time that we need to sleep is larger than the last time we tried sending a request,
                // if so then sleep cus our bucket needs more time to fill
                // if not, then our bucket should have enough available drops to process our request
                if (isset($this->bucket['last_req_time']) && $sleep_time > (time() - $this->bucket['last_req_time'])) {
                    $sleep_microseconds = ceil($sleep_time * 1000000);
                    Log::debug(self::class . ' Notice: Rate limit reached, sleeping ' . $sleep_microseconds / 1000000 . ' seconds.');
                    usleep((int)$sleep_microseconds);
                }
            }

            return $request;
        };
    }

    /**
     * A middleware method to read the bucket state from each reponse.
     *
     * The bucket level and size are parsed from the X-LS-API-Bucket-Level
     * header. The drip rate is calculated as the bucket size divided by 60.
     * The time is also saved so we know how much time has passed.
     *
     * @return callable
     */
    protected function getBucket(): callable
    {
        return function (ResponseInterface $response) {
            $bucket_header = $response->getHeader('X-LS-API-Bucket-Level');

            if (count($bucket_header) > 0) {
                $bucket = explode('/', $bucket_header[0]);

                $this->bucket = [
                    'drip' => $bucket[1] / 60,
                    'size' => $bucket[1],
                    'level' => $bucket[0],
                    'available' => $bucket[1] - $bucket[0],
                ];
            }

            // set timestamp since we last got a response
            $this->bucket['timestamp'] = now()->toIso8601String();
            $this->bucket['last_req_time'] = time();

            // cache bucket rate
            Cache::store($this->cacheStore)->set($this->bucketCacheKey, $this->bucket);

            return $response;
        };
    }

    protected function refreshToken(): void
    {
        $responseObject = $this->requestRefreshToken($this->tokenRepository->getRefreshToken(), $this->tokenRepository->getScope());
        $response = $responseObject->json();

        // convert access token to JWT data
        $tokenData = resolve(ParseJwtAction::class)->execute($response['access_token']);

        if ($response) {
            $this->tokenRepository->saveToken([
                'scope' => $tokenData['scope'] ?? null,
                'refresh_token' => $response['refresh_token'] ?? null,
                'access_token' => $response['access_token'] ?? null,
                'expires_in' => $response['expires_in'] ?? null,
            ]);
        } else {
            Log::emergency(self::class . ' Unable to refresh token.', [$response]);
        }
    }

    protected function extractAttributes(array $data): Collection
    {
        if (! array_key_exists('@attributes', $data)) {
            return collect();
        }

        $attributes = $data['@attributes'] ?? [];

        // extract "next" key from URL
        if (array_key_exists('next', $attributes) && $attributes['next'] !== '') {
            // parse URL and extract query parameters
            parse_str(parse_url($attributes['next'])['query'], $queryParameters);
            $after = $queryParameters['after'] ?? '';
        }

        // extract "previous" key from URL
        if (array_key_exists('previous', $attributes) && $attributes['previous'] !== '') {
            // parse URL and extract query parameters
            parse_str(parse_url($attributes['previous'])['query'], $queryParameters);
            $before = $queryParameters['before'] ?? '';
        }

        // add new keys to $attributes
        $attributes['after'] = $after ?? '';
        $attributes['before'] = $before ?? '';

        return collect($attributes);
    }

    /**
     * This method will filter out the deprecated "systemuserid:{number}" scope
     */
    protected function filterInvalidScopes(string $scope): string
    {
        if (! str_contains($scope, 'systemuserid')) {
            return $scope;
        }
        
        return collect(explode(' ', $scope))
            ->mapWithKeys(function ($value) {
                $keyValue = explode(':', $value);
                return [$keyValue[0] => $keyValue[1]];
            })
            ->filter(fn($value, $key) => $key !== 'systemuserid')
            ->implode(' ');
    }
}
