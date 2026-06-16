<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Auth;

use Emeq\ExactApi\Data\ExactCredentials;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

/**
 * POST {auth_base_url}/api/oauth2/token — refresh.
 *
 * Body (urlencoded), verbatim uit Exact's Postman-collection:
 *   grant_type=refresh_token, refresh_token, client_id, client_secret
 *
 * Exact roteert: elke geslaagde refresh geeft een NIEUW refresh_token en
 * invalideert het oude. Exact weigert de refresh zolang de access_token nog
 * geldig is (HTTP 400 "Rate limit exceeded: access_token not expired").
 */
final class RefreshTokenRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly ExactCredentials $credentials,
        private readonly string $refreshToken,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/api/oauth2/token';
    }

    /**
     * @return array{grant_type: string, refresh_token: string, client_id: string, client_secret: string}
     */
    protected function defaultBody(): array
    {
        return [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'client_id'     => $this->credentials->clientId,
            'client_secret' => $this->credentials->clientSecret,
        ];
    }
}
