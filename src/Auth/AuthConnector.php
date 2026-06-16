<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Auth;

use Saloon\Http\Connector;

/**
 * Aparte Saloon-connector voor Exact's OAuth2-endpoints (`/api/oauth2/auth` +
 * `/api/oauth2/token`). Los van ExactConnector: geen Bearer, geen division-path,
 * geen resource-retry/rate-limit-middleware.
 *
 * Singleton in ExactServiceProvider zodat MockClient-fixtures in tests deze
 * connector ook bereiken.
 */
class AuthConnector extends Connector
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds = 10,
    ) {
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => $this->timeoutSeconds,
        ];
    }
}
