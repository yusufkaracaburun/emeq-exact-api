<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Data;

use InvalidArgumentException;

/**
 * Per-connection Exact Online OAuth2-credentials.
 *
 * - clientId / clientSecret / redirectUri — de geregistreerde Exact-app (gedeeld
 *   over álle tenants).
 * - connectionRef — opaque per-connection identifier die de host levert (bv. de
 *   Connection-id). Onmisbaar: zonder dit zou de fingerprint over tenants heen
 *   collideren (clientId is immers gedeeld), en zouden token-opslag + refresh-lock
 *   per-app i.p.v. per-connection werken.
 * - scope — optioneel; Exact-apps gebruiken niet altijd scopes.
 */
final readonly class ExactCredentials
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public string $redirectUri,
        public string $connectionRef,
        public ?string $scope = null,
    ) {
        foreach (['clientId', 'clientSecret', 'redirectUri', 'connectionRef'] as $field) {
            if ('' === mb_trim($this->{$field})) {
                throw new InvalidArgumentException("ExactCredentials: {$field} may not be empty.");
            }
        }
    }

    /**
     * @param  array{clientId: string, clientSecret: string, redirectUri: string, connectionRef: string, scope?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['clientId'],
            clientSecret: $data['clientSecret'],
            redirectUri: $data['redirectUri'],
            connectionRef: $data['connectionRef'],
            scope: $data['scope'] ?? null,
        );
    }

    /**
     * Stabiele, niet-omkeerbare per-connection sleutel voor cache + lock. Hasht
     * clientId + connectionRef (NIET de secret) zodat twee tenants op dezelfde app
     * nooit collideren en de raw secret nergens in logs/cache lekt.
     */
    public function fingerprint(): string
    {
        return hash('sha256', $this->clientId . '|' . $this->connectionRef);
    }
}
