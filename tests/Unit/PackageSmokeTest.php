<?php

declare(strict_types=1);

use Emeq\ExactApi\Auth\AuthConnector;
use Emeq\ExactApi\Auth\AuthorizeUrlBuilder;
use Emeq\ExactApi\Auth\CacheTokenStore;
use Emeq\ExactApi\Auth\OAuthAuthenticator;
use Emeq\ExactApi\Contracts\ExactCredentialResolver;
use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Exact;
use Emeq\ExactApi\ExactServiceProvider;
use Emeq\ExactApi\Exceptions\MissingCredentialResolverException;
use Emeq\ExactApi\Exceptions\MissingTokenStoreException;
use Emeq\ExactApi\Http\ExactConnector;
use Emeq\ExactApi\Tests\Support\FakeCredentialResolver;

it('registers the service provider', function (): void {
    expect(app()->getProvider(ExactServiceProvider::class))->toBeInstanceOf(ExactServiceProvider::class);
});

it('publishes the exact config with safety_margin 0', function (): void {
    expect(config('exact.api_base_url'))->toBe('https://start.exactonline.nl')
        ->and(config('exact.auth_base_url'))->toBe('https://start.exactonline.nl')
        ->and(config('exact.cache.ttl_safety_margin'))->toBe(0)
        ->and(config('exact.token.store_prefix'))->toBe('exact_token_');
});

it('binds AuthConnector as a singleton at the auth base URL', function (): void {
    $first  = app(AuthConnector::class);
    $second = app(AuthConnector::class);

    expect($first)->toBe($second)
        ->and($first->resolveBaseUrl())->toBe('https://start.exactonline.nl');
});

it('binds the AuthorizeUrlBuilder as a singleton', function (): void {
    expect(app(AuthorizeUrlBuilder::class))->toBe(app(AuthorizeUrlBuilder::class));
});

it('throws MissingCredentialResolverException when no resolver is bound', function (): void {
    expect(fn () => app(Exact::class))->toThrow(MissingCredentialResolverException::class);
});

it('throws MissingTokenStoreException when a resolver is bound but no store', function (): void {
    app()->bind(ExactCredentialResolver::class, fn () => FakeCredentialResolver::with());

    expect(fn () => app(Exact::class))->toThrow(MissingTokenStoreException::class);
});

it('resolves the Exact client when resolver and store are bound', function (): void {
    app()->bind(ExactCredentialResolver::class, fn () => FakeCredentialResolver::with());
    app()->bind(TokenStore::class, CacheTokenStore::class);

    $exact = app(Exact::class);

    expect($exact)->toBeInstanceOf(Exact::class)
        ->and($exact->credentials()->clientId)->toBe('test-client-id')
        ->and($exact->tokenStore())->toBeInstanceOf(CacheTokenStore::class)
        ->and($exact->authenticator())->toBeInstanceOf(OAuthAuthenticator::class);
});

it('builds a division-scoped ExactConnector', function (): void {
    app()->bind(ExactCredentialResolver::class, fn () => FakeCredentialResolver::with());
    app()->bind(TokenStore::class, CacheTokenStore::class);

    $connector = app(Exact::class)->connector('4471372');

    expect($connector)->toBeInstanceOf(ExactConnector::class)
        ->and($connector->resolveBaseUrl())->toBe('https://start.exactonline.nl/api/v1/4471372');
});

it('builds the authorize URL from the resolved credentials', function (): void {
    app()->bind(ExactCredentialResolver::class, fn () => FakeCredentialResolver::with());
    app()->bind(TokenStore::class, CacheTokenStore::class);

    $url = app(Exact::class)->authorizeUrl('state-123');

    expect($url)->toContain('/api/oauth2/auth')
        ->and($url)->toContain('client_id=test-client-id')
        ->and($url)->toContain('state=state-123');
});
