<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Read;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Beschikbare webhook-topics — `GET webhooks/WebhookTopics` (division-relatief).
 *
 * Bron-van-waarheid voor de exacte topic-strings (`FinancialTransactions`,
 * `Documents`, `Accounts`, `Items`, …) die `CreateWebhookSubscription` verwacht.
 */
final class ListWebhookTopics extends BaseRequest
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/webhooks/WebhookTopics';
    }
}
