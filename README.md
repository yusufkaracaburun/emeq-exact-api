# emeq/exact-api

Thin Laravel SDK voor de **Exact Online** REST API. HTTP-laag, OAuth2-auth en
DTOs — geen host-domeinmodellen. Onderdeel van het Emeq integration-platform,
naast `emeq/snelstart-api` en `emeq/mollie-api`.

## Kern

- **OAuth2 `authorization_code`** (Seamless connection) met **roterende,
  single-use refresh-tokens**.
- **Reactief refreshen**: Exact weigert een refresh zolang de access-token nog
  geldig is (`"Rate limit exceeded: access_token not expired"`), dus refreshen
  gebeurt pas ná expiry (access-TTL = 600s). Géén proactieve safety-margin.
- **Token-persistence-seam** (`TokenStore`): de SDK bezit géén token-opslag — de
  host (Hub) implementeert het contract en persisteert het geroteerde
  refresh-token atomair. Per-connection lock serialiseert concurrent refreshes.
- **Division** in het pad: `/api/v1/{division}/...`.

## Status

Foundation — auth + HTTP + DTOs. Resource-wrapping leeft in de Hub.
