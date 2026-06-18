<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Request\Write;

use Emeq\ExactApi\Http\Request\BaseRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Webhook-subscription aanmaken — `POST webhooks/WebhookSubscriptions` (division-relatief).
 *
 * `Topic` + `CallbackURL` zijn verplicht; `IsInstant` (near-realtime vs batched)
 * en `Description` optioneel — alleen meegestuurd als de caller ze zet, zodat de
 * SDK geen niet-geverifieerde velden default invult. De CallbackURL moet hetzelfde
 * domein hebben als de geregistreerde OAuth-RedirectURI en HTTPS zijn (Exact-constraint).
 */
final class CreateWebhookSubscription extends BaseRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $topic,
        private readonly string $callbackUrl,
        private readonly ?bool $isInstant = null,
        private readonly ?string $description = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/webhooks/WebhookSubscriptions';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        $body = [
            'Topic'       => $this->topic,
            'CallbackURL' => $this->callbackUrl,
        ];

        if (null !== $this->isInstant) {
            $body['IsInstant'] = $this->isInstant;
        }

        if (null !== $this->description) {
            $body['Description'] = $this->description;
        }

        return $body;
    }
}
