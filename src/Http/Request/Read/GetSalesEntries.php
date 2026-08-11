<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Verkoopboekingen — `GET salesentry/SalesEntries` (division-relatief).
 *
 * Symmetrisch met {@see \Emeq\ExactApi\Http\Request\Write\CreateSalesEntry}: wat via
 * die request geboekt is, komt hier terug. Bewust niet `salesinvoice/SalesInvoices` —
 * dat is de item-based factuurmodule, en daar verschijnen GL-based boekingen niet in.
 *
 * Bezit het OData-pad; de query (`$select`/`$filter`/`$top`/`$skiptoken`/`$expand`)
 * komt van de caller en gaat ongewijzigd door. Voor de regels: `$expand=SalesEntryLines`.
 */
final class GetSalesEntries extends BaseRequest
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
        return '/salesentry/SalesEntries';
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParams;
    }
}
