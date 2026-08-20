<?php

declare(strict_types=1);

use Emeq\ExactApi\OData\DateValue;

it('parses epoch milliseconds into a UTC moment', function (): void {
    $parsed = DateValue::parse('/Date(1755123456000)/');

    expect($parsed?->format('Y-m-d H:i:s'))->toBe('2025-08-13 22:17:36')
        ->and($parsed?->getTimezone()->getName())->toBe('UTC');
});

it('ignores a trailing timezone offset because the epoch is already absolute', function (): void {
    expect(DateValue::parse('/Date(1755123456000+0200)/')?->format('Y-m-d H:i:s'))
        ->toBe('2025-08-13 22:17:36');
});

it('parses a pre-epoch value without rounding into the wrong day', function (): void {
    expect(DateValue::parse('/Date(-500)/')?->format('Y-m-d H:i:s'))
        ->toBe('1969-12-31 23:59:59');
});

it('returns null for anything that is not an Exact date', function (mixed $value): void {
    expect(DateValue::parse($value))->toBeNull();
})->with([
    'null'         => null,
    'int'          => 1755123456000,
    'iso string'   => '2026-08-13',
    'empty'        => '',
    'no digits'    => '/Date()/',
    'not anchored' => 'prefix /Date(1755123456000)/',
]);
