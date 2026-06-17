<?php

declare(strict_types=1);

namespace Emeq\ExactApi\OData;

/**
 * Decodeert de OData-envelope die Exact om elke REST-respons heen zet.
 *
 * GET-collecties komen als `{"d":{"results":[...]}}` of soms `{"d":[...]}`;
 * een POST-create geeft `{"d":{"ID":"...",...}}`. Deze decoder is de enige plek
 * die die vorm kent — callers (Hub of standalone-app) krijgen platte records of
 * een ID terug zonder de envelope te hoeven kennen.
 */
final class Envelope
{
    /**
     * Records uit een GET-collectie (of een enkel record als een lijst van één).
     *
     * @param  array<string, mixed>|null  $json
     * @return list<array<string, mixed>>
     */
    public static function results(?array $json): array
    {
        if (null === $json) {
            return [];
        }

        $d = $json['d'] ?? null;

        if ( ! is_array($d)) {
            return [];
        }

        if (array_key_exists('results', $d) && is_array($d['results'])) {
            return array_values($d['results']);
        }

        return array_is_list($d) ? $d : [$d];
    }

    /**
     * Het `ID`-veld van het eerste record — voor de externe referentie na een create.
     *
     * @param  array<string, mixed>|null  $json
     */
    public static function firstId(?array $json): ?string
    {
        if (null === $json) {
            return null;
        }

        $d = $json['d'] ?? null;

        if ( ! is_array($d)) {
            return null;
        }

        $id = $d['ID']
            ?? ($d['results'][0]['ID'] ?? null)
            ?? ($d[0]['ID'] ?? null);

        return null !== $id ? (string) $id : null;
    }
}
