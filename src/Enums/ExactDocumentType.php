<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Enums;

/**
 * Exact `documents/DocumentTypes`-id's die de SDK voor bijlagen gebruikt.
 *
 * De magische int-waarden zijn Exact-wire (bezit hier, niet in de caller). De
 * caller kiest semantisch welk type bij een boeking hoort en levert `->value`.
 * Alleen `DocumentIsCreatable=true`-types die via de API een Document mogen maken.
 */
enum ExactDocumentType: int
{
    case SalesInvoice = 10;
    case PurchaseInvoice = 20;
    case Miscellaneous = 55;
}
