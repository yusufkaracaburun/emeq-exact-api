<?php

declare(strict_types=1);

namespace Emeq\ExactApi\OData;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Leest Exact's datumnotatie in JSON-responses: `/Date(1755123456000)/`, het
 * Microsoft-formaat voor Edm.DateTime — epoch-milliseconden, soms met een
 * offset-suffix (`/Date(1755123456000+0200)/`).
 *
 * De offset wordt genegeerd: de epoch-waarde is al een absoluut moment, dus het
 * resultaat is altijd UTC. Wie lokale tijd wil, converteert zelf.
 */
final class DateValue
{
    public static function parse(mixed $value): ?DateTimeImmutable
    {
        if ( ! is_string($value) || 1 !== preg_match('/^\/Date\((-?\d+)/', $value, $matches)) {
            return null;
        }

        $milliseconds = (int) $matches[1];

        return (new DateTimeImmutable('@' . (int) floor($milliseconds / 1000)))
            ->setTimezone(new DateTimeZone('UTC'));
    }
}
