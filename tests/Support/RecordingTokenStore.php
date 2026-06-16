<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Tests\Support;

use Emeq\ExactApi\Contracts\TokenStore;
use Emeq\ExactApi\Data\AccessToken;
use Emeq\ExactApi\Data\ExactCredentials;

/**
 * In-memory TokenStore die elke put() bewaart — voor rotatie-asserts. get()
 * geeft de laatst geplaatste (of de initiële) bundle terug.
 */
final class RecordingTokenStore implements TokenStore
{
    /** @var list<AccessToken> */
    public array $puts = [];

    private ?AccessToken $current;

    public function __construct(?AccessToken $initial = null)
    {
        $this->current = $initial;
    }

    public function get(ExactCredentials $credentials): ?AccessToken
    {
        return $this->current;
    }

    public function put(ExactCredentials $credentials, AccessToken $token): void
    {
        $this->puts[]  = $token;
        $this->current = $token;
    }
}
