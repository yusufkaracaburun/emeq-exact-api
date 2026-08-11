<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Cash-dagafschriften — `GET financialtransaction/CashEntries` (division-relatief).
 *
 * De header draagt dagboek, periode en het open-/sluitsaldo; de mutaties zelf zitten
 * in de `CashEntryLines`-collectie (`$expand=CashEntryLines`).
 *
 * Dit is de resource waar het webhook-topic `CashEntries` over notificeert — zonder
 * deze read kan een ontvanger van zo'n notificatie niets ophalen.
 */
final class GetCashEntries extends BaseRequest
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
        return '/financialtransaction/CashEntries';
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParams;
    }
}
