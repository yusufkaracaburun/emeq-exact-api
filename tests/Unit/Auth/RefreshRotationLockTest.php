<?php

declare(strict_types=1);

use Emeq\ExactApi\Auth\AuthConnector;
use Emeq\ExactApi\Auth\OAuthAuthenticator;
use Emeq\ExactApi\Auth\RefreshTokenRequest;
use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Data\AccessToken;
use Emeq\ExactApi\Data\ExactCredentials;
use Emeq\ExactApi\Exceptions\AuthenticationException;
use Emeq\ExactApi\Tests\Support\RecordingTokenStore;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('does not refresh when a parallel winner already rotated under the lock', function (): void {
    $stale = AccessToken::fromTokenResponse(
        ['access_token' => 'acc-stale', 'refresh_token' => 'ref-stale', 'expires_in' => '0'],
        new DateTimeImmutable('-30 seconds'),
    );
    $winner = AccessToken::fromTokenResponse(['access_token' => 'acc-winner', 'refresh_token' => 'ref-winner', 'expires_in' => '600']);

    // get() #1 (pre-lock) → stale; #2 (post-lock double-check) → winner.
    $store = new class ($stale, $winner) implements TokenStore {
        private int $calls = 0;

        public function __construct(private readonly AccessToken $stale, private readonly AccessToken $winner)
        {
        }

        public function get(ExactCredentials $credentials): ?AccessToken
        {
            return 1 === ++$this->calls ? $this->stale : $this->winner;
        }

        public function put(ExactCredentials $credentials, AccessToken $token): void
        {
            throw new RuntimeException('should not refresh — winner already rotated');
        }
    };

    $authConnector = new AuthConnector(baseUrl: 'https://start.exactonline.nl');
    $mock          = new MockClient([
        RefreshTokenRequest::class => MockResponse::make(['access_token' => 'x', 'token_type' => 'bearer', 'expires_in' => '600', 'refresh_token' => 'y'], 200),
    ]);
    $authConnector->withMockClient($mock);

    $authenticator = new OAuthAuthenticator(exactCreds(), $store, $authConnector, app(CacheFactory::class), null, 'exact_refresh_', 0, 5, 10);

    $pending = exactPendingRequest();
    $authenticator->set($pending);

    expect($pending->headers()->get('Authorization'))->toBe('Bearer acc-winner');
    $mock->assertNothingSent();
});

it('refreshes exactly once when the token is still stale under the lock', function (): void {
    $stale = AccessToken::fromTokenResponse(
        ['access_token' => 'acc-stale', 'refresh_token' => 'ref-stale', 'expires_in' => '0'],
        new DateTimeImmutable('-30 seconds'),
    );
    $store = new RecordingTokenStore($stale);

    $authConnector = new AuthConnector(baseUrl: 'https://start.exactonline.nl');
    $mock          = new MockClient([
        RefreshTokenRequest::class => MockResponse::make(['access_token' => 'acc-new', 'token_type' => 'bearer', 'expires_in' => '600', 'refresh_token' => 'ref-new'], 200),
    ]);
    $authConnector->withMockClient($mock);

    $authenticator = new OAuthAuthenticator(exactCreds(), $store, $authConnector, app(CacheFactory::class), null, 'exact_refresh_', 0, 5, 10);

    $authenticator->set(exactPendingRequest());

    expect($store->puts)->toHaveCount(1);
    $mock->assertSentCount(1);
});

it('throws refreshLockTimeout when the per-connection lock is held', function (): void {
    $credentials = exactCreds();
    $key         = 'exact_refresh_' . $credentials->fingerprint();

    // Hou de lock vast zodat block() time-out.
    app(CacheFactory::class)->store(null)->lock($key, 10)->get();

    $stale = AccessToken::fromTokenResponse(
        ['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => '0'],
        new DateTimeImmutable('-30 seconds'),
    );

    $authConnector = new AuthConnector(baseUrl: 'https://start.exactonline.nl');
    $authConnector->withMockClient(new MockClient([RefreshTokenRequest::class => MockResponse::make([], 200)]));

    $authenticator = new OAuthAuthenticator($credentials, new RecordingTokenStore($stale), $authConnector, app(CacheFactory::class), null, 'exact_refresh_', 0, 1, 10);

    expect(fn () => $authenticator->set(exactPendingRequest()))
        ->toThrow(AuthenticationException::class, 'refresh-lock');
});
