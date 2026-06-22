<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Write;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Relatie (debiteur/crediteur) aanmaken — `POST crm/Accounts`.
 *
 * Lazy auto-create: de Hub roept dit aan wanneer een boeking een party draagt die
 * nog niet als Exact-relatie bestaat. `Name` is het enige verplichte veld (officiële
 * Exact-referentie); `Status='C'` + `IsSales` markeert een debiteur, `IsSupplier`
 * een crediteur. `VATNumber` is de stabiele dedup-sleutel als die er is. De respons-
 * `d.ID` (GUID) haal je op via OData\Envelope::firstId().
 */
final class CreateAccount extends BaseRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $name,
        private readonly ?string $status = null,
        private readonly ?bool $isSales = null,
        private readonly ?bool $isSupplier = null,
        private readonly ?string $vatNumber = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/crm/Accounts';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter([
            'Name'       => $this->name,
            'Status'     => $this->status,
            'IsSales'    => $this->isSales,
            'IsSupplier' => $this->isSupplier,
            'VATNumber'  => $this->vatNumber,
        ], static fn (mixed $v): bool => null !== $v);
    }
}
