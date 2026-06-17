<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Write;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Verkoop-boeking in het verkoopdagboek — `POST salesentry/SalesEntries`.
 *
 * GL-based (géén item-based SalesInvoice): accounting-sync zet boekhoud-data in
 * Exact, het invoicen gebeurt bij de Consumer. Bezit de Exact-veldnamen
 * (`Customer`/`Journal`/`SalesEntryLines`/`AmountFC`); de caller levert
 * al-geresolvede waarden (relatie-GUID, journaal-code, regels) in een neutrale vorm.
 */
final class CreateSalesEntry extends BaseRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  list<array{description?: string|null, amount: int|float, vatCode?: string|null, glAccount?: string|null}>  $lines
     */
    public function __construct(
        private readonly string $customer,
        private readonly string $entryDate,
        private readonly string $journal,
        private readonly ?string $description,
        private readonly array $lines,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/salesentry/SalesEntries';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'Customer'        => $this->customer,
            'EntryDate'       => $this->entryDate,
            'Journal'         => $this->journal,
            'Description'     => $this->description,
            'SalesEntryLines' => array_map(
                static fn (array $line): array => array_filter([
                    'Description' => $line['description'] ?? null,
                    'AmountFC'    => $line['amount'],
                    'VATCode'     => $line['vatCode'] ?? null,
                    'GLAccount'   => $line['glAccount'] ?? null,
                ], static fn (mixed $v): bool => null !== $v),
                $this->lines,
            ),
        ];
    }
}
