# Changelog

Alle noemenswaardige wijzigingen aan `emeq/exact-api`. Volgt [Keep a Changelog](https://keepachangelog.com/nl/1.1.0/) en [Semantic Versioning](https://semver.org/lang/nl/).

## [0.6.0] - 2026-08-14

### Added
- `Http\Request\Write\CreateAccount` en `Write\UpdateAccount` dragen de volledige relatiekaart: `ChamberOfCommerce`, `AddressLine1`, `AddressLine2`, `Postcode`, `City`, `State`, `Country` (ISO-landcode), `Email`, `Phone`, `Website` — en `VATNumber` ook op `UpdateAccount`. Allemaal optioneel en ze vallen uit de body zodra ze null zijn, dus bestaande aanroepen sturen dezelfde body als voorheen. Veldnamen geverifieerd tegen een echte administratie.

## [0.5.0] - 2026-08-14

### Added
- `Http\Request\Read\GetDocuments` — `GET documents/Documents`. Tegenhanger van `Write\CreateDocument`; de OData-query (`$filter`/`$select`/`$skiptoken`) komt ongewijzigd van de caller, filteren op relatie (`Account`) of aanmaakdatum (`Created`) is caller-verantwoordelijkheid.
- `Http\Request\Delete\DeleteDocument` — `DELETE documents/Documents(guid'{id}')`. Ruimt het document op dat een relatie blokkeert (`Kan niet verwijderen: Relatie - Gebruikt in: Documenten`).

## [0.2.13] - 2026-06-23

### Added
- `Http\Request\Write\UpdateAccount` — `PUT crm/Accounts(guid'{id}')` om een bestaande relatie naar een extra rol te promoveren: debiteur → óók leverancier (`IsSupplier`), crediteur → óók klant (`IsSales` + `Status='C'`). Partial body (alleen niet-null velden), Exact antwoordt 204. Lost de "Ongeldig: Leverancier (Type)"-weigering op wanneer dezelfde firma zowel klant als leverancier is.

## [0.2.12] en eerder

Zie de git-tags (`git tag -l`) voor de geschiedenis vóór dit changelog werd bijgehouden.

[0.6.0]: https://github.com/yusufkaracaburun/emeq-exact-api/releases/tag/v0.6.0
[0.5.0]: https://github.com/yusufkaracaburun/emeq-exact-api/releases/tag/v0.5.0
[0.2.13]: https://github.com/yusufkaracaburun/emeq-exact-api/releases/tag/v0.2.13
