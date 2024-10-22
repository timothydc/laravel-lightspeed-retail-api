<?php

declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Actions;

use Illuminate\Support\Collection;

class ParseJwtAction
{
    /**
     * This method decodes a JWT string into:
     *
     * typ    Token Type: The type of token. This will always be JWT.
     * alg    Algorithm: The algorithm used to sign the token. At the moment, this will always be RS256, however, this could change in future.
     * kid    Key ID: The ID of the public key used to verify the token. Can be used to retrieve the public key from the JWKS endpoint in order to verify the signature of the token
     * jti    JSON Web Token Identifier: The unique identifier of the token. For Lightspeed internal use only.
     * iss    Issuer: The URL of the issuer of the token. Should always be https://cloud.lightspeedapp.com
     * aud    Audience: This is the public client ID of the application that requested the token.
     * sub    Subscriber ID: The ID of the Retail POS (R-Series) user that authorized the token
     * acct    Account ID: The ID of the Retail POS (R-Series) account that the token is authorized to access
     * scopes    A space-separated list of permissions that the token has been granted
     * iat    Issued At: The unix timestamp of when the token was issued.
     * nbf    Not Before: The unix timestamp indicating when the token is valid from.
     * exp    Expires: The unix timestamp after which the token will no longer be valid. After a token is expired, a new one should be requested with a Refresh Token Grant
     */
    public function execute(string $token): Collection
    {
        // decode token, json decode parts and return a single array
        $parsed = collect();
        foreach (explode('.', $token) as $part) {
            $decodedString = base64_decode($part);
            $parsed = $parsed->merge(json_decode($decodedString, true));
        }

        return $parsed;
    }
}
