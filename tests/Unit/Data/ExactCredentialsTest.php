<?php

declare(strict_types=1);

use Emeq\ExactApi\Data\ExactCredentials;

it('fingerprint differs per connectionRef (per-connection, not per-app)', function (): void {
    $a = new ExactCredentials('cid', 'sec', 'https://cb', 'conn-1');
    $b = new ExactCredentials('cid', 'sec', 'https://cb', 'conn-2');

    expect($a->fingerprint())->not->toBe($b->fingerprint());
});

it('fingerprint depends only on clientId + connectionRef', function (): void {
    $a = new ExactCredentials('cid', 'sec', 'https://cb', 'conn-1');
    $b = new ExactCredentials('cid', 'OTHER-secret', 'https://OTHER-cb', 'conn-1');

    expect($a->fingerprint())->toBe($b->fingerprint());
});

it('never leaks the raw secret into the fingerprint', function (): void {
    $credentials = new ExactCredentials('cid', 'super-secret-value', 'https://cb', 'conn-1');

    expect($credentials->fingerprint())->not->toContain('super-secret-value');
});

it('throws on any empty required field', function (string $clientId, string $clientSecret, string $redirectUri, string $connectionRef): void {
    expect(fn () => new ExactCredentials($clientId, $clientSecret, $redirectUri, $connectionRef))
        ->toThrow(InvalidArgumentException::class);
})->with([
    ['', 'sec', 'https://cb', 'conn'],
    ['cid', '', 'https://cb', 'conn'],
    ['cid', 'sec', '', 'conn'],
    ['cid', 'sec', 'https://cb', ''],
]);
