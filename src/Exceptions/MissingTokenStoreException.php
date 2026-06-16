<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Exceptions;

use Emeq\ExactApi\Contracts\TokenStore;

/**
 * Gegooid als het package gebruikt wordt zonder gebonden TokenStore.
 *
 * Bewust géén stille default: Exact's refresh-token is single-use/roterend en de
 * persistence is de énige kopie — een stille cache-default zou een vergeten
 * Hub-wiring maskeren en de Connection na de eerste refresh stilletjes laten
 * sterven. De host MOET expliciet een TokenStore binden (CacheTokenStore voor
 * standalone, of een Connection-backed impl in de Hub).
 */
final class MissingTokenStoreException extends ExactException
{
    public static function notBound(): self
    {
        return new self(sprintf(
            'No %s binding found in the container. Bind one in a ServiceProvider, e.g.: ' .
            "\$this->app->bind(%s::class, YourConnectionTokenStore::class); (of %s\\CacheTokenStore voor standalone).",
            TokenStore::class,
            TokenStore::class,
            'Emeq\\ExactApi\\Auth',
        ));
    }
}
