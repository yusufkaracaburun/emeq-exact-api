# Changelog

Alle noemenswaardige wijzigingen aan `emeq/exact-api`. Volgt [Keep a Changelog](https://keepachangelog.com/nl/1.1.0/) en [Semantic Versioning](https://semver.org/lang/nl/).

## [0.2.13] - 2026-06-23

### Added
- `Http\Request\Write\UpdateAccount` — `PUT crm/Accounts(guid'{id}')` om een bestaande relatie naar een extra rol te promoveren: debiteur → óók leverancier (`IsSupplier`), crediteur → óók klant (`IsSales` + `Status='C'`). Partial body (alleen niet-null velden), Exact antwoordt 204. Lost de "Ongeldig: Leverancier (Type)"-weigering op wanneer dezelfde firma zowel klant als leverancier is.

## [0.2.12] en eerder

Zie de git-tags (`git tag -l`) voor de geschiedenis vóór dit changelog werd bijgehouden.

[0.2.13]: https://github.com/yusufkaracaburun/emeq-exact-api/releases/tag/v0.2.13
