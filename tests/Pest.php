<?php

declare(strict_types=1);

use Emeq\ExactApi\Auth\AuthConnector;
use Emeq\ExactApi\Auth\OAuthAuthenticator;
use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Data\ExactCredentials;
use Emeq\ExactApi\Http\ExactConnector;
use Emeq\ExactApi\Tests\Support\RecordingTokenStore;
use Emeq\ExactApi\Tests\TestCase;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\Cache;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

uses(TestCase::class)->in(__DIR__);

// TokenStore/cache zijn singletons en de array-cache leeft binnen het
// Testbench-proces; flushen houdt elke test hermetisch.
uses()->beforeEach(function (): void {
    Cache::flush();
})->in(__DIR__);

function exactCreds(string $connectionRef = 'conn-1'): ExactCredentials
{
    return new ExactCredentials(
        clientId: 'cid',
        clientSecret: 'sec',
        redirectUri: 'https://hub.emeq.test/v1/oauth/exact/callback',
        connectionRef: $connectionRef,
    );
}

/**
 * Minimale Saloon-request om de headers die de authenticator zet te asserten.
 */
final class ExactFakeApiRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/echo';
    }
}

function exactPendingRequest(): PendingRequest
{
    $connector = new AuthConnector(baseUrl: 'https://start.exactonline.nl');
    $connector->withMockClient(new MockClient([
        ExactFakeApiRequest::class => MockResponse::make([], 200),
    ]));

    return $connector->createPendingRequest(new ExactFakeApiRequest());
}

/**
 * @param  array<string, mixed>|MockResponse  $refreshResponse
 * @return array{0: OAuthAuthenticator, 1: ExactCredentials, 2: TokenStore}
 */
function buildExactAuthenticator(
    array|MockResponse $refreshResponse = ['access_token' => 'acc-new', 'token_type' => 'bearer', 'expires_in' => '600', 'refresh_token' => 'ref-new'],
    ?TokenStore $store = null,
    int $safetyMargin = 0,
    int $lockWait = 5,
): array {
    $credentials = exactCreds();

    $authConnector = new AuthConnector(baseUrl: 'https://start.exactonline.nl');
    $authConnector->withMockClient(new MockClient([
        Emeq\ExactApi\Auth\RefreshTokenRequest::class => is_array($refreshResponse) ? MockResponse::make($refreshResponse) : $refreshResponse,
    ]));

    $store ??= new RecordingTokenStore();

    $authenticator = new OAuthAuthenticator(
        credentials: $credentials,
        tokenStore: $store,
        authConnector: $authConnector,
        cacheFactory: app(CacheFactory::class),
        lockStore: null,
        lockPrefix: 'exact_refresh_',
        safetyMarginSeconds: $safetyMargin,
        lockWaitSeconds: $lockWait,
        lockTtlSeconds: 10,
    );

    return [$authenticator, $credentials, $store];
}

function makeExactConnector(string $division = '4471372'): ExactConnector
{
    $authenticator = new OAuthAuthenticator(
        credentials: exactCreds(),
        tokenStore: new RecordingTokenStore(),
        authConnector: new AuthConnector(baseUrl: 'https://start.exactonline.nl'),
        cacheFactory: app(CacheFactory::class),
    );

    return new ExactConnector(
        apiBaseUrl: 'https://start.exactonline.nl',
        division: $division,
        authenticator: $authenticator,
    );
}

/**
 * @param  array<string, string>  $headers  Extra response-headers (bv. X-RateLimit-*).
 */
function fakeExactResponse(
    int $status,
    string $body = '{}',
    ?string $retryAfter = null,
    string $url = 'https://start.exactonline.nl/api/v1/4471372/crm/Accounts',
    array $headers = [],
): Response {
    $pendingRequest = test()->createMock(PendingRequest::class);
    $pendingRequest->method('getUrl')->willReturn($url);

    if (null !== $retryAfter) {
        $headers['Retry-After'] = $retryAfter;
    }

    $response = test()->createMock(Response::class);
    $response->method('status')->willReturn($status);
    $response->method('body')->willReturn($body);
    $response->method('header')->willReturnCallback(
        static fn (string $name) => $headers[$name] ?? null,
    );
    $response->method('getPendingRequest')->willReturn($pendingRequest);

    return $response;
}
