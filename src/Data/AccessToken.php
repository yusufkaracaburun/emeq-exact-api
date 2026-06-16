<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Data;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * OAuth2-tokenbundle van Exact Online.
 *
 * Exact geeft `{ access_token, token_type: "bearer", expires_in: "600", refresh_token }`.
 * `expires_in` komt als STRING binnen → naar int gecast. Anders dan Snelstart
 * draagt deze bundle óók het refresh-token: dat roteert bij élke refresh en is
 * dus onderdeel van wat de host moet persisteren.
 */
final readonly class AccessToken
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public DateTimeImmutable $expiresAt,
    ) {
        if ('' === mb_trim($this->accessToken)) {
            throw new InvalidArgumentException('AccessToken: accessToken may not be empty.');
        }

        if ('' === mb_trim($this->refreshToken)) {
            throw new InvalidArgumentException('AccessToken: refreshToken may not be empty.');
        }
    }

    /**
     * @param  array{access_token?: string, refresh_token?: string, expires_in?: int|string}  $body
     */
    public static function fromTokenResponse(array $body, ?DateTimeImmutable $now = null): self
    {
        $now ??= new DateTimeImmutable();

        return new self(
            accessToken: (string) ($body['access_token'] ?? ''),
            refreshToken: (string) ($body['refresh_token'] ?? ''),
            expiresAt: $now->modify('+' . (int) ($body['expires_in'] ?? 0) . ' seconds'),
        );
    }

    /**
     * @param  array{accessToken: string, refreshToken: string, expiresAt: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: $data['accessToken'],
            refreshToken: $data['refreshToken'],
            expiresAt: new DateTimeImmutable($data['expiresAt']),
        );
    }

    /**
     * @param  int  $safetyMarginSeconds  behandel de token deze seconden vóór de echte expiry al als verlopen
     */
    public function isExpired(int $safetyMarginSeconds = 0, ?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();
        $cutoff = $this->expiresAt->modify('-' . $safetyMarginSeconds . ' seconds');

        return $now >= $cutoff;
    }

    /**
     * @return array{accessToken: string, refreshToken: string, expiresAt: string}
     */
    public function toArray(): array
    {
        return [
            'accessToken'  => $this->accessToken,
            'refreshToken' => $this->refreshToken,
            'expiresAt'    => $this->expiresAt->format(DATE_ATOM),
        ];
    }
}
