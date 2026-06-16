<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Pass-through request voor willekeurige (division-relatieve) Exact-endpoints.
 *
 * ```php
 * $exact->connector('4471372')->send(new RawExactRequest(
 *     method: Method::GET,
 *     endpoint: '/crm/Accounts',
 *     query: ['$top' => 5],
 * ));
 * ```
 *
 * De connector regelt auth, division-base, retry en error-mapping; deze class
 * draagt enkel de request-vorm. `rawX`-prefix omdat Saloon\Http\Request al
 * niet-readonly `$headers`/`$query`/`$body` declareert.
 */
class RawExactRequest extends BaseRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method;

    private readonly string $rawEndpoint;

    /** @var array<string, scalar|null> */
    private readonly array $rawQuery;

    /** @var array<string, mixed>|null */
    private readonly ?array $rawBody;

    /** @var array<string, string> */
    private readonly array $rawHeaders;

    /**
     * @param  array<string, scalar|null>  $query
     * @param  array<string, mixed>|null  $body
     * @param  array<string, string>  $headers
     */
    public function __construct(
        Method $method,
        string $endpoint,
        array $query = [],
        ?array $body = null,
        array $headers = [],
    ) {
        $this->method      = $method;
        $this->rawEndpoint = $endpoint;
        $this->rawQuery    = $query;
        $this->rawBody     = $body;
        $this->rawHeaders  = $headers;
    }

    public function resolveEndpoint(): string
    {
        return $this->rawEndpoint;
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->rawQuery;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->rawBody ?? [];
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return $this->rawHeaders;
    }
}
