<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request;

use Saloon\Http\Request;

/**
 * Gedeelde basis voor elke Exact resource-API request. Marker-class — concrete
 * resource-requests komen later; de Hub gebruikt voorlopig RawExactRequest.
 */
abstract class BaseRequest extends Request
{
}
