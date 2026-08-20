<?php

declare(strict_types=1);

namespace Emeq\ExactApi\OData;

use InvalidArgumentException;

/**
 * Bouwt OData `$filter`-expressies voor Exact Online.
 *
 * Bestaat om één reden: het quoten en escapen van waarden mag geen call-site-
 * verantwoordelijkheid zijn. Een niet-geëscapete apostrof breekt de expressie
 * (of erger: verandert 'm), en een ID-veld heeft `guid'...'` nodig waar een
 * tekstveld `'...'` krijgt.
 *
 * Waarde-opmaak volgt het PHP-type:
 *   - string            → 'value' (elke ' verdubbeld)
 *   - int|float         → kale literal
 *   - bool              → true/false
 *   - null              → null
 *   - Guid              → guid'...'
 *
 * De operator-set is bewust minimaal: alleen wat de Hub aantoonbaar gebruikt en
 * wat tegen een echte administratie gedraaid heeft. Alles daarbuiten —
 * `substringof()`, `startswith()`, samengestelde and/or, navigatiepaden — gaat
 * via `Filter::raw()`, waar de caller zelf verantwoordelijk is voor de escaping.
 */
final readonly class Filter
{
    private function __construct(public string $expression)
    {
    }

    /**
     * Ontsnappingsluik voor expressies die deze class niet uitdrukt. De caller
     * bezit de escaping.
     */
    public static function raw(string $expression): self
    {
        return new self($expression);
    }

    public static function eq(string $property, mixed $value): self
    {
        return new self(sprintf('%s eq %s', $property, self::formatValue($value)));
    }

    private static function formatValue(mixed $value): string
    {
        return match (true) {
            null === $value                  => 'null',
            is_bool($value)                  => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) $value,
            $value instanceof Guid           => sprintf("guid'%s'", $value->value),
            is_string($value)                => sprintf("'%s'", str_replace("'", "''", $value)),
            default                          => throw new InvalidArgumentException('Filter value of unsupported type: ' . get_debug_type($value)),
        };
    }
}
