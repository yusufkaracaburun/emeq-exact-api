<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Exceptions;

/**
 * HTTP 404 — meestal een onbekende division-path of verwijderde resource-GUID.
 */
final class NotFoundException extends ExactException
{
    public static function forUrl(string $url): self
    {
        return new self('Exact API gaf HTTP 404 voor ' . $url);
    }
}
