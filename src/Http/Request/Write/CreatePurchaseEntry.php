<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Write;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Inkoop-boeking in het inkoopdagboek — `POST purchaseentry/PurchaseEntries`.
 *
 * Bezit de Exact-veldnamen (`Supplier`/`Journal`/`PurchaseEntryLines`/`AmountFC`);
 * de caller levert al-geresolvede waarden in een neutrale vorm.
 */
final class CreatePurchaseEntry extends BaseRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  list<array{description?: string|null, amount: int|float, vatCode?: string|null, glAccount?: string|null}>  $lines
     */
    public function __construct(
        private readonly string $supplier,
        private readonly string $entryDate,
        private readonly string $journal,
        private readonly ?string $description,
        private readonly array $lines,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/purchaseentry/PurchaseEntries';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'Supplier'           => $this->supplier,
            'EntryDate'          => $this->entryDate,
            'Journal'            => $this->journal,
            'Description'        => $this->description,
            'PurchaseEntryLines' => array_map(
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
