<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Auth;

use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Data\AccessToken;
use Emeq\ExactApi\Data\ExactCredentials;
use Emeq\ExactApi\Exceptions\AuthenticationException;
use Emeq\ExactApi\Exceptions\ExactException;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

/**
 * Saloon Authenticator voor Exact's OAuth2 (authorization_code + roterende refresh).
 *
 * Op elke uitgaande request:
 *   1. Haalt de bundle via TokenStore (host bezit de opslag).
 *   2. Géén opgeslagen token → AuthenticationException (host moet consent doen).
 *   3. Verlopen → refresht onder een per-connection lock en zet Authorization.
 *
 * Exact-specifiek (empirisch geverifieerd):
 *   - safetyMargin = 0: NIET proactief refreshen — Exact weigert refresh zolang
 *     de access-token nog geldig is ("Rate limit exceeded: access_token not expired").
 *   - Roterend refresh-token: elke refresh levert een NIEUW refresh-token; we
 *     persisteren het direct (vóór return) zodat de volgende refresh werkt.
 *   - Lock + double-check: twee parallelle requests mogen niet beide het (single-use)
 *     refresh-token verbruiken; de verliezer leest de bundle van de winnaar.
 *   - Clock-skew-guard: krijgt de refresh 400 "not expired", dan is de huidige
 *     token volgens Exact nog geldig → gebruik 'm i.p.v. falen.
 */
final class OAuthAuthenticator implements Authenticator
{
    public function __construct(
        private readonly ExactCredentials $credentials,
        private readonly TokenStore $tokenStore,
        private readonly AuthConnector $authConnector,
        private readonly CacheFactory $cacheFactory,
        private readonly ?string $lockStore = null,
        private readonly string $lockPrefix = 'exact_refresh_',
        private readonly int $safetyMarginSeconds = 0,
        private readonly int $lockWaitSeconds = 8,
        private readonly int $lockTtlSeconds = 10,
    ) {
    }

    public function set(PendingRequest $pendingRequest): void
    {
        $token = $this->resolveToken();

        $pendingRequest->headers()->add('Authorization', 'Bearer ' . $token->accessToken);
    }

    /**
     * Forceer een refresh ongeacht de huidige expiry — voor de 401-retry-pad
     * (een API-call kreeg 401 omdat de token net verliep).
     */
    public function forceRefresh(): AccessToken
    {
        $current = $this->tokenStore->get($this->credentials);

        if (null === $current) {
            throw AuthenticationException::notAuthenticated($this->credentials->fingerprint());
        }

        return $this->refreshUnderLock($current);
    }

    private function resolveToken(): AccessToken
    {
        $current = $this->tokenStore->get($this->credentials);

        if (null === $current) {
            throw AuthenticationException::notAuthenticated($this->credentials->fingerprint());
        }

        if ( ! $current->isExpired($this->safetyMarginSeconds)) {
            return $current;
        }

        return $this->refreshUnderLock($current);
    }

    private function refreshUnderLock(AccessToken $stale): AccessToken
    {
        $lock = $this->newLock();

        try {
            $lock->block($this->lockWaitSeconds);
        } catch (LockTimeoutException) {
            throw AuthenticationException::refreshLockTimeout($this->credentials->fingerprint());
        }

        try {
            // Double-check: een parallelle winnaar kan onder de lock al geroteerd
            // hebben. Dan NIET nogmaals refreshen met het inmiddels-verbruikte token.
            $fresh = $this->tokenStore->get($this->credentials);

            if (null !== $fresh && ! $fresh->isExpired($this->safetyMarginSeconds)) {
                return $fresh;
            }

            return $this->performRefresh($stale);
        } finally {
            $lock->release();
        }
    }

    private function performRefresh(AccessToken $stale): AccessToken
    {
        $response = $this->authConnector->send(new RefreshTokenRequest($this->credentials, $stale->refreshToken));

        if ($response->failed()) {
            // Clock-skew: onze klok liep vóór en zei "verlopen", maar Exact vindt
            // de access-token nog geldig. De huidige token is dus nog bruikbaar.
            if ($this->refusedBecauseNotExpired($response)) {
                return $stale;
            }

            throw AuthenticationException::refreshFailed(
                status: $response->status(),
                body: $response->body(),
                credentialFingerprint: $this->credentials->fingerprint(),
            );
        }

        /** @var array{access_token?: string, refresh_token?: string, expires_in?: int|string} $body */
        $body = $response->json();

        if ( ! isset($body['access_token'], $body['refresh_token'], $body['expires_in'])) {
            throw AuthenticationException::malformedTokenResponse($response->body());
        }

        $rotated = AccessToken::fromTokenResponse($body);

        // Persist VÓÓR return: het oude (single-use) refresh-token is nu dood, dus
        // de nieuwe bundle moet durabel zijn voordat er een API-call op leunt.
        $this->tokenStore->put($this->credentials, $rotated);

        return $rotated;
    }

    private function refusedBecauseNotExpired(Response $response): bool
    {
        return 400 === $response->status() && str_contains($response->body(), 'not expired');
    }

    /**
     * Verkrijgt een per-connection lock via de onderliggende store. De
     * Repository-contract exposed geen lock(); we narrowen daarom naar de
     * LockProvider-store. Een store zonder atomic locks = config-fout.
     */
    private function newLock(): Lock
    {
        $repository = $this->cacheFactory->store($this->lockStore);
        $store      = $repository instanceof CacheRepository ? $repository->getStore() : null;

        if ( ! $store instanceof LockProvider) {
            throw new ExactException(
                'Exact refresh-lock vereist een cache-store met atomic locks (redis/database/memcached/dynamodb/array). Configureer exact.cache.lock_store.',
            );
        }

        return $store->lock($this->lockPrefix . $this->credentials->fingerprint(), $this->lockTtlSeconds);
    }
}
