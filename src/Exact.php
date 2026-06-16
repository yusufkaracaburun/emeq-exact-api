<?php

declare(strict_types=1);

namespace Emeq\ExactApi;

use Closure;
use Emeq\ExactApi\Auth\AuthConnector;
use Emeq\ExactApi\Auth\AuthorizationCodeRequest;
use Emeq\ExactApi\Auth\AuthorizeUrlBuilder;
use Emeq\ExactApi\Auth\OAuthAuthenticator;
use Emeq\ExactApi\Contracts\ExactCredentialResolver;
use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Data\AccessToken;
use Emeq\ExactApi\Data\AuthorizeUrlParameters;
use Emeq\ExactApi\Data\ExactCredentials;
use Emeq\ExactApi\Exceptions\AuthenticationException;
use Emeq\ExactApi\Http\ExactConnector;

/**
 * Hoofd-client. Bouwt per-connection Saloon-connectors op aanvraag en levert de
 * consent-/code-exchange-helpers die de Hub's OAuth-flow gebruikt.
 *
 * De TokenStore en AuthConnector zijn singletons; de token-opslag is per
 * connection (gesleuteld op credential-fingerprint).
 */
class Exact
{
    /**
     * @param  Closure(ExactCredentials): OAuthAuthenticator  $authenticatorFactory
     * @param  Closure(OAuthAuthenticator, string): ExactConnector  $connectorFactory
     */
    public function __construct(
        private readonly ExactCredentialResolver $resolver,
        private readonly TokenStore $tokenStore,
        private readonly Closure $authenticatorFactory,
        private readonly Closure $connectorFactory,
        private readonly AuthorizeUrlBuilder $authorizeUrlBuilder,
        private readonly AuthConnector $authConnector,
    ) {
    }

    public function credentials(): ExactCredentials
    {
        return $this->resolver->resolve();
    }

    public function tokenStore(): TokenStore
    {
        return $this->tokenStore;
    }

    public function authenticator(): OAuthAuthenticator
    {
        return ($this->authenticatorFactory)($this->credentials());
    }

    /**
     * Saloon-connector voor de gegeven division (bv. '4471372', of 'current'
     * voor `/api/v1/current/Me`).
     */
    public function connector(string $division): ExactConnector
    {
        return ($this->connectorFactory)($this->authenticator(), $division);
    }

    /**
     * Consent-URL voor de browser-redirect (Hub bewaart + verifieert de state).
     */
    public function authorizeUrl(?string $state = null, ?string $scope = null): string
    {
        $credentials = $this->credentials();

        return $this->authorizeUrlBuilder->build(new AuthorizeUrlParameters(
            clientId: $credentials->clientId,
            redirectUri: $credentials->redirectUri,
            state: $state,
            scope: $scope ?? $credentials->scope,
        ));
    }

    /**
     * Wisselt de authorization_code in voor de eerste tokenbundle. Persisteert
     * NIET — de Hub beslist (callback-controller) en schrijft via z'n TokenStore.
     */
    public function exchangeAuthorizationCode(string $code): AccessToken
    {
        $credentials = $this->credentials();

        $response = $this->authConnector->send(new AuthorizationCodeRequest($credentials, $code));

        if ($response->failed()) {
            throw AuthenticationException::tokenExchangeFailed(
                status: $response->status(),
                body: $response->body(),
                credentialFingerprint: $credentials->fingerprint(),
            );
        }

        /** @var array{access_token?: string, refresh_token?: string, expires_in?: int|string} $body */
        $body = $response->json();

        if ( ! isset($body['access_token'], $body['refresh_token'], $body['expires_in'])) {
            throw AuthenticationException::malformedTokenResponse($response->body());
        }

        return AccessToken::fromTokenResponse($body);
    }
}
