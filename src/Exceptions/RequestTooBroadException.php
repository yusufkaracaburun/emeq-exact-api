<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Exceptions;

/**
 * HTTP 408 — Exact's "request too broad". Geen klassieke timeout: Exact weigert een
 * query die te veel data zou teruggeven. Niet retrybaar; de consumer moet de
 * $filter/$select verfijnen of een sync-endpoint gebruiken.
 */
final class RequestTooBroadException extends ExactException
{
    public function __construct(
        string $message,
        public readonly string $rawBody = '',
    ) {
        parent::__construct($message);
    }

    public static function fromBody(string $body): self
    {
        return new self(
            'Exact API gaf HTTP 408 (request too broad) — verfijn de query of gebruik de sync-endpoints. Body: ' . self::truncate($body),
            $body,
        );
    }

    private static function truncate(string $body, int $max = 500): string
    {
        return mb_strlen($body) > $max ? mb_substr($body, 0, $max) . '…' : $body;
    }
}
