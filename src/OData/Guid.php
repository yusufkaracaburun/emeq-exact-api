<?php

declare(strict_types=1);

namespace Emeq\ExactApi\OData;

use InvalidArgumentException;

/**
 * Getagde GUID-waarde zodat Filter `guid'...'` uitzendt in plaats van een
 * gewoon gequote string.
 *
 * Exact verwacht ID-velden in een `$filter` als `guid'xxxxxxxx-xxxx-…'`; een
 * gewone `'xxxxxxxx-…'` levert een 400. Door de rauwe UUID in deze class te
 * wikkelen staat die bedoeling op de call-site.
 */
final readonly class Guid
{
    public function __construct(public string $value)
    {
        if (1 !== preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $value)) {
            throw new InvalidArgumentException('Guid: invalid UUID format: ' . $value);
        }
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    /**
     * Zelfde als from(), maar levert null in plaats van een exception wanneer de
     * waarde geen UUID is. Voor call-sites die een niet-geverifieerde waarde uit
     * opslag halen en zelf willen beslissen wat er dan moet gebeuren.
     */
    public static function tryFrom(string $value): ?self
    {
        try {
            return new self($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
