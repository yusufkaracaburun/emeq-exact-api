<?php

declare(strict_types=1);

use Emeq\ExactApi\Webhooks\ExactWebhookSignature;

const EXACT_WEBHOOK_SECRET = 'super-secret-app-key';

/**
 * Bouwt een realistische inbound body: de Content-substring wordt letterlijk
 * herbruikt voor de HashCode-berekening (zoals Exact zelf zou doen).
 */
function exactWebhookBody(string $contentJson, ?string $hashCode = null): string
{
    $hashCode ??= ExactWebhookSignature::sign($contentJson, EXACT_WEBHOOK_SECRET);

    return '{"Content":' . $contentJson . ',"HashCode":' . json_encode($hashCode) . '}';
}

it('signs the Content node as uppercase hex HMAC-SHA256', function (): void {
    $content = '{"Topic":"GeneralJournalEntries","Action":"Update","Division":4471372}';

    $signed = ExactWebhookSignature::sign($content, EXACT_WEBHOOK_SECRET);

    // Live-geverifieerd: 64-char uppercase hex, GEEN base64 (Exact's "byte array
    // of length 40"-doc is misleidend).
    expect($signed)
        ->toBe(mb_strtoupper(hash_hmac('sha256', $content, EXACT_WEBHOOK_SECRET)))
        ->toMatch('/^[0-9A-F]{64}$/');
});

it('verifies a real-shape uppercase-hex HashCode from Exact', function (): void {
    // Exacte wire-vorm zoals live ontvangen (uppercase hex in het HashCode-veld).
    $content  = '{"Topic":"GeneralJournalEntries","ClientId":"e88e32c0","Division":4471372,"Action":"Update","Key":"fe20f499"}';
    $hashCode = mb_strtoupper(hash_hmac('sha256', $content, EXACT_WEBHOOK_SECRET));
    $body     = '{"Content":' . $content . ',"HashCode":' . json_encode($hashCode) . '}';

    expect(ExactWebhookSignature::verify($body, EXACT_WEBHOOK_SECRET))->toBeTrue();
});

it('verifies a correctly signed notification', function (): void {
    $content = '{"Topic":"FinancialTransactions","Action":"UPDATE","Division":4471372,"Key":"abc"}';

    expect(ExactWebhookSignature::verify(exactWebhookBody($content), EXACT_WEBHOOK_SECRET))->toBeTrue();
});

it('rejects a tampered Content node', function (): void {
    $content  = '{"Topic":"FinancialTransactions","Action":"UPDATE","Division":4471372}';
    $hashCode = ExactWebhookSignature::sign($content, EXACT_WEBHOOK_SECRET);
    $tampered = '{"Topic":"FinancialTransactions","Action":"DELETE","Division":4471372}';

    expect(ExactWebhookSignature::verify(exactWebhookBody($tampered, $hashCode), EXACT_WEBHOOK_SECRET))->toBeFalse();
});

it('rejects when signed with a different secret', function (): void {
    $content  = '{"Topic":"Documents","Action":"UPDATE","Division":1}';
    $hashCode = ExactWebhookSignature::sign($content, 'other-secret');

    expect(ExactWebhookSignature::verify(exactWebhookBody($content, $hashCode), EXACT_WEBHOOK_SECRET))->toBeFalse();
});

it('returns false on an empty secret', function (): void {
    $content = '{"Topic":"Items","Action":"UPDATE","Division":1}';

    expect(ExactWebhookSignature::verify(exactWebhookBody($content), ''))->toBeFalse();
});

it('returns false when Content or HashCode is missing', function (): void {
    expect(ExactWebhookSignature::verify('{"HashCode":"x"}', EXACT_WEBHOOK_SECRET))->toBeFalse()
        ->and(ExactWebhookSignature::verify('{"Content":{"Topic":"x"}}', EXACT_WEBHOOK_SECRET))->toBeFalse()
        ->and(ExactWebhookSignature::verify('', EXACT_WEBHOOK_SECRET))->toBeFalse();
});

it('extracts the literal Content substring including nested braces and escaped quotes', function (): void {
    $content = '{"Topic":"Documents","Action":"UPDATE","Nested":{"a":1,"b":{"c":2}},"Label":"a \"quoted\" }brace{"}';
    $body    = exactWebhookBody($content);

    expect(ExactWebhookSignature::extractContentJson($body))->toBe($content);
});

it('verifies a Content node containing multibyte characters', function (): void {
    // Een Description met é/€ dwingt char- vs byte-index-consistentie af in de extractor.
    $content = '{"Topic":"Documents","Action":"UPDATE","Label":"Factuur café €1,50"}';

    expect(ExactWebhookSignature::verify(exactWebhookBody($content), EXACT_WEBHOOK_SECRET))->toBeTrue()
        ->and(ExactWebhookSignature::extractContentJson(exactWebhookBody($content)))->toBe($content);
});

it('extracts Content even when HashCode appears before it', function (): void {
    $content = '{"Topic":"Accounts","Action":"UPDATE","Division":2}';
    $body    = '{"HashCode":"abc","Content":' . $content . '}';

    expect(ExactWebhookSignature::extractContentJson($body))->toBe($content);
});

it('returns null extracting Content from a body without a Content node', function (): void {
    expect(ExactWebhookSignature::extractContentJson('{"Foo":"bar"}'))->toBeNull();
});
