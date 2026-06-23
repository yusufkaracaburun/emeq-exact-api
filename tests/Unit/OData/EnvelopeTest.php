<?php

declare(strict_types=1);

use Emeq\ExactApi\OData\Envelope;

it('reads results from the d.results envelope', function (): void {
    $json = ['d' => ['results' => [['ID' => '1'], ['ID' => '2']]]];

    expect(Envelope::results($json))->toBe([['ID' => '1'], ['ID' => '2']]);
});

it('reads results when d is the list itself', function (): void {
    $json = ['d' => [['ID' => '1']]];

    expect(Envelope::results($json))->toBe([['ID' => '1']]);
});

it('wraps a single d-record as a one-item list', function (): void {
    $json = ['d' => ['ID' => 'abc', 'Code' => '8000']];

    expect(Envelope::results($json))->toBe([['ID' => 'abc', 'Code' => '8000']]);
});

it('returns an empty list for null or shapeless json', function (): void {
    expect(Envelope::results(null))->toBe([])
        ->and(Envelope::results(['nope' => 1]))->toBe([]);
});

it('extracts the first id from a create-response', function (): void {
    expect(Envelope::firstId(['d' => ['ID' => 'guid-1']]))->toBe('guid-1')
        ->and(Envelope::firstId(['d' => [['ID' => 'guid-2']]]))->toBe('guid-2')
        ->and(Envelope::firstId(['d' => ['results' => [['ID' => 'guid-3']]]]))->toBe('guid-3');
});

it('prefers EntryID over ID for general-journal creates', function (): void {
    expect(Envelope::firstId(['d' => ['EntryID' => 'gj-1']]))->toBe('gj-1')
        ->and(Envelope::firstId(['d' => ['EntryID' => 'gj-2', 'ID' => 'ignored']]))->toBe('gj-2');
});

it('returns null when no id is present', function (): void {
    expect(Envelope::firstId(null))->toBeNull()
        ->and(Envelope::firstId(['d' => ['Code' => '8000']]))->toBeNull();
});

it('extracts the human EntryNumber from a create-response', function (): void {
    expect(Envelope::firstEntryNumber(['d' => ['EntryID' => 'se-1', 'EntryNumber' => 60001]]))->toBe(60001)
        ->and(Envelope::firstEntryNumber(['d' => [['EntryNumber' => 60002]]]))->toBe(60002)
        ->and(Envelope::firstEntryNumber(['d' => ['results' => [['EntryNumber' => 60003]]]]))->toBe(60003)
        ->and(Envelope::firstEntryNumber(['d' => ['EntryNumber' => '60004']]))->toBe(60004);
});

it('returns null EntryNumber when absent or shapeless', function (): void {
    expect(Envelope::firstEntryNumber(null))->toBeNull()
        ->and(Envelope::firstEntryNumber(['d' => ['EntryID' => 'se-1']]))->toBeNull()
        ->and(Envelope::firstEntryNumber(['nope' => 1]))->toBeNull();
});

it('extracts the auto-linked Document ref (purchase) and null otherwise (sales)', function (): void {
    expect(Envelope::documentRef(['d' => ['EntryID' => 'pe-1', 'Document' => 'doc-guid']]))->toBe('doc-guid')
        ->and(Envelope::documentRef(['d' => ['EntryID' => 'se-1', 'Document' => null]]))->toBeNull()
        ->and(Envelope::documentRef(['d' => ['EntryID' => 'se-2']]))->toBeNull()
        ->and(Envelope::documentRef(null))->toBeNull();
});
