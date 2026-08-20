<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * De ingelogde gebruiker — `GET current/Me`.
 *
 * Anders dan de resource-requests is deze niet division-relatief: stuur 'm via
 * `Exact::connector('current')`, want de division is hier juist wat je nog niet
 * weet. `CurrentDivision` uit de respons is de administratie die de gebruiker
 * open had staan tijdens de consent; `UserID` identificeert de gebruiker zelf.
 */
final class GetMe extends BaseRequest
{
    protected Method $method = Method::GET;

    /**
     * @param  array<string, scalar|null>  $queryParams
     */
    public function __construct(private readonly array $queryParams = [])
    {
    }

    public function resolveEndpoint(): string
    {
        return '/Me';
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParams;
    }
}
