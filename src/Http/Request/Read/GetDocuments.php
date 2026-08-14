<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Documenten — `GET documents/Documents` (division-relatief).
 *
 * Tegenhanger van {@see \Emeq\ExactApi\Http\Request\Write\CreateDocument}. Bezit
 * het OData-pad; de OData-query (`$select`/`$filter`/`$top`/`$skiptoken`/…) komt van
 * de caller en gaat ongewijzigd door — filteren op relatie (`Account`) of aanmaakdatum
 * (`Created`) is caller-verantwoordelijkheid.
 */
final class GetDocuments extends BaseRequest
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
        return '/documents/Documents';
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParams;
    }
}
