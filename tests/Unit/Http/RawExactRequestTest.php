<?php

declare(strict_types=1);

use Emeq\ExactApi\Http\Request\RawExactRequest;
use Saloon\Enums\Method;

it('exposes its method, endpoint and query', function (): void {
    $request = new RawExactRequest(
        method: Method::GET,
        endpoint: '/crm/Accounts',
        query: ['$top' => 5, '$filter' => "Name eq 'Acme'"],
    );

    expect($request->getMethod())->toBe(Method::GET)
        ->and($request->resolveEndpoint())->toBe('/crm/Accounts')
        ->and($request->query()->all())->toBe(['$top' => 5, '$filter' => "Name eq 'Acme'"]);
});

it('supports POST with a JSON body', function (): void {
    $request = new RawExactRequest(method: Method::POST, endpoint: '/crm/Accounts', body: ['Name' => 'Acme BV']);

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->body()->all())->toBe(['Name' => 'Acme BV']);
});

it('treats a null body as no body', function (): void {
    $request = new RawExactRequest(method: Method::GET, endpoint: '/current/Me');

    expect($request->body()->all())->toBe([]);
});

it('passes extra headers through', function (): void {
    $request = new RawExactRequest(method: Method::GET, endpoint: '/crm/Accounts', headers: ['X-Correlation-Id' => 'abc-123']);

    expect($request->headers()->get('X-Correlation-Id'))->toBe('abc-123');
});

it('supports every HTTP method', function (Method $method): void {
    expect((new RawExactRequest(method: $method, endpoint: '/x'))->getMethod())->toBe($method);
})->with([Method::GET, Method::POST, Method::PUT, Method::PATCH, Method::DELETE]);
