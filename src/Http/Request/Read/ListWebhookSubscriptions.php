<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Actieve webhook-subscriptions — `GET webhooks/WebhookSubscriptions` (division-relatief).
 *
 * Gebruikt om idempotent te (her)registreren: lees de bestaande subscriptions en
 * maak alleen ontbrekende topics aan. De OData-query komt ongewijzigd van de caller.
 */
final class ListWebhookSubscriptions extends BaseRequest
{
    protected Method $method = Method::GET;

    /**
     * @param  array<string, scalar|null>  $queryParams
     */
    public function __construct(private readonly array $queryParams = [])
    {
    }

    public function resolveEndpoint(): string
    {
        return '/webhooks/WebhookSubscriptions';
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParams;
    }
}
