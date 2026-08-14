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
 *
 * Draagt daarnaast de adresvelden, zodat een relatie die eerder met alleen een naam
 * is aangemaakt alsnog een volledige kaart krijgt. `Country` is de ISO-landcode.
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
        private readonly ?string $vatNumber = null,
        private readonly ?string $chamberOfCommerce = null,
        private readonly ?string $addressLine1 = null,
        private readonly ?string $addressLine2 = null,
        private readonly ?string $postcode = null,
        private readonly ?string $city = null,
        private readonly ?string $state = null,
        private readonly ?string $country = null,
        private readonly ?string $email = null,
        private readonly ?string $phone = null,
        private readonly ?string $website = null,
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
            'Status'            => $this->status,
            'IsSales'           => $this->isSales,
            'IsSupplier'        => $this->isSupplier,
            'VATNumber'         => $this->vatNumber,
            'ChamberOfCommerce' => $this->chamberOfCommerce,
            'AddressLine1'      => $this->addressLine1,
            'AddressLine2'      => $this->addressLine2,
            'Postcode'          => $this->postcode,
            'City'              => $this->city,
            'State'             => $this->state,
            'Country'           => $this->country,
            'Email'             => $this->email,
            'Phone'             => $this->phone,
            'Website'           => $this->website,
        ], static fn (mixed $v): bool => null !== $v);
    }
}
