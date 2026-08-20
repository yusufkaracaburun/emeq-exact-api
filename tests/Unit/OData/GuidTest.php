<?php

declare(strict_types=1);

use Emeq\ExactApi\OData\Guid;

it('accepts a well-formed uuid in either case', function (): void {
    expect(Guid::from('d3b3f9a1-9c2e-4b7a-8f7e-2f4a1b6c9d0e')->value)
        ->toBe('d3b3f9a1-9c2e-4b7a-8f7e-2f4a1b6c9d0e')
        ->and(Guid::from('D3B3F9A1-9C2E-4B7A-8F7E-2F4A1B6C9D0E')->value)
        ->toBe('D3B3F9A1-9C2E-4B7A-8F7E-2F4A1B6C9D0E');
});

it('rejects anything that is not a uuid', function (string $value): void {
    Guid::from($value);
})->with([
    'empty'        => '',
    'braced'       => '{D3B3F9A1-9C2E-4B7A-8F7E-2F4A1B6C9D0E}',
    'unhyphenated' => 'd3b3f9a19c2e4b7a8f7e2f4a1b6c9d0e',
    'too short'    => 'd3b3f9a1-9c2e-4b7a-8f7e-2f4a1b6c9d0',
    'injection'    => "d3b3f9a1-9c2e-4b7a-8f7e-2f4a1b6c9d0e' or '1'='1",
])->throws(InvalidArgumentException::class);

it('returns null instead of throwing via tryFrom', function (): void {
    expect(Guid::tryFrom('not-a-guid'))->toBeNull()
        ->and(Guid::tryFrom('d3b3f9a1-9c2e-4b7a-8f7e-2f4a1b6c9d0e'))->toBeInstanceOf(Guid::class);
});
