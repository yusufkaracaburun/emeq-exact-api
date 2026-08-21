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
     * Het mensleesbare boekstuknummer van het eerste record na een create — Exact's
     * `EntryNumber` (`Edm.Int32`; voor verkoopfacturen het factuurnummer). Naast de
     * `EntryID`-GUID het nummer dat een boekhouder herkent. Niet elke entity draagt 't
     * → null.
     *
     * @param  array<string, mixed>|null  $json
     */
    public static function firstEntryNumber(?array $json): ?int
    {
        if (null === $json) {
            return null;
        }

        $d = $json['d'] ?? null;

        if ( ! is_array($d)) {
            return null;
        }

        $number = $d['EntryNumber']
            ?? ($d['results'][0]['EntryNumber'] ?? null)
            ?? ($d[0]['EntryNumber'] ?? null);

        return null !== $number ? (int) $number : null;
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

    /**
     * De continuation-token van een gepagineerde GET-collectie, of null bij de laatste
     * pagina.
     *
     * Exact hangt bij meer resultaten een `d.__next` aan de envelope: een volledige URL
     * met daarin `$skiptoken`. Alleen die token is bruikbaar voor een vervolgrequest —
     * de rest van die URL (base-url, division) kent de connector al, en overnemen zou
     * betekenen dat je 'm buiten de connector om aanroept.
     *
     * Bewust met een regex en niet met `parse_str()`: die verminkt parameternamen met
     * punten en spaties, en de waarde is URL-encoded OData-syntax (`guid'…'`).
     *
     * @param  array<string, mixed>|null  $json
     */
    public static function nextSkipToken(?array $json): ?string
    {
        $next = $json['d']['__next'] ?? null;

        if ( ! is_string($next) || '' === $next) {
            return null;
        }

        // Exact levert `__next` met een URL-encoded dollar (`%24skiptoken`); de letterlijke
        // vorm komt voor in handgeschreven URL's. Allebei accepteren.
        if (1 !== preg_match('/[?&](?:\$|%24)skiptoken=([^&]+)/i', $next, $matches)) {
            return null;
        }

        return urldecode($matches[1]);
    }

    /**
     * De leesbare foutmelding uit Exact's error-envelope
     * (`{"error":{"message":{"value":"..."}}}`), of null als het body geen
     * herkenbare fout is.
     *
     * Neemt anders dan de andere methodes de rauwe body-string: de fout komt
     * binnen via een exception die z'n `rawBody` ongedecodeerd draagt, en of dat
     * überhaupt JSON is hoort deze decoder te bepalen — niet de caller.
     */
    public static function errorMessage(string $body): ?string
    {
        if ('' === $body) {
            return null;
        }

        $decoded = json_decode($body, true);

        if ( ! is_array($decoded)) {
            return null;
        }

        $value = $decoded['error']['message']['value'] ?? null;

        return (is_string($value) && '' !== $value) ? $value : null;
    }
}
