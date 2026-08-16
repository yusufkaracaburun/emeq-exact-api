<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Write;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Document-koptekst — `POST documents/Documents`.
 *
 * Stap A van de 2-staps bijlage-upload: maakt het Document waar de feitelijke
 * bijlage (zie CreateDocumentAttachment) aan hangt. Bezit de Exact-veldnamen
 * (`Subject`/`Type`/`Account`/`FinancialTransactionEntryID`); de caller levert
 * al-geresolvede waarden. `Account` (relatie-GUID) laat het document op de
 * relatiekaart verschijnen; `FinancialTransactionEntryID` koppelt het aan de
 * boeking. `DocumentDate` (`Y-m-d`) is de datum van het stuk zelf; laat je 'm weg,
 * dan stempelt Exact de dag van uploaden, en staat een factuur uit mei in het
 * documentenoverzicht onder de dag waarop 'm geboekt werd. De respons-`d.ID` haal
 * je op via OData\Envelope::firstId().
 */
final class CreateDocument extends BaseRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $subject,
        private readonly int $type,
        private readonly ?string $account = null,
        private readonly ?string $financialTransactionEntryId = null,
        private readonly ?string $documentDate = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/documents/Documents';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter([
            'Subject'                     => $this->subject,
            'Type'                        => $this->type,
            'Account'                     => $this->account,
            'FinancialTransactionEntryID' => $this->financialTransactionEntryId,
            'DocumentDate'                => $this->documentDate,
        ], static fn (mixed $v): bool => null !== $v);
    }
}
