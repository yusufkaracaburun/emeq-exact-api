<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Inkoopboekingen — `GET purchaseentry/PurchaseEntries` (division-relatief).
 *
 * Tegenhanger van {@see \Emeq\ExactApi\Http\Request\Write\CreatePurchaseEntry}.
 * Voor de regels: `$expand=PurchaseEntryLines`.
 */
final class GetPurchaseEntries extends BaseRequest
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
        return '/purchaseentry/PurchaseEntries';
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParams;
    }
}
