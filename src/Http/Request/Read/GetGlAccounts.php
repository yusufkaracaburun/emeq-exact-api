<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Grootboekrekeningen — `GET financial/GLAccounts` (division-relatief).
 *
 * Bezit het OData-pad; de OData-query (`$select`/`$filter`/`$top`/…) komt van de
 * caller en wordt ongewijzigd doorgegeven.
 */
final class GetGlAccounts extends BaseRequest
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
        return '/financial/GLAccounts';
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParams;
    }
}
