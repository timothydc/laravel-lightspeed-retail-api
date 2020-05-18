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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TimothyDC\LightspeedRetailApi\Exceptions\AuthenticationException;
use TimothyDC\LightspeedRetailApi\Exceptions\DuplicateResourceException;
use TimothyDC\LightspeedRetailApi\Exceptions\LightspeedRetailException;
use TimothyDC\LightspeedRetailApi\Repositories\TokenRepository;
use TimothyDC\LightspeedRetailApi\Traits\QueryBuilder;
use TimothyDC\LightspeedRetailApi\Traits\RetailResources;

class ApiClient
{
    use RetailResources, QueryBuilder;

    const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';
    const GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';

    private string $baseUrl = 'https://api.merchantos.com/API/Account/';

    public int $last_req_time;
    private string $client_id;
    private string $client_secret;
    private TokenRepository $tokenRepository;

    private array $bucket = [
        'drip' => 1,
        'size' => 60,
        'level' => 0,
        'available' => 60,
    ];

    public function __construct(TokenRepository $tokenRepository)
    {
        $this->client_id = config('lightspeed-retail.api.key');
        $this->client_secret = config('lightspeed-retail.api.secret');
        $this->tokenRepository = $tokenRepository;
    }

    public function isConfigured(): bool
    {
        return $this->tokenRepository->exists();
    }

    public function get(string $resource = null, int $id = null, array $query = []): Collection
    {
        $responseObject = Http::withHeaders(['Accept' => 'application/json'])
            ->withOptions(['handler' => $this->createHandlerStack()])
            ->get($this->getUrl($resource, $id) . $this->buildQueryString($query));

        $this->logAction('GET ' . $this->getUrl($resource, $id), ['params' => func_get_args(), 'status' => $responseObject->status()]);

        $response = $responseObject->json();

        // unstructured way of requesting the "Account" resource
        if (!$resource) {
            return collect($response['Account']);
        }

        // fix Lightspeed unstructured way of returning an array when a multi dimensional array is expected
        if ($response['@attributes']['count'] ?? false && $response['@attributes']['count'] === 1) {
            $response[$resource] = [$response[$resource]];
        }

        return collect($response[$resource] ?? []);
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

    private function logAction(string $method, array $data)
    {
        if (config('lightspeed-retail.api.logging')) {
            Log::debug('Lightspeed Retail: ' . $method, $data);
        }
    }

    private function getUrl(string $resource = null, int $id = null): string
    {
        if (!$resource) {
            return $this->baseUrl;
        }

        return $this->baseUrl . $this->tokenRepository->getAccountId() . ($resource ? '/' . $resource : '') . ($id ? '/' . $id : '');
    }

    /**
     * @param string $code
     * @throws AuthenticationException
     */
    public function startUpClient(string $code)
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

        $this->tokenRepository->saveToken([
            'scope' => $response['scope'],
            'expires_in' => $response['expires_in'],
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
        ]);
    }

    protected function requestRefreshToken(string $code): Response
    {
        return $this->requestToken($code, self::GRANT_TYPE_REFRESH_TOKEN);
    }

    protected function requestAccessToken(string $code): Response
    {
        return $this->requestToken($code, self::GRANT_TYPE_AUTHORIZATION_CODE);
    }

    protected function requestToken($code, string $grantType): Response
    {
        $postFields = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $grantType,
        ];

        switch ($grantType) {
            case self::GRANT_TYPE_AUTHORIZATION_CODE:
                $postFields += ['code' => $code];
                break;
            case self::GRANT_TYPE_REFRESH_TOKEN:
                $postFields += ['refresh_token' => $code];
                break;
        }

        return Http::post('https://cloud.lightspeedapp.com/oauth/access_token.php', $postFields);
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
    protected function retryDecider()
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
                if ($code == 401 && $retries <= 1) {
                    $refresh = true;
                    $should_retry = true;
                }

                // 429, 502, 503, 504: try again
                if (in_array($code, [429, 502, 503, 504])) {
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

            if ($should_retry) {
                if ($retries > 0) {
                    Log::debug(self::class . ' Retry ' . $retries . '…');
                }
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
    protected function retryDelay()
    {
        return function ($numberOfRetries, ResponseInterface $response = null) {
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
    protected function checkBucket()
    {
        return function (RequestInterface $request) {
            $cost = strtolower($request->getMethod()) == 'get' ? 1 : 10;
            $overflow = $cost - $this->bucket['available'];

            if ($overflow > 0) {
                $sleep_time = $overflow / $this->bucket['drip'];
                $time_since_last = time() - $this->last_req_time;

                if ($sleep_time > $time_since_last) {
                    $sleep_microseconds = ceil($sleep_time * 1000000);
                    Log::debug(self::class . ' Notice: Rate limit reached, sleeping ' . $sleep_microseconds / 1000000 . ' seconds.');
                    usleep($sleep_microseconds);
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
    protected function getBucket()
    {
        return function (ResponseInterface $response) {
            $bucket_header = $response->getHeader('X-LS-API-Bucket-Level');

            if (count($bucket_header) > 0) {
                $bucket = explode('/', $bucket_header[0]);
                $this->bucket = [
                    'level' => $bucket[0],
                    'size' => $bucket[1],
                    'available' => $bucket[1] - $bucket[0],
                    'drip' => $bucket[1] / 60
                ];
            }

            $this->last_req_time = time();

            return $response;
        };
    }

    protected function refreshToken()
    {
        $responseObject = $this->requestRefreshToken($this->tokenRepository->getRefreshToken());
        $response = $responseObject->json();

        if ($response) {
            $this->tokenRepository->saveToken(['access_token' => $response['access_token'], 'expires_in' => 3600]);
        } else {
            Log::emergency(self::class . ' Unable to refresh token.', [$response]);
        }
    }
}
