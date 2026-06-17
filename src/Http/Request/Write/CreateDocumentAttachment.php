<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Write;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Bijlage-inhoud — `POST documents/DocumentAttachments`.
 *
 * Stap B van de 2-staps upload: hangt de base64-bestandsinhoud aan een al
 * aangemaakt Document (`Document` = de GUID uit CreateDocument). Bezit de
 * Exact-veldnamen (`Document`/`FileName`/`Attachment`); de caller levert de
 * al-geëncodeerde base64-string.
 */
final class CreateDocumentAttachment extends BaseRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $document,
        private readonly string $fileName,
        private readonly string $attachment,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/documents/DocumentAttachments';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'Document'   => $this->document,
            'FileName'   => $this->fileName,
            'Attachment' => $this->attachment,
        ];
    }
}
