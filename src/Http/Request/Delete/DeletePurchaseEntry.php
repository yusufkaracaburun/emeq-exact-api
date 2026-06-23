<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Delete;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Inkoop-boeking verwijderen — `DELETE purchaseentry/PurchaseEntries(guid'{id}')`.
 *
 * Key = de `EntryID` (GUID) van de boeking. Exact weigert de delete als de entry
 * vergrendeld/gerapporteerd is; de caller behandelt dat als een nette fout.
 */
final class DeletePurchaseEntry extends BaseRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(private readonly string $entryId)
    {
    }

    public function resolveEndpoint(): string
    {
        return "/purchaseentry/PurchaseEntries(guid'{$this->entryId}')";
    }
}
