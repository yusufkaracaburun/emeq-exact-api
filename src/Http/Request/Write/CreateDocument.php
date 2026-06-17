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
 * boeking. De respons-`d.ID` haal je op via OData\Envelope::firstId().
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
        ], static fn (mixed $v): bool => null !== $v);
    }
}
