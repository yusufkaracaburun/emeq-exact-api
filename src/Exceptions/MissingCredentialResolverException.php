<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Exceptions;

use Emeq\ExactApi\Contracts\ExactCredentialResolver;

/**
 * Gegooid als het package gebruikt wordt zonder dat de host een
 * ExactCredentialResolver bond. Het package is tenant-agnostisch, dus de host
 * MOET vertellen hoe credentials opgehaald worden.
 */
final class MissingCredentialResolverException extends ExactException
{
    public static function notBound(): self
    {
        return new self(sprintf(
            'No %s binding found in the container. Bind your resolver in a ServiceProvider, e.g.: ' .
            "\$this->app->bind(%s::class, YourTenantResolver::class);",
            ExactCredentialResolver::class,
            ExactCredentialResolver::class,
        ));
    }
}
