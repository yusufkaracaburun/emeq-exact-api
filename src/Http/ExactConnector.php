<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http;

use Emeq\ExactApi\Auth\OAuthAuthenticator;
use Emeq\ExactApi\Exceptions\AuthenticationException;
use Emeq\ExactApi\Exceptions\NotFoundException;
use Emeq\ExactApi\Exceptions\RateLimitException;
use Emeq\ExactApi\Exceptions\ServerException;
use Emeq\ExactApi\Exceptions\ValidationException;
use Saloon\Contracts\Authenticator;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

/**
 * Hoofd-Saloon-connector tegen `{api_base_url}/api/v1/{division}`.
 *
 * Per-call instance (built door Exact::connector($division)) met de
 * tenant-specifieke OAuthAuthenticator. De division zit in het pad (geen header).
 * Retryt automatisch op 429 + transient 5xx; andere fouten bubbelen als een
 * ExactException-subclass via getRequestException().
 */
class ExactConnector extends Connector
{
    private readonly OAuthAuthenticator $oauthAuthenticator;

    /**
     * @param  list<int>  $retryOnStatuses
     */
    public function __construct(
        private readonly string $apiBaseUrl,
        private readonly string $division,
        OAuthAuthenticator $authenticator,
        private readonly int $timeoutSeconds = 30,
        public ?int $tries = 3,
        public ?int $retryInterval = 1000,
        private readonly array $retryOnStatuses = [429, 500, 502, 503, 504],
    ) {
        // Onder een andere naam opgeslagen: Saloon\Http\Connector declareert al
        // een niet-readonly $authenticator-property die we niet mogen herdeclareren.
        $this->oauthAuthenticator = $authenticator;
    }

    public function resolveBaseUrl(): string
    {
        return mb_rtrim($this->apiBaseUrl, '/') . '/api/v1/' . $this->division;
    }

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        if ($exception instanceof FatalRequestException) {
            return true;
        }

        return in_array($exception->getResponse()->status(), $this->retryOnStatuses, true);
    }

    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        $status = $response->status();
        $body   = $response->body();

        return match (true) {
            400 === $status                  => ValidationException::fromBody($body),
            401 === $status, 403 === $status => AuthenticationException::apiUnauthorized($status, $body),
            404 === $status                  => NotFoundException::forUrl((string) $response->getPendingRequest()->getUrl()),
            429 === $status                  => RateLimitException::fromBody($body, self::parseRetryAfter($response)),
            $status >= 500 && $status < 600  => ServerException::fromResponse($status, $body),
            default                          => null,
        };
    }

    protected function defaultAuth(): ?Authenticator
    {
        return $this->oauthAuthenticator;
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => $this->timeoutSeconds,
        ];
    }

    private static function parseRetryAfter(Response $response): ?int
    {
        $value = $response->header('Retry-After');

        if (null === $value || '' === $value) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
