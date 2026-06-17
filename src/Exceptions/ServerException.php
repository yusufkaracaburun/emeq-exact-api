<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Exceptions;

/**
 * HTTP 5xx — typisch transient; de ExactConnector retryt 500/502/503/504 al
 * automatisch voordat de exception bovenkomt.
 */
final class ServerException extends ExactException
{
    public function __construct(
        string $message,
        public readonly int $status = 500,
        public readonly string $rawBody = '',
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(int $status, string $body, ?int $retryAfterSeconds = null): self
    {
        return new self(
            sprintf('Exact API gaf HTTP %d. Body: %s', $status, self::truncate($body)),
            $status,
            $body,
            $retryAfterSeconds,
        );
    }

    private static function truncate(string $body, int $max = 500): string
    {
        return mb_strlen($body) > $max ? mb_substr($body, 0, $max) . '…' : $body;
    }
}
