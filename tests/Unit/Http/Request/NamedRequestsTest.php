<?php

declare(strict_types=1);

use Emeq\ExactApi\Enums\ExactDocumentType;
use Emeq\ExactApi\Http\Request\Delete\DeleteWebhookSubscription;
use Emeq\ExactApi\Http\Request\Read\GetGlAccounts;
use Emeq\ExactApi\Http\Request\Read\GetJournals;
use Emeq\ExactApi\Http\Request\Read\GetRelations;
use Emeq\ExactApi\Http\Request\Read\GetVatCodes;
use Emeq\ExactApi\Http\Request\Read\ListWebhookSubscriptions;
use Emeq\ExactApi\Http\Request\Read\ListWebhookTopics;
use Emeq\ExactApi\Http\Request\Write\CreateAccount;
use Emeq\ExactApi\Http\Request\Write\CreateDocument;
use Emeq\ExactApi\Http\Request\Write\CreateDocumentAttachment;
use Emeq\ExactApi\Http\Request\Write\CreateGeneralJournalEntry;
use Emeq\ExactApi\Http\Request\Write\CreatePurchaseEntry;
use Emeq\ExactApi\Http\Request\Write\CreateSalesEntry;
use Emeq\ExactApi\Http\Request\Write\CreateWebhookSubscription;
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

it('CreateSalesEntry adds YourRef when given and omits it otherwise', function (): void {
    $without = new CreateSalesEntry(customer: 'c', entryDate: '2026-06-17', journal: '80', description: 'x', lines: [['amount' => 10]]);
    $with    = new CreateSalesEntry(customer: 'c', entryDate: '2026-06-17', journal: '80', description: 'x', lines: [['amount' => 10]], yourRef: 'system · INV-1');

    expect($without->body()->all())->not->toHaveKey('YourRef')
        ->and($with->body()->all()['YourRef'])->toBe('system · INV-1');
});

it('CreatePurchaseEntry adds YourRef when given and omits it otherwise', function (): void {
    $without = new CreatePurchaseEntry(supplier: 's', entryDate: '2026-06-17', journal: '70', description: 'x', lines: [['amount' => 10]]);
    $with    = new CreatePurchaseEntry(supplier: 's', entryDate: '2026-06-17', journal: '70', description: 'x', lines: [['amount' => 10]], yourRef: 'system · PINV-1');

    expect($without->body()->all())->not->toHaveKey('YourRef')
        ->and($with->body()->all()['YourRef'])->toBe('system · PINV-1');
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

it('CreateAccount marks a debtor as a customer with the sales flag', function (): void {
    $request = new CreateAccount(
        name: 'Acme BV',
        status: 'C',
        isSales: true,
        vatNumber: 'NL000099998B57',
    );

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/crm/Accounts')
        ->and($request->body()->all())->toBe([
            'Name'      => 'Acme BV',
            'Status'    => 'C',
            'IsSales'   => true,
            'VATNumber' => 'NL000099998B57',
        ]);
});

it('CreateAccount marks a creditor as a supplier and drops null fields', function (): void {
    $request = new CreateAccount(
        name: 'Leverancier BV',
        isSupplier: true,
    );

    expect($request->body()->all())->toBe([
        'Name'       => 'Leverancier BV',
        'IsSupplier' => true,
    ]);
});

it('CreateDocument maps neutral input onto the Exact Documents body', function (): void {
    $request = new CreateDocument(
        subject: 'INV-1',
        type: ExactDocumentType::SalesInvoice->value,
        account: 'cust-guid',
        financialTransactionEntryId: 'entry-guid',
    );

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/documents/Documents')
        ->and($request->body()->all())->toBe([
            'Subject'                     => 'INV-1',
            'Type'                        => 10,
            'Account'                     => 'cust-guid',
            'FinancialTransactionEntryID' => 'entry-guid',
        ]);
});

it('CreateDocument drops null Account and FinancialTransactionEntryID', function (): void {
    $request = new CreateDocument(
        subject: 'Los bonnetje',
        type: ExactDocumentType::Miscellaneous->value,
    );

    expect($request->body()->all())->toBe([
        'Subject' => 'Los bonnetje',
        'Type'    => 55,
    ]);
});

it('CreateDocumentAttachment carries the base64 payload', function (): void {
    $request = new CreateDocumentAttachment(
        document: 'doc-guid',
        fileName: 'factuur.pdf',
        attachment: 'JVBERi0xLjQK',
    );

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/documents/DocumentAttachments')
        ->and($request->body()->all())->toBe([
            'Document'   => 'doc-guid',
            'FileName'   => 'factuur.pdf',
            'Attachment' => 'JVBERi0xLjQK',
        ]);
});

it('ExactDocumentType maps the creatable Exact document-type ids', function (): void {
    expect(ExactDocumentType::SalesInvoice->value)->toBe(10)
        ->and(ExactDocumentType::PurchaseInvoice->value)->toBe(20)
        ->and(ExactDocumentType::Miscellaneous->value)->toBe(55);
});

it('CreateWebhookSubscription sends only the required fields by default', function (): void {
    $request = new CreateWebhookSubscription(
        topic: 'FinancialTransactions',
        callbackUrl: 'https://hub-dev.emeq.nl/webhooks/exact',
    );

    expect($request->getMethod())->toBe(Method::POST)
        ->and($request->resolveEndpoint())->toBe('/webhooks/WebhookSubscriptions')
        ->and($request->body()->all())->toBe([
            'Topic'       => 'FinancialTransactions',
            'CallbackURL' => 'https://hub-dev.emeq.nl/webhooks/exact',
        ]);
});

it('CreateWebhookSubscription includes IsInstant and Description when set', function (): void {
    $request = new CreateWebhookSubscription(
        topic: 'Documents',
        callbackUrl: 'https://hub-dev.emeq.nl/webhooks/exact',
        isInstant: true,
        description: 'Emeq Hub',
    );

    expect($request->body()->all())->toBe([
        'Topic'       => 'Documents',
        'CallbackURL' => 'https://hub-dev.emeq.nl/webhooks/exact',
        'IsInstant'   => true,
        'Description' => 'Emeq Hub',
    ]);
});

it('webhook read requests own their division-relative path', function (string $class, string $endpoint): void {
    expect((new $class())->resolveEndpoint())->toBe($endpoint)
        ->and((new $class())->getMethod())->toBe(Method::GET);
})->with([
    'subscriptions' => [ListWebhookSubscriptions::class, '/webhooks/WebhookSubscriptions'],
    'topics'        => [ListWebhookTopics::class, '/webhooks/WebhookTopics'],
]);

it('DeleteWebhookSubscription targets the guid-addressed resource', function (): void {
    $request = new DeleteWebhookSubscription('11111111-2222-3333-4444-555555555555');

    expect($request->getMethod())->toBe(Method::DELETE)
        ->and($request->resolveEndpoint())->toBe("/webhooks/WebhookSubscriptions(guid'11111111-2222-3333-4444-555555555555')");
});
