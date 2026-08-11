<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Bank-dagafschriften — `GET financialtransaction/BankEntries` (division-relatief).
 *
 * De header draagt dagboek, periode en het open-/sluitsaldo; de mutaties zelf zitten
 * in de `BankEntryLines`-collectie (`$expand=BankEntryLines`).
 *
 * Dit is de resource waar het webhook-topic `BankEntries` over notificeert — zonder
 * deze read kan een ontvanger van zo'n notificatie niets ophalen.
 */
final class GetBankEntries extends BaseRequest
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
        return '/financialtransaction/BankEntries';
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParams;
    }
}
