<?php

declare(strict_types=1);

use Emeq\ExactApi\Http\Middleware\VerifyExactSignature;
use Emeq\ExactApi\Webhooks\ExactWebhookSignature;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

function exactSignatureRequest(string $rawBody): Request
{
    return Request::create('/webhooks/exact', 'POST', [], [], [], [], $rawBody);
}

function runExactMiddleware(Request $request): Response
{
    return (new VerifyExactSignature(app('config')))->handle(
        $request,
        static fn (): Response => response('passed', 200),
    );
}

function signedExactBody(string $content, string $secret): string
{
    $hashCode = ExactWebhookSignature::sign($content, $secret);

    return '{"Content":' . $content . ',"HashCode":' . json_encode($hashCode) . '}';
}

it('returns 500 when no webhook secret is configured', function (): void {
    config()->set('exact.webhook.secret', null);

    expect(runExactMiddleware(exactSignatureRequest('{"Content":{},"HashCode":"x"}'))->getStatusCode())->toBe(500);
});

it('passes an empty-body validation ping through to the handler', function (): void {
    config()->set('exact.webhook.secret', 'app-secret');

    $response = runExactMiddleware(exactSignatureRequest(''));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('passed');
});

it('passes a correctly signed notification through to the handler', function (): void {
    config()->set('exact.webhook.secret', 'app-secret');
    $body = signedExactBody('{"Topic":"FinancialTransactions","Action":"UPDATE","Division":4471372}', 'app-secret');

    $response = runExactMiddleware(exactSignatureRequest($body));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('passed');
});

it('returns 401 for an invalid HashCode', function (): void {
    config()->set('exact.webhook.secret', 'app-secret');
    $body = signedExactBody('{"Topic":"Documents","Action":"UPDATE","Division":1}', 'WRONG-secret');

    expect(runExactMiddleware(exactSignatureRequest($body))->getStatusCode())->toBe(401);
});
