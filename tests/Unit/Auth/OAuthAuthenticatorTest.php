<?php

declare(strict_types=1);

use Emeq\ExactApi\Data\AccessToken;
use Emeq\ExactApi\Exceptions\AuthenticationException;
use Emeq\ExactApi\Tests\Support\RecordingTokenStore;
use Saloon\Http\Faking\MockResponse;

it('throws when no token is stored (consent required)', function (): void {
    [$authenticator] = buildExactAuthenticator(store: new RecordingTokenStore(null));

    expect(fn () => $authenticator->set(exactPendingRequest()))
        ->toThrow(AuthenticationException::class, 'consent-flow');
});

it('uses a valid stored token without hitting the auth endpoint', function (): void {
    $valid = AccessToken::fromTokenResponse(['access_token' => 'acc-ok', 'refresh_token' => 'ref-ok', 'expires_in' => '600']);
    $store = new RecordingTokenStore($valid);

    [$authenticator] = buildExactAuthenticator(store: $store);

    $pending = exactPendingRequest();
    $authenticator->set($pending);

    expect($pending->headers()->get('Authorization'))->toBe('Bearer acc-ok')
        ->and($store->puts)->toBeEmpty();
});

it('refreshes an expired token and persists the rotated refresh_token', function (): void {
    $expired = AccessToken::fromTokenResponse(
        ['access_token' => 'acc-old', 'refresh_token' => 'ref-old', 'expires_in' => '0'],
        new DateTimeImmutable('-30 seconds'),
    );
    $store = new RecordingTokenStore($expired);

    [$authenticator] = buildExactAuthenticator(store: $store);

    $pending = exactPendingRequest();
    $authenticator->set($pending);

    expect($pending->headers()->get('Authorization'))->toBe('Bearer acc-new')
        ->and($store->puts)->toHaveCount(1)
        ->and($store->puts[0]->refreshToken)->toBe('ref-new')
        ->and($store->puts[0]->refreshToken)->not->toBe('ref-old');
});

it('throws refreshFailed (with fingerprint, no secret) on invalid_grant', function (): void {
    $expired = AccessToken::fromTokenResponse(
        ['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => '0'],
        new DateTimeImmutable('-30 seconds'),
    );

    [$authenticator] = buildExactAuthenticator(
        refreshResponse: MockResponse::make(['error' => 'invalid_grant'], 400),
        store: new RecordingTokenStore($expired),
    );

    try {
        $authenticator->set(exactPendingRequest());
        $this->fail('expected AuthenticationException');
    } catch (AuthenticationException $e) {
        expect($e->getMessage())->toContain('fp:')
            ->and($e->getMessage())->toContain('HTTP 400');
    }
});

it('treats a 400 not-expired refusal as the current token still valid (clock skew)', function (): void {
    $current = AccessToken::fromTokenResponse(
        ['access_token' => 'acc-current', 'refresh_token' => 'ref-current', 'expires_in' => '0'],
        new DateTimeImmutable('-1 second'),
    );

    [$authenticator] = buildExactAuthenticator(
        refreshResponse: MockResponse::make(['error' => 'access_denied', 'error_description' => 'Rate limit exceeded: access_token not expired'], 400),
        store: new RecordingTokenStore($current),
    );

    $pending = exactPendingRequest();
    $authenticator->set($pending);

    expect($pending->headers()->get('Authorization'))->toBe('Bearer acc-current');
});
