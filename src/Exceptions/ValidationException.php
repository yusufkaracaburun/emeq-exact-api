<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Exceptions;

/**
 * HTTP 400. Exact geeft fouten als OData-envelope `{"error":{"code":...,"message":{"value":...}}}`.
 * We surfacen de message waar mogelijk en bewaren de volledige body.
 */
final class ValidationException extends ExactException
{
    public function __construct(
        string $message,
        public readonly string $rawBody = '',
    ) {
        parent::__construct($message);
    }

    public static function fromBody(string $body): self
    {
        $detail = self::extractMessage($body);

        $message = null !== $detail
            ? 'Exact API weigerde de request (HTTP 400): ' . $detail
            : 'Exact API gaf HTTP 400. Body: ' . self::truncate($body);

        return new self($message, $body);
    }

    private static function extractMessage(string $body): ?string
    {
        /** @var array{error?: array{message?: array{value?: string}}}|null $decoded */
        $decoded = json_decode($body, true);

        $value = $decoded['error']['message']['value'] ?? null;

        return is_string($value) && '' !== $value ? $value : null;
    }

    private static function truncate(string $body, int $max = 500): string
    {
        return mb_strlen($body) > $max ? mb_substr($body, 0, $max) . '…' : $body;
    }
}
