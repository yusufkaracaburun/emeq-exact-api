<?php

declare(strict_types=1);

use Emeq\ExactApi\Auth\AuthConnector;
use Emeq\ExactApi\Auth\AuthorizationCodeRequest;
use Emeq\ExactApi\Auth\CacheTokenStore;
use Emeq\ExactApi\Auth\RefreshTokenRequest;
use Emeq\ExactApi\Contracts\ExactCredentialResolver;
use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Data\ExactCredentials;
use Emeq\ExactApi\Exact;
use Emeq\ExactApi\Tests\Support\FakeCredentialResolver;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('builds the authorization_code form body', function (): void {
    $request = new AuthorizationCodeRequest(new ExactCredentials('cid', 'sec', 'https://cb', 'conn'), 'the-code');

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/api/oauth2/token')
        ->and($request->body()->all())->toBe([
            'grant_type'    => 'authorization_code',
            'client_id'     => 'cid',
            'client_secret' => 'sec',
            'redirect_uri'  => 'https://cb',
            'code'          => 'the-code',
        ]);
});

it('builds the refresh_token form body without redirect_uri or scope', function (): void {
    $request = new RefreshTokenRequest(new ExactCredentials('cid', 'sec', 'https://cb', 'conn'), 'the-refresh');

    expect($request->resolveEndpoint())->toBe('/api/oauth2/token')
        ->and($request->body()->all())->toBe([
            'grant_type'    => 'refresh_token',
            'refresh_token' => 'the-refresh',
            'client_id'     => 'cid',
            'client_secret' => 'sec',
        ]);
});

it('exchanges an authorization code into a token bundle', function (): void {
    app()->bind(ExactCredentialResolver::class, fn () => FakeCredentialResolver::with());
    app()->bind(TokenStore::class, CacheTokenStore::class);

    app(AuthConnector::class)->withMockClient(new MockClient([
        AuthorizationCodeRequest::class => MockResponse::make([
            'access_token' => 'acc', 'token_type' => 'bearer', 'expires_in' => '600', 'refresh_token' => 'ref',
        ], 200),
    ]));

    $token = app(Exact::class)->exchangeAuthorizationCode('the-code');

    expect($token->accessToken)->toBe('acc')
        ->and($token->refreshToken)->toBe('ref');
});
