<?php

declare(strict_types=1);

use Emeq\ExactApi\Auth\CacheTokenStore;
use Emeq\ExactApi\Auth\OAuthAuthenticator;
use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Exceptions\AuthenticationException;
use Emeq\ExactApi\Exceptions\NotFoundException;
use Emeq\ExactApi\Exceptions\RateLimitException;
use Emeq\ExactApi\Exceptions\ServerException;
use Emeq\ExactApi\Exceptions\ValidationException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\PendingRequest;

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

it('maps HTTP 401 and 403 to AuthenticationException', function (int $status): void {
    $exception = makeExactConnector()->getRequestException(fakeExactResponse($status, 'nope'), null);

    expect($exception)->toBeInstanceOf(AuthenticationException::class)
        ->and($exception->getMessage())->toContain("HTTP {$status}");
})->with([401, 403]);

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

it('maps transient 5xx to ServerException', function (int $status): void {
    $exception = makeExactConnector()->getRequestException(fakeExactResponse($status, 'boom'), null);

    expect($exception)->toBeInstanceOf(ServerException::class)
        ->and($exception->getMessage())->toContain("HTTP {$status}");
})->with([500, 502, 503, 504]);

it('returns null for unmapped 2xx/3xx', function (int $status): void {
    expect(makeExactConnector()->getRequestException(fakeExactResponse($status, ''), null))->toBeNull();
})->with([204, 301]);

it('handleRetry true for FatalRequestException', function (): void {
    $fatal = new FatalRequestException(new RuntimeException('refused'), test()->createMock(PendingRequest::class));

    expect(makeExactConnector()->handleRetry($fatal, test()->createMock(Saloon\Http\Request::class)))->toBeTrue();
});

it('handleRetry true for retryable statuses', function (int $status): void {
    $exception = new RequestException(fakeExactResponse($status, 'retry'), message: 'stub');

    expect(makeExactConnector()->handleRetry($exception, test()->createMock(Saloon\Http\Request::class)))->toBeTrue();
})->with([429, 500, 502, 503, 504]);

it('handleRetry false for non-retryable 4xx', function (int $status): void {
    $exception = new RequestException(fakeExactResponse($status, 'no'), message: 'stub');

    expect(makeExactConnector()->handleRetry($exception, test()->createMock(Saloon\Http\Request::class)))->toBeFalse();
})->with([400, 401, 404]);
