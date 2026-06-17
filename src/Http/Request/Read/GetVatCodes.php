<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * BTW-codes — `GET vat/VATCodes` (division-relatief).
 *
 * Bezit het OData-pad; de OData-query komt van de caller en gaat ongewijzigd door.
 */
final class GetVatCodes extends BaseRequest
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
        return '/vat/VATCodes';
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParams;
    }
}
