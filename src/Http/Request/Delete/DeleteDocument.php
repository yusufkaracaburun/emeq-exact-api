<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Delete;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Document verwijderen — `DELETE documents/Documents(guid'{id}')`.
 *
 * Key = de `ID` (GUID) van het document. Exact weigert het verwijderen van een relatie
 * (`crm/Accounts`) zolang die relatie nog gekoppelde documenten heeft; deze delete ruimt
 * dat blokkerende document op. De caller behandelt een weigering als een nette fout.
 */
final class DeleteDocument extends BaseRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(private readonly string $id)
    {
    }

    public function resolveEndpoint(): string
    {
        return "/documents/Documents(guid'{$this->id}')";
    }
}
