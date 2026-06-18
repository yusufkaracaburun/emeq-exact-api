# Exact Online — Webhooks (subscribe + inbound HashCode-verify)

Geverifieerd contract voor de webhook-laag van `emeq/exact-api`. Bron: door de
user geplakte Exact-spec ("Getting started with Webhooks" + "Exact Online
Webhooks"), validatiesessie 2026-06-17, aangevuld met Exact's officiële
KB-artikel `All-All-DNO-Content-webhooksc`.

> **Laag-grens.** Deze SDK bezit alleen het *protocol*: de subscription-requests,
> de HashCode-HMAC en de verify-middleware. Routing, division→Connection-resolutie,
> audit, fan-out en lifecycle leven in de Hub.

## Subscriptions (division-scoped, OData)

Endpoint-base: `{api_base_url}/api/v1/{division}` (de `ExactConnector`).

| Actie | Methode + pad | Class |
|---|---|---|
| Aanmaken | `POST /webhooks/WebhookSubscriptions` | `Http\Request\Write\CreateWebhookSubscription` |
| Lijst | `GET /webhooks/WebhookSubscriptions` | `Http\Request\Read\ListWebhookSubscriptions` |
| Topics | `GET /webhooks/WebhookTopics` | `Http\Request\Read\ListWebhookTopics` |
| Opzeggen | `DELETE /webhooks/WebhookSubscriptions(guid'{id}')` | `Http\Request\Delete\DeleteWebhookSubscription` |

### Create-body

```json
{ "Topic": "FinancialTransactions", "CallbackURL": "https://…/webhooks/exact", "IsInstant": true, "Description": "…" }
```

- `Topic` + `CallbackURL` **verplicht**. `IsInstant` + `Description` optioneel —
  de SDK stuurt ze alleen mee als de caller ze zet.
- **CallbackURL-constraint**: zelfde domein als de geregistreerde OAuth-RedirectURI,
  en HTTPS. → het Hub-callback-domein == het OAuth-redirect-domein.
- **Validatie-handshake**: direct na create POST't Exact een **lege body** naar de
  CallbackURL. De handler MOET **200/201** geven, anders faalt de subscription.
- **Duplicate**: een tweede subscribe op hetzelfde (topic, division) door een andere
  user van dezelfde klant geeft **HTTP 500 `'Data already exists'`**. Idempotent
  afhandelen, geen harde fout.
- **Topics** (bevestig de exacte strings via `ListWebhookTopics`): o.a.
  `FinancialTransactions`, `Documents`, `Accounts`, `Items`, `StockPositions`.

## Inbound notificatie

```json
{
  "Content": {
    "Topic": "FinancialTransactions",
    "Action": "UPDATE",
    "Division": 4471372,
    "Key": "…guid…",
    "ExactOnlineEndpoint": "/api/v1/4471372/…",
    "EventCreatedOn": "/Date(…)/"
  },
  "HashCode": "base64-hmac-sha256"
}
```

- `Action` = `UPDATE` | `DELETE` (DELETE alleen bij volledig verwijderd record).
- De handler MOET **200/201** retourneren, anders **10 retries** over ~34u
  (delay `2^n` minuten). → in `routes/webhooks.php` `throttle:api` strippen.
- Data ná de notificatie ophalen via `Content.ExactOnlineEndpoint` met het
  **token van de Connection voor die Division** (Exact doet de security-checks).

## HashCode (HMAC-verificatie)

- **Algoritme**: `HashCode = base64( HMAC-SHA256( ContentJson, AppWebhookSecret ) )`.
- **Over welke bytes**: de **letterlijke JSON van de `Content`-node, inclusief de
  buitenste accolades**, exact zoals ontvangen. Daarom de raw `Content`-substring
  uit de body extraheren — *niet* `json_decode`→re-encode (herordening/whitespace
  breekt de HMAC). `ExactWebhookSignature::extractContentJson()` balanceert de
  accolades byte-level.
- **Secret**: **app-breed** (één Webhook-secret per Exact-app, niet per-Connection).
  Gedocumenteerde uitzondering op de Hub-invariant "per-Connection webhook-secrets"
  — spiegelt de gedeelde-app-keuze. Leeft in `ExactSettings::$webhook_secret`
  (encrypted), gehydrateerd naar `config('exact.webhook.secret')`.
- **Compare**: constant-time (`hash_equals`).

### ⚠️ Live te bevestigen (encoding)

De **base64**-encoding volgt Exact's officiële .NET-sample
(`Convert.ToBase64String(HMACSHA256(...))`). De user-spec noteerde "40-byte
hashcode", wat niet rijmt met SHA256 (32 bytes → 44 base64-chars). Bevestig het
wire-formaat (base64 vs hex, lengte, padding) tegen een **echte** Exact-ping op
`hub-dev.emeq.nl` voordat de subscription productie-gebruikt wordt. Bij afwijking:
één-regel-aanpassing in `ExactWebhookSignature::sign()`.

## Middleware

`Http\Middleware\VerifyExactSignature` — auto-alias `verify.exact.signature`:

| Situatie | Respons |
|---|---|
| Geen secret-config | `500` |
| Lege body (validatie-ping) | `$next()` (controller → 200/201) |
| Geen/ongeldige HashCode | `401` |
| Geldige HashCode | `$next()` |
