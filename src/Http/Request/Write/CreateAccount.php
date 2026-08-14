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
 *
 * De adresvelden zijn optioneel en gaan alleen mee wanneer de aanroeper ze levert:
 * een relatiekaart die de boekhouder kan gebruiken, niet alleen een naam. `Country`
 * is de ISO-landcode (`NL`), niet de landsnaam.
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
        return '/crm/Accounts';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter([
            'Name'              => $this->name,
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
