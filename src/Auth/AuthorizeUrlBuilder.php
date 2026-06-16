<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Auth;

use Emeq\ExactApi\Data\AuthorizeUrlParameters;

/**
 * Bouwt Exact's consent-URL (`/api/oauth2/auth`). Geen Saloon-request — dit is
 * een GET die de browser van de eindgebruiker uitvoert; de Hub orchestreert de
 * redirect + state-opslag.
 */
final class AuthorizeUrlBuilder
{
    public function __construct(
        private readonly string $authBaseUrl,
    ) {
    }

    public function build(AuthorizeUrlParameters $parameters): string
    {
        return mb_rtrim($this->authBaseUrl, '/') . '/api/oauth2/auth?' . http_build_query($parameters->toQuery());
    }
}
