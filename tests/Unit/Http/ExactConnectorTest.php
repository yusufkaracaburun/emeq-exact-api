<?php

declare(strict_types=1);

use Emeq\ExactApi\Auth\CacheTokenStore;
use Emeq\ExactApi\Auth\OAuthAuthenticator;
use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Exceptions\AuthenticationException;
use Emeq\ExactApi\Exceptions\NotFoundException;
use Emeq\ExactApi\Exceptions\RateLimitException;
use Emeq\ExactApi\Exceptions\RequestTooBroadException;
use Emeq\ExactApi\Exceptions\ServerException;
use Emeq\ExactApi\Exceptions\ValidationException;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\PendingRequest;

/** Request-mock met een vaste HTTP-methode voor de retry-tests. */
function requestWithMethod(Method $method): Saloon\Http\Request
{
    $request = test()->createMock(Saloon\Http\Request::class);
    $request->method('getMethod')->willReturn($method);

    return $request;
}

it('resolves the division-scoped base URL', function (): void {
    expect(makeExactConnector('4471372')->resolveBaseUrl())->toBe('https://start.exactonline.nl/api/v1/4471372')
        ->and(makeExactConnector('current')->resolveBaseUrl())->toBe('https://start.exactonline.nl/api/v1/current');
});

it('invokes the authenticator factory', function (): void {
    app()->bind(TokenStore::class, CacheTokenStore::class);

    $authenticator = app('exact.authenticator-factory')(exactCreds());

    expect($authenticator)->toBeInstanceOf(OAuthAuthenticator::class);
});

it('maps HTTP 400 to ValidationException and surfaces the OData message', function (): void {
    $exception = makeExactConnector()->getRequestException(
        fakeExactResponse(400, '{"error":{"message":{"value":"Veld ontbreekt"}}}'),
        null,
    );

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception->getMessage())->toContain('Veld ontbreekt');
});

it('maps HTTP 401 and 403 to AuthenticationException carrying the upstream status', function (int $status): void {
    $exception = makeExactConnector()->getRequestException(fakeExactResponse($status, 'nope'), null);

    expect($exception)->toBeInstanceOf(AuthenticationException::class)
        ->and($exception->getMessage())->toContain("HTTP {$status}")
        ->and($exception->apiStatus)->toBe($status);
})->with([401, 403]);

it('maps HTTP 408 to RequestTooBroadException with the raw body', function (): void {
    $exception = makeExactConnector()->getRequestException(fakeExactResponse(408, 'too broad'), null);

    expect($exception)->toBeInstanceOf(RequestTooBroadException::class)
        ->and($exception->getMessage())->toContain('408')
        ->and($exception->rawBody)->toBe('too broad');
});

it('maps HTTP 404 to NotFoundException with the URL', function (): void {
    $exception = makeExactConnector()->getRequestException(
        fakeExactResponse(404, 'gone', url: 'https://start.exactonline.nl/api/v1/4471372/crm/Accounts/missing'),
        null,
    );

    expect($exception)->toBeInstanceOf(NotFoundException::class)
        ->and($exception->getMessage())->toContain('/crm/Accounts/missing')
        ->and($exception->getMessage())->toContain('404');
});

it('maps HTTP 429 to RateLimitException and parses Retry-After', function (): void {
    $exception = makeExactConnector()->getRequestException(fakeExactResponse(429, 'slow down', retryAfter: '42'), null);

    expect($exception)->toBeInstanceOf(RateLimitException::class)
        ->and($exception->retryAfterSeconds)->toBe(42);
});

it('maps HTTP 429 carrying the X-RateLimit-* quota headers', function (): void {
    $exception = makeExactConnector()->getRequestException(fakeExactResponse(429, 'slow', headers: [
        'X-RateLimit-Remaining'          => '0',
        'X-RateLimit-Reset'              => '1718700000000',
        'X-RateLimit-Minutely-Remaining' => '0',
        'Some-Other-Header'              => 'ignored',
    ]), null);

    expect($exception)->toBeInstanceOf(RateLimitException::class)
        ->and($exception->rateLimitHeaders)->toBe([
            'X-RateLimit-Remaining'          => '0',
            'X-RateLimit-Reset'              => '1718700000000',
            'X-RateLimit-Minutely-Remaining' => '0',
        ]);
});

it('maps transient 5xx to ServerException carrying status, body and Retry-After', function (int $status): void {
    $exception = makeExactConnector()->getRequestException(
        fakeExactResponse($status, '{"error":{"message":{"value":"boom"}}}', retryAfter: '120'),
        null,
    );

    expect($exception)->toBeInstanceOf(ServerException::class)
        ->and($exception->getMessage())->toContain("HTTP {$status}")
        ->and($exception->status)->toBe($status)
        ->and($exception->rawBody)->toContain('boom')
        ->and($exception->retryAfterSeconds)->toBe(120);
})->with([500, 502, 503, 504]);

it('returns null for unmapped 2xx/3xx', function (int $status): void {
    expect(makeExactConnector()->getRequestException(fakeExactResponse($status, ''), null))->toBeNull();
})->with([204, 301]);

it('handleRetry true for FatalRequestException', function (): void {
    $fatal = new FatalRequestException(new RuntimeException('refused'), test()->createMock(PendingRequest::class));

    expect(makeExactConnector()->handleRetry($fatal, requestWithMethod(Method::GET)))->toBeTrue();
});

it('handleRetry true for retryable statuses', function (int $status): void {
    $exception = new RequestException(fakeExactResponse($status, 'retry'), message: 'stub');

    expect(makeExactConnector()->handleRetry($exception, requestWithMethod(Method::GET)))->toBeTrue();
})->with([429, 500, 502, 503, 504]);

it('handleRetry false for non-retryable 4xx', function (int $status): void {
    $exception = new RequestException(fakeExactResponse($status, 'no'), message: 'stub');

    expect(makeExactConnector()->handleRetry($exception, requestWithMethod(Method::GET)))->toBeFalse();
})->with([400, 401, 404, 408]);

it('handleRetry false for a POST on transient 5xx (geen dubbele boeking)', function (int $status): void {
    $exception = new RequestException(fakeExactResponse($status, 'boom'), message: 'stub');

    expect(makeExactConnector()->handleRetry($exception, requestWithMethod(Method::POST)))->toBeFalse();
})->with([500, 502, 503, 504]);

it('handleRetry false for a POST on a fatal connection error', function (): void {
    $fatal = new FatalRequestException(new RuntimeException('refused'), test()->createMock(PendingRequest::class));

    expect(makeExactConnector()->handleRetry($fatal, requestWithMethod(Method::POST)))->toBeFalse();
});

it('handleRetry true for a POST on 429 (rate limit is niet verwerkt)', function (): void {
    $exception = new RequestException(fakeExactResponse(429, 'slow'), message: 'stub');

    expect(makeExactConnector()->handleRetry($exception, requestWithMethod(Method::POST)))->toBeTrue();
});

it('handleRetry true for an idempotent GET on transient 5xx', function (int $status): void {
    $exception = new RequestException(fakeExactResponse($status, 'boom'), message: 'stub');

    expect(makeExactConnector()->handleRetry($exception, requestWithMethod(Method::GET)))->toBeTrue();
})->with([500, 502, 503, 504]);
