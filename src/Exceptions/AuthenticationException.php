<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Exceptions;

/**
 * OAuth2-/auth-fouten. De clientSecret en raw tokens komen NOOIT in de message —
 * alleen een fingerprint, zodat logs geen secrets lekken.
 */
final class AuthenticationException extends ExactException
{
    /**
     * Bij een API-call-fout (401/403) draagt dit de upstream-status zodat de Hub
     * 403 (rechten/scope/division) van 401 (token vervangen) kan onderscheiden.
     * Null voor token-exchange/refresh-fouten.
     */
    public function __construct(string $message, public readonly ?int $apiStatus = null)
    {
        parent::__construct($message);
    }

    public static function notAuthenticated(string $credentialFingerprint): self
    {
        return new self(sprintf(
            'Geen opgeslagen Exact-token voor connection (fp:%s). De host moet eerst de consent-flow (authorization_code) doorlopen.',
            mb_substr($credentialFingerprint, 0, 12),
        ));
    }

    public static function tokenExchangeFailed(int $status, string $body, string $credentialFingerprint): self
    {
        return new self(sprintf(
            'Exact authorization_code-exchange gaf HTTP %d voor connection (fp:%s). Body: %s',
            $status,
            mb_substr($credentialFingerprint, 0, 12),
            self::truncate($body),
        ));
    }

    public static function refreshFailed(int $status, string $body, string $credentialFingerprint): self
    {
        return new self(sprintf(
            'Exact refresh gaf HTTP %d voor connection (fp:%s) — refresh-token mogelijk al verbruikt/ingetrokken; her-consent nodig. Body: %s',
            $status,
            mb_substr($credentialFingerprint, 0, 12),
            self::truncate($body),
        ));
    }

    public static function refreshLockTimeout(string $credentialFingerprint): self
    {
        return new self(sprintf(
            'Kon de refresh-lock niet verkrijgen voor connection (fp:%s) — een parallelle refresh duurt te lang.',
            mb_substr($credentialFingerprint, 0, 12),
        ));
    }

    public static function malformedTokenResponse(string $body): self
    {
        return new self('Exact token-endpoint gaf een respons zonder access_token / refresh_token / expires_in. Body: ' . self::truncate($body));
    }

    public static function apiUnauthorized(int $status, string $body): self
    {
        return new self(
            sprintf('Exact API gaf HTTP %d (token verlopen of onvoldoende rechten). Body: %s', $status, self::truncate($body)),
            $status,
        );
    }

    private static function truncate(string $body, int $max = 500): string
    {
        return mb_strlen($body) > $max ? mb_substr($body, 0, $max) . '…' : $body;
    }
}
