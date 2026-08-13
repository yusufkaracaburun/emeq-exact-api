<?php

declare(strict_types=1);

use Emeq\ExactApi\Auth\AuthConnector;
use Emeq\ExactApi\Auth\OAuthAuthenticator;

it('holds the refresh lock longer than the auth request can take', function (): void {
    $lockTtl = (new ReflectionParameter([OAuthAuthenticator::class, '__construct'], 'lockTtlSeconds'))
        ->getDefaultValue();

    $authTimeout = (new ReflectionParameter([AuthConnector::class, '__construct'], 'timeoutSeconds'))
        ->getDefaultValue();

    expect($lockTtl)->toBeGreaterThan($authTimeout);
});

it('gives up waiting for the lock before it can expire under the holder', function (): void {
    $lockWait = (new ReflectionParameter([OAuthAuthenticator::class, '__construct'], 'lockWaitSeconds'))
        ->getDefaultValue();

    $lockTtl = (new ReflectionParameter([OAuthAuthenticator::class, '__construct'], 'lockTtlSeconds'))
        ->getDefaultValue();

    expect($lockWait)->toBeLessThan($lockTtl);
});
