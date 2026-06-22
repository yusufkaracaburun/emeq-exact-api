# Exact Online — accounting-resources (named requests)

Grondslag voor de named request-classes in `src/Http/Request/`. Paden zijn
division-relatief (de connector zet `{api_base_url}/api/v1/{division}` ervoor).

Bron: officiële Exact REST API-referentie (HlpRestAPIResources). De write-endpoints
+ verplichte velden zijn live geverifieerd tegen een Exact test-administratie.

## Read (GET)

| Request | Endpoint | Toelichting |
|---|---|---|
| `Read\GetGlAccounts` | `financial/GLAccounts` | Grootboekrekeningen (`ID`, `Code`, `Description`) |
| `Read\GetVatCodes` | `vat/VATCodes` | BTW-codes (`Code`, `Description`, `Percentage`) |
| `Read\GetRelations` | `crm/Accounts` | Relaties / debiteuren-crediteuren (`ID`, `Name`, `Code`) |
| `Read\GetJournals` | `financial/Journals` | Dagboeken (`Code`, `Description`, `Type`) |

De OData-query (`$select`/`$filter`/`$top`/…) levert de caller; de request bezit
alleen het pad. Responses worden gedecodeerd via `OData\Envelope::results()`.

## Write (POST)

| Request | Endpoint | Relatie-veld | Dagboek-veld | Regel-collectie | Bedrag-veld |
|---|---|---|---|---|---|
| `Write\CreateSalesEntry` | `salesentry/SalesEntries` | `Customer` | `Journal` | `SalesEntryLines` | `AmountFC` |
| `Write\CreatePurchaseEntry` | `purchaseentry/PurchaseEntries` | `Supplier` | `Journal` | `PurchaseEntryLines` | `AmountFC` |
| `Write\CreateGeneralJournalEntry` | `generaljournalentry/GeneralJournalEntries` | — | `JournalCode` | `GeneralJournalEntryLines` | `AmountDC` |

Afwijkingen om te onthouden (live geverifieerd):
- Verkoop is een **GL-based boeking** in het verkoopdagboek (`SalesEntries`), niet een
  item-based `SalesInvoices` — accounting-sync zet boekhoud-data weg, het invoicen
  gebeurt bij de Consumer.
- Memoriaal gebruikt `JournalCode` (niet `Journal`) en `AmountDC` (niet `AmountFC`),
  kent geen relatie/`EntryDate`, en verstuurt géén header-`Description` of regel-`VATCode`
  (Exact weigert beide).

De caller levert al-geresolvede waarden (relatie-GUID, journaal-code, regels met
`amount`/`vatCode`/`glAccount`) in een neutrale vorm; de request mapt die naar de
Exact-veldnamen. Het create-antwoord levert de externe referentie via
`OData\Envelope::firstId()`.

### Relatie aanmaken

| Request | Endpoint | Verplicht | Optioneel |
|---|---|---|---|
| `Write\CreateAccount` | `crm/Accounts` | `Name` | `Status`, `IsSales`, `IsSupplier`, `VATNumber` |

`Name` is het enige verplichte veld (officiële Exact-referentie). Een debiteur krijgt
`Status='C'` + `IsSales=true`, een crediteur `IsSupplier=true`; `VATNumber` is de
stabiele dedup-sleutel als die er is. Null-velden worden niet meegestuurd. De Hub
gebruikt dit voor lazy auto-create van een ontbrekende relatie tijdens een boeking
(opt-in per Connection); de respons-`d.ID` (GUID) komt via `OData\Envelope::firstId()`.
