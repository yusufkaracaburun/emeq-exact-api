<?php

declare(strict_types=1);

use Emeq\ExactApi\Exceptions\AuthenticationException;
use Emeq\ExactApi\Exceptions\ExactException;
use Emeq\ExactApi\Exceptions\NotFoundException;
use Emeq\ExactApi\Exceptions\RateLimitException;
use Emeq\ExactApi\Exceptions\ServerException;
use Emeq\ExactApi\Exceptions\ValidationException;

it('NotFoundException includes the URL', function (): void {
    $e = NotFoundException::forUrl('https://start.exactonline.nl/api/v1/4471372/crm/Accounts/x');

    expect($e)->toBeInstanceOf(ExactException::class)
        ->and($e->getMessage())->toContain('/crm/Accounts/x')
        ->and($e->getMessage())->toContain('404');
});

it('RateLimitException captures retry-after seconds', function (): void {
    $e = RateLimitException::fromBody('too many', 5);

    expect($e->retryAfterSeconds)->toBe(5)
        ->and($e->getMessage())->toContain('retry after 5s');
});

it('RateLimitException leaves retryAfter null when absent', function (): void {
    $e = RateLimitException::fromBody('throttled', null);

    expect($e->retryAfterSeconds)->toBeNull()
        ->and($e->getMessage())->not->toContain('retry after');
});

it('ServerException reports the HTTP status', function (int $status): void {
    expect(ServerException::fromResponse($status, 'down')->getMessage())->toContain("HTTP {$status}");
})->with([500, 502, 503, 504]);

it('ValidationException extracts the OData error message', function (): void {
    $e = ValidationException::fromBody('{"error":{"message":{"value":"Veld X ontbreekt"}}}');

    expect($e)->toBeInstanceOf(ExactException::class)
        ->and($e->getMessage())->toContain('Veld X ontbreekt')
        ->and($e->rawBody)->toContain('Veld X ontbreekt');
});

it('truncates runaway bodies', function (): void {
    $huge = str_repeat('payload-', 200);

    expect(mb_strlen(ServerException::fromResponse(503, $huge)->getMessage()))->toBeLessThan(800);
});

it('AuthenticationException keeps the secret out and includes a fingerprint', function (): void {
    $e = AuthenticationException::refreshFailed(400, '{"error":"invalid_grant"}', hash('sha256', 'secret-value'));

    expect($e->getMessage())->toContain('fp:')
        ->and($e->getMessage())->toContain('HTTP 400')
        ->and($e->getMessage())->not->toContain('secret-value');
});
