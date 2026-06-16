<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Auth;

use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Data\AccessToken;
use Emeq\ExactApi\Data\ExactCredentials;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Default TokenStore voor standalone/tests — Laravel-cache, gesleuteld op
 * `$credentials->fingerprint()`. NIET auto-gebonden: de host moet 'm bewust
 * binden (zie MissingTokenStoreException). De Hub bindt z'n eigen
 * Connection-backed implementatie.
 *
 * TTL = `exact.token.store_ttl` (lang — de bundle bevat het langlevende
 * refresh-token), niet de 600s access-TTL.
 */
final class CacheTokenStore implements TokenStore
{
    public function __construct(
        private readonly CacheFactory $cacheFactory,
        private readonly Config $config,
    ) {
    }

    public function get(ExactCredentials $credentials): ?AccessToken
    {
        $payload = $this->store()->get($this->key($credentials));

        if ( ! is_array($payload)) {
            return null;
        }

        /** @var array{accessToken: string, refreshToken: string, expiresAt: string} $payload */
        return AccessToken::fromArray($payload);
    }

    public function put(ExactCredentials $credentials, AccessToken $token): void
    {
        $ttl = (int) $this->config->get('exact.token.store_ttl', 2592000);

        $this->store()->put($this->key($credentials), $token->toArray(), $ttl);
    }

    private function store(): Repository
    {
        /** @var string|null $storeName */
        $storeName = $this->config->get('exact.cache.lock_store');

        return $this->cacheFactory->store($storeName);
    }

    private function key(ExactCredentials $credentials): string
    {
        /** @var string $prefix */
        $prefix = $this->config->get('exact.token.store_prefix', 'exact_token_');

        return $prefix . $credentials->fingerprint();
    }
}
