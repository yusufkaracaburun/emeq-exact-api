<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Auth;

use Emeq\ExactApi\Data\ExactCredentials;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

/**
 * POST {auth_base_url}/api/oauth2/token — initiële code-exchange.
 *
 * Body (application/x-www-form-urlencoded), verbatim uit Exact's Postman-collection:
 *   grant_type=authorization_code, client_id, client_secret, redirect_uri, code
 *
 * Respons: { access_token, token_type: "bearer", expires_in: "600", refresh_token }
 */
final class AuthorizationCodeRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly ExactCredentials $credentials,
        private readonly string $code,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/api/oauth2/token';
    }

    /**
     * @return array{grant_type: string, client_id: string, client_secret: string, redirect_uri: string, code: string}
     */
    protected function defaultBody(): array
    {
        return [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->credentials->clientId,
            'client_secret' => $this->credentials->clientSecret,
            'redirect_uri'  => $this->credentials->redirectUri,
            'code'          => $this->code,
        ];
    }
}
