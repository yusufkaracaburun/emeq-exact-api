<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Delete;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Enums\Method;

/**
 * Webhook-subscription opzeggen — `DELETE webhooks/WebhookSubscriptions(guid'{id}')`.
 *
 * Na een OAuth-revoke kan deze call niet meer (token weg); Exact ruimt verweesde
 * subscriptions 's nachts zelf op. De caller (Hub) behandelt een delete-na-revoke
 * daarom niet als harde fout.
 */
final class DeleteWebhookSubscription extends BaseRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(private readonly string $id)
    {
    }

    public function resolveEndpoint(): string
    {
        return "/webhooks/WebhookSubscriptions(guid'{$this->id}')";
    }
}
