<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Webhooks;

/**
 * HMAC-verifier voor Exact-webhook-ingress.
 *
 * Exact tekent elke inbound notificatie met een `HashCode` = base64 van de
 * HMAC-SHA256 over de **JSON van de `Content`-node (inclusief de accolades)**,
 * met de **app-brede** Webhook-secret. De hashcode zit in het body-veld
 * `HashCode`, NIET in een header — anders dan Snelstart/Mollie (die over de
 * raw body tekenen en in een header leveren).
 *
 * De verifier moet daarom de **letterlijke `Content`-substring** uit de raw body
 * halen (geen json_decode→re-encode, want herordening/spacing breekt de HMAC) en
 * vergelijken met `HashCode`. Constant-time (`hash_equals`).
 *
 * Match-pattern met `Emeq\SnelstartApi\Webhooks\SnelstartWebhookSignature`: pure
 * partner-protocol-laag, geen framework-state, callers passen ze in.
 *
 * @see docs/partners/exact/webhooks.md — base64-encoding bevestigd tegen Exact's
 *      .NET-sample; live-ping verifieert het wire-formaat definitief.
 */
final class ExactWebhookSignature
{
    /**
     * Bereken de HashCode over de letterlijke Content-node-JSON.
     */
    public static function sign(string $contentJson, string $secret, string $algo = 'sha256'): string
    {
        // Uppercase hex — live-geverifieerd tegen de echte Exact-wire (2026-06-18).
        // Exact's docs noemen "byte array of length 40", maar de feitelijke HashCode
        // is de 64-char uppercase hex van de HMAC-SHA256 (32 bytes), GEEN base64.
        return mb_strtoupper(hash_hmac($algo, $contentJson, $secret));
    }

    /**
     * @return bool  true = HashCode matcht de over de Content-node berekende HMAC;
     *               false = geen secret, geen Content-node, geen HashCode, of mismatch.
     */
    public static function verify(string $rawBody, string $secret, string $algo = 'sha256'): bool
    {
        if ('' === $secret) {
            return false;
        }

        $content  = self::extractContentJson($rawBody);
        $hashCode = self::extractHashCode($rawBody);

        if (null === $content || null === $hashCode) {
            return false;
        }

        // Exact levert uppercase hex; normaliseer de ontvangen waarde zodat een
        // (theoretische) lowercase-variant niet stilletjes faalt. hash_equals
        // blijft constant-time over de strings.
        return hash_equals(self::sign($content, $secret, $algo), mb_strtoupper($hashCode));
    }

    /**
     * Haalt de letterlijke `{...}`-substring van de `Content`-node uit de raw body.
     * Balanceert accolades met respect voor strings en escapes, zodat de exacte
     * bytes die Exact tekende behouden blijven.
     *
     * Char-based (consistent met de project-`mb_*`-stijl): de JSON-structuur-tekens
     * `{` `}` `"` `\` zijn single-char, dus brace-matching op char-niveau is correct;
     * `mb_substr` levert de juiste UTF-8-bytes terug voor de HMAC, ook bij een
     * multibyte teken in bv. een Description.
     */
    public static function extractContentJson(string $rawBody): ?string
    {
        $marker = '"Content"';
        $pos    = mb_strpos($rawBody, $marker);

        if (false === $pos) {
            return null;
        }

        $start = mb_strpos($rawBody, '{', $pos + mb_strlen($marker));

        if (false === $start) {
            return null;
        }

        $depth    = 0;
        $inString = false;
        $escaped  = false;
        $length   = mb_strlen($rawBody);

        for ($i = $start; $i < $length; $i++) {
            $char = mb_substr($rawBody, $i, 1);

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ('\\' === $char) {
                    $escaped = true;
                } elseif ('"' === $char) {
                    $inString = false;
                }

                continue;
            }

            if ('"' === $char) {
                $inString = true;
            } elseif ('{' === $char) {
                $depth++;
            } elseif ('}' === $char) {
                $depth--;

                if (0 === $depth) {
                    return mb_substr($rawBody, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    private static function extractHashCode(string $rawBody): ?string
    {
        $decoded = json_decode($rawBody, true);

        if ( ! is_array($decoded) || ! isset($decoded['HashCode']) || ! is_string($decoded['HashCode'])) {
            return null;
        }

        return $decoded['HashCode'];
    }
}
