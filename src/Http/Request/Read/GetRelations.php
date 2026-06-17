<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Relaties (debiteuren/crediteuren) — `GET crm/Accounts` (division-relatief).
 *
 * Bezit het OData-pad; de OData-query komt van de caller en gaat ongewijzigd door.
 */
final class GetRelations extends BaseRequest
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
        return '/crm/Accounts';
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParams;
    }
}
