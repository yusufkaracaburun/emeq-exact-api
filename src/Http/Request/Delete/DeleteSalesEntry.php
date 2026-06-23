<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Delete;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Verkoop-boeking verwijderen — `DELETE salesentry/SalesEntries(guid'{id}')`.
 *
 * Key = de `EntryID` (GUID) van de boeking. Exact weigert de delete als de entry
 * vergrendeld/gerapporteerd is; de caller behandelt dat als een nette fout.
 */
final class DeleteSalesEntry extends BaseRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(private readonly string $entryId)
    {
    }

    public function resolveEndpoint(): string
    {
        return "/salesentry/SalesEntries(guid'{$this->entryId}')";
    }
}
