<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Write;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Memoriaal-boeking — `POST generaljournalentry/GeneralJournalEntries`.
 *
 * Afwijkend van sales/purchase: het dagboek-veld heet `JournalCode` (niet
 * `Journal`) en het regelbedrag `AmountDC` (niet `AmountFC`). Geen relatie/EntryDate.
 * De caller levert al-geresolvede waarden in een neutrale vorm.
 */
final class CreateGeneralJournalEntry extends BaseRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  list<array{description?: string|null, amount: int|float, vatCode?: string|null, glAccount?: string|null}>  $lines
     */
    public function __construct(
        private readonly string $journalCode,
        private readonly ?string $description,
        private readonly array $lines,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/generaljournalentry/GeneralJournalEntries';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'JournalCode'              => $this->journalCode,
            'Description'              => $this->description,
            'GeneralJournalEntryLines' => array_map(
                static fn (array $line): array => array_filter([
                    'Description' => $line['description'] ?? null,
                    'AmountDC'    => $line['amount'],
                    'VATCode'     => $line['vatCode'] ?? null,
                    'GLAccount'   => $line['glAccount'] ?? null,
                ], static fn (mixed $v): bool => null !== $v),
                $this->lines,
            ),
        ];
    }
}
