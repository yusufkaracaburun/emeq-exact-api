<?php

declare(strict_types=1);

use Emeq\ExactApi\Data\AccessToken;

it('maps a token response with a string expires_in', function (): void {
    $token = AccessToken::fromTokenResponse([
        'access_token'  => 'acc',
        'refresh_token' => 'ref',
        'expires_in'    => '600',
    ]);

    expect($token->accessToken)->toBe('acc')
        ->and($token->refreshToken)->toBe('ref')
        ->and($token->isExpired(0))->toBeFalse();
});

it('treats a token within the safety margin as expired', function (): void {
    $now   = new DateTimeImmutable();
    $token = AccessToken::fromTokenResponse(['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => '60'], $now);

    expect($token->isExpired(0, $now))->toBeFalse()
        ->and($token->isExpired(120, $now))->toBeTrue();
});

it('throws on an empty access or refresh token', function (): void {
    expect(fn () => new AccessToken('', 'r', new DateTimeImmutable()))->toThrow(InvalidArgumentException::class);
    expect(fn () => new AccessToken('a', '', new DateTimeImmutable()))->toThrow(InvalidArgumentException::class);
});

it('round-trips through toArray/fromArray including the refresh token', function (): void {
    $token = AccessToken::fromTokenResponse(['access_token' => 'acc', 'refresh_token' => 'ref', 'expires_in' => '600']);
    $back  = AccessToken::fromArray($token->toArray());

    expect($back->accessToken)->toBe('acc')
        ->and($back->refreshToken)->toBe('ref')
        ->and($back->expiresAt->format(DATE_ATOM))->toBe($token->expiresAt->format(DATE_ATOM));
});
