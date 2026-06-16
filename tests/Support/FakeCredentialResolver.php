<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Tests\Support;

use Emeq\ExactApi\Contracts\ExactCredentialResolver;
use Emeq\ExactApi\Data\ExactCredentials;

final class FakeCredentialResolver implements ExactCredentialResolver
{
    public function __construct(
        private readonly ExactCredentials $credentials,
    ) {
    }

    public static function with(
        string $clientId = 'test-client-id',
        string $clientSecret = 'test-client-secret',
        string $redirectUri = 'https://hub.emeq.test/v1/oauth/exact/callback',
        string $connectionRef = 'conn-test',
        ?string $scope = null,
    ): self {
        return new self(new ExactCredentials(
            clientId: $clientId,
            clientSecret: $clientSecret,
            redirectUri: $redirectUri,
            connectionRef: $connectionRef,
            scope: $scope,
        ));
    }

    public function resolve(): ExactCredentials
    {
        return $this->credentials;
    }
}
