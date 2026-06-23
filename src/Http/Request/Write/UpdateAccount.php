<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Write;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Relatie (debiteur/crediteur) bijwerken — `PUT crm/Accounts(guid'{id}')`.
 *
 * Promoveert een bestaande relatie naar een extra rol: een debiteur die óók
 * leverancier wordt krijgt `IsSupplier=true`, een crediteur die óók klant wordt
 * `IsSales=true` (+ `Status='C'`). Exact-relaties mogen beide rollen tegelijk
 * dragen. Partial body — alleen niet-null velden gaan mee; Exact antwoordt 204.
 */
final class UpdateAccount extends BaseRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly string $id,
        private readonly ?string $status = null,
        private readonly ?bool $isSales = null,
        private readonly ?bool $isSupplier = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/crm/Accounts(guid'{$this->id}')";
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter([
            'Status'     => $this->status,
            'IsSales'    => $this->isSales,
            'IsSupplier' => $this->isSupplier,
        ], static fn (mixed $v): bool => null !== $v);
    }
}
