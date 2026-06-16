<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Exceptions;

/**
 * HTTP 429. Exact hanteert minutely + daily limits (X-RateLimit-* headers).
 * `retryAfterSeconds` is null als er geen Retry-After meegestuurd is.
 */
final class RateLimitException extends ExactException
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message);
    }

    public static function fromBody(string $body, ?int $retryAfterSeconds): self
    {
        return new self(
            message: 'Exact API gaf HTTP 429 (rate limited)' . (null !== $retryAfterSeconds ? ', retry after ' . $retryAfterSeconds . 's' : '') . '. Body: ' . self::truncate($body),
            retryAfterSeconds: $retryAfterSeconds,
        );
    }

    private static function truncate(string $body, int $max = 500): string
    {
        return mb_strlen($body) > $max ? mb_substr($body, 0, $max) . '…' : $body;
    }
}
