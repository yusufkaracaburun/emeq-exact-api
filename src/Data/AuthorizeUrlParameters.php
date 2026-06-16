<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Data;

/**
 * Query-parameters voor Exact's consent-URL (`/api/oauth2/auth`).
 *
 * Shape per Exact's Postman-collection: client_id, redirect_uri, response_type=code.
 * `state` (CSRF) en `scope` zijn optioneel en worden weggelaten als niet gezet.
 */
final readonly class AuthorizeUrlParameters
{
    public function __construct(
        public string $clientId,
        public string $redirectUri,
        public ?string $state = null,
        public ?string $scope = null,
        public string $responseType = 'code',
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => $this->responseType,
            'state'         => $this->state,
            'scope'         => $this->scope,
        ], static fn (?string $value): bool => null !== $value && '' !== $value);
    }
}
