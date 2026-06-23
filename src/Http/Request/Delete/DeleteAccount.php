<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Delete;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Relatie (debiteur/crediteur) verwijderen — `DELETE crm/Accounts(guid'{id}')`.
 *
 * Key = de `ID` (GUID) van de relatie. Exact weigert de delete als de relatie nog
 * transacties (boekingen) heeft; verwijder eerst de entries. De caller behandelt
 * een weigering als een nette fout.
 */
final class DeleteAccount extends BaseRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(private readonly string $id)
    {
    }

    public function resolveEndpoint(): string
    {
        return "/crm/Accounts(guid'{$this->id}')";
    }
}
