<?php

declare(strict_types=1);

use Emeq\ExactApi\Http\Request\Read\GetGlAccounts;
use Emeq\ExactApi\Http\Request\Read\GetJournals;
use Emeq\ExactApi\Http\Request\Read\GetRelations;
use Emeq\ExactApi\Http\Request\Read\GetVatCodes;
use Emeq\ExactApi\Http\Request\Write\CreateGeneralJournalEntry;
use Emeq\ExactApi\Http\Request\Write\CreatePurchaseEntry;
use Emeq\ExactApi\Http\Request\Write\CreateSalesEntry;
use Saloon\Enums\Method;

it('GetGlAccounts owns its path and passes the query through', function (): void {
    $request = new GetGlAccounts(['$select' => 'ID,Code,Description', '$top' => 50]);

    expect($request->getMethod())->toBe(Method::GET)
        ->and($request->resolveEndpoint())->toBe('/financial/GLAccounts')
        ->and($request->query()->all())->toBe(['$select' => 'ID,Code,Description', '$top' => 50]);
});

it('GetGlAccounts defaults to an empty query', function (): void {
    expect((new GetGlAccounts())->query()->all())->toBe([]);
});

it('CreateSalesEntry maps neutral input onto the Exact SalesEntries body', function (): void {
    $request = new CreateSalesEntry(
        customer: 'cust-guid',
        entryDate: '2026-06-17',
        journal: '70',
        description: 'INV-1',
        lines: [
            ['description' => 'Regel 1', 'amount' => 100.0, 'vatCode' => '1', 'glAccount' => 'gl-guid'],
        ],
    );

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/salesentry/SalesEntries')
        ->and($request->body()->all())->toBe([
            'Customer'        => 'cust-guid',
            'EntryDate'       => '2026-06-17',
            'Journal'         => '70',
            'Description'     => 'INV-1',
            'SalesEntryLines' => [
                ['Description' => 'Regel 1', 'AmountFC' => 100.0, 'VATCode' => '1', 'GLAccount' => 'gl-guid'],
            ],
        ]);
});

it('CreateSalesEntry drops null line fields but keeps the amount', function (): void {
    $request = new CreateSalesEntry(
        customer: 'cust-guid',
        entryDate: '2026-06-17',
        journal: '70',
        description: 'INV-2',
        lines: [
            ['amount' => 42.5],
        ],
    );

    expect($request->body()->all()['SalesEntryLines'])->toBe([
        ['AmountFC' => 42.5],
    ]);
});

it('read requests own their division-relative OData path', function (string $class, string $endpoint): void {
    expect((new $class())->resolveEndpoint())->toBe($endpoint)
        ->and((new $class())->getMethod())->toBe(Method::GET);
})->with([
    'gl accounts' => [GetGlAccounts::class, '/financial/GLAccounts'],
    'vat codes'   => [GetVatCodes::class, '/vat/VATCodes'],
    'relations'   => [GetRelations::class, '/crm/Accounts'],
    'journals'    => [GetJournals::class, '/financial/Journals'],
]);

it('CreatePurchaseEntry maps neutral input onto the Exact PurchaseEntries body', function (): void {
    $request = new CreatePurchaseEntry(
        supplier: 'supp-guid',
        entryDate: '2026-06-17',
        journal: '20',
        description: 'PINV-1',
        lines: [
            ['description' => 'Regel 1', 'amount' => 80.0, 'vatCode' => '5', 'glAccount' => 'gl-guid'],
        ],
    );

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/purchaseentry/PurchaseEntries')
        ->and($request->body()->all())->toBe([
            'Supplier'           => 'supp-guid',
            'EntryDate'          => '2026-06-17',
            'Journal'            => '20',
            'Description'        => 'PINV-1',
            'PurchaseEntryLines' => [
                ['Description' => 'Regel 1', 'AmountFC' => 80.0, 'VATCode' => '5', 'GLAccount' => 'gl-guid'],
            ],
        ]);
});

it('CreateGeneralJournalEntry omits header Description and line VATCode', function (): void {
    $request = new CreateGeneralJournalEntry(
        journalCode: '90',
        lines: [
            ['description' => 'Regel 1', 'amount' => 12.34, 'vatCode' => '0', 'glAccount' => 'gl-guid'],
        ],
    );

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/generaljournalentry/GeneralJournalEntries')
        ->and($request->body()->all())->toBe([
            'JournalCode'              => '90',
            'GeneralJournalEntryLines' => [
                ['Description' => 'Regel 1', 'AmountDC' => 12.34, 'GLAccount' => 'gl-guid'],
            ],
        ]);
});
