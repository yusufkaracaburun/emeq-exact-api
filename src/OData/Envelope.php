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
     * De externe referentie van het eerste record na een create.
     *
     * Probeert `EntryID` vóór `ID`: GeneralJournalEntries geven `EntryID` terug,
     * SalesEntries/PurchaseEntries `ID`. Valt door naar de results-/lijst-varianten.
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

        $id = $d['EntryID']
            ?? $d['ID']
            ?? ($d['results'][0]['EntryID'] ?? null)
            ?? ($d['results'][0]['ID'] ?? null)
            ?? ($d[0]['EntryID'] ?? null)
            ?? ($d[0]['ID'] ?? null);

        return null !== $id ? (string) $id : null;
    }

    /**
     * De GUID van het Document dat Exact bij een create automatisch koppelt — staat op
     * `d.Document`. PurchaseEntries krijgen er één (inkoopfactuur-registratie); SalesEntries
     * niet → null. Hiermee koppelt de caller een bijlage aan het bestaande Document i.p.v.
     * een tweede aan te maken.
     *
     * @param  array<string, mixed>|null  $json
     */
    public static function documentRef(?array $json): ?string
    {
        $ref = $json['d']['Document'] ?? null;

        return (null !== $ref && '' !== $ref) ? (string) $ref : null;
    }
}
