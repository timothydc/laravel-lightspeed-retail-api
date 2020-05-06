<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Repositories;

use TimothyDC\LightspeedRetailApi\Interfaces\TokenInterface;
use TimothyDC\LightspeedRetailApi\Models\ApiToken;

class TokenRepository implements TokenInterface
{
    private ApiToken $table;
    protected string $tokenIdentifier = 'ls_retail_api';

    protected string $keyIdentifier = 'type';
    protected string $keyAccessToken = 'access_token';
    protected string $keyRefreshToken = 'refresh_token';
    protected string $keyExpiresAt = 'expires_at';
    protected string $keyScope = 'scope';
    protected string $keyExternalId = 'account_id';

    public function __construct(ApiToken $table)
    {
        $this->table = $table;
    }

    public function saveToken(array $data): ApiToken
    {
        if (array_key_exists('access_token', $data)) {
            $this->getToken()->setAttribute($this->keyAccessToken, $data['access_token']);
        }

        if (array_key_exists('refresh_token', $data)) {
            $this->getToken()->setAttribute($this->keyRefreshToken, $data['refresh_token']);
        }

        if (array_key_exists('expires_in', $data)) {
            $this->getToken()->setAttribute($this->keyExpiresAt, now()->addSeconds($data['expires_in'] - 2)); // some leeway?
        }

        if (array_key_exists('scope', $data)) {
            $this->getToken()->setAttribute($this->keyScope, $data['scope']);
        }

        if (array_key_exists('account_id', $data)) {
            $this->getToken()->setAttribute($this->keyExternalId, $data['account_id']);
        }

        $this->getToken()->save();

        return $this->getToken();
    }

    public function saveAccessToken(string $accessToken): ApiToken
    {
        return $this->saveToken(['access_token' => $accessToken]);
    }

    public function saveRefreshToken(string $refreshToken): ApiToken
    {
        $this->getToken()->setAttribute($this->keyRefreshToken, $refreshToken);
        $this->getToken()->save();

        return $this->getToken();
    }

    public function getToken(): ApiToken
    {
        if ($this->table->getAttribute($this->keyIdentifier) === null) {
            $token = (new $this->table())->where($this->keyIdentifier, $this->tokenIdentifier)->first();

            if (!$token) {
                $token = $this->createToken();
            }

            $this->table = $token;
        }

        return $this->table;
    }

    public function createToken(): ApiToken
    {
        $token = (new $this->table());
        $token->setAttribute($this->keyIdentifier, $this->tokenIdentifier);
        $token->save();

        return $token;
    }

    public function exists(string $tokenType): bool
    {
        return $this->getToken() !== null;
    }
}
