<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Http\Middleware;

use Closure;
use Emeq\ExactApi\Webhooks\ExactWebhookSignature;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifieert de HMAC-`HashCode` op een inbound Exact-webhook.
 *
 * Out-of-the-box gedrag (geen host-state vereist):
 *  - missing secret-config        → 500 + lege body
 *  - lege body (validatie-ping)   → $next($request) — Exact stuurt bij subscribe
 *                                   direct een lege-body-POST die 200/201 MOET
 *                                   krijgen, anders faalt de subscription.
 *  - geen/ongeldige HashCode      → 401 + lege body
 *  - geldige HashCode             → $next($request)
 *
 * De secret is **app-breed** (één per Exact-app), niet per-Connection — een
 * gedocumenteerde uitzondering op de Hub-invariant "per-Connection webhook-secrets"
 * (spiegelt de gedeelde-app-keuze). Host-apps die audit-rows op fail-paths willen
 * schrijven plaatsen een eigen middleware vóór deze; deze blijft Hub-agnostisch.
 *
 * Auto-geregistreerd onder alias `verify.exact.signature` via
 * ExactServiceProvider::packageBooted().
 */
final class VerifyExactSignature
{
    public function __construct(private readonly Repository $config)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $secret = $this->config->get('exact.webhook.secret');

        if ( ! is_string($secret) || '' === $secret) {
            return response('', 500);
        }

        $rawBody = $request->getContent();

        // Validatie-ping: lege body, geen signature → doorlaten zodat de controller
        // 200/201 kan antwoorden en de subscription valideert.
        if ('' === mb_trim($rawBody)) {
            return $next($request);
        }

        $algo = (string) $this->config->get('exact.webhook.signature_algo', 'sha256');

        return ExactWebhookSignature::verify($rawBody, $secret, $algo)
            ? $next($request)
            : response('', 401);
    }
}
