<?php

declare(strict_types=1);

use Emeq\ExactApi\OData\Filter;
use Emeq\ExactApi\OData\Guid;

it('quotes a string value', function (): void {
    expect(Filter::eq('ChamberOfCommerce', '12345678')->expression)
        ->toBe("ChamberOfCommerce eq '12345678'");
});

it('doubles apostrophes so a quote cannot break out of the expression', function (): void {
    expect(Filter::eq('Name', "O'Reilly")->expression)
        ->toBe("Name eq 'O''Reilly'");
});

it('escapes an injection attempt into a literal instead of an operator', function (): void {
    expect(Filter::eq('Name', "x' or IsSales eq true or Name eq '")->expression)
        ->toBe("Name eq 'x'' or IsSales eq true or Name eq '''");
});

it('emits a guid literal for a Guid value', function (): void {
    expect(Filter::eq('ID', Guid::from('d3b3f9a1-9c2e-4b7a-8f7e-2f4a1b6c9d0e'))->expression)
        ->toBe("ID eq guid'd3b3f9a1-9c2e-4b7a-8f7e-2f4a1b6c9d0e'");
});

it('emits bare literals for bool, int, float and null', function (): void {
    expect(Filter::eq('IsSupplier', true)->expression)->toBe('IsSupplier eq true')
        ->and(Filter::eq('IsSales', false)->expression)->toBe('IsSales eq false')
        ->and(Filter::eq('Status', 20)->expression)->toBe('Status eq 20')
        ->and(Filter::eq('Rate', 21.5)->expression)->toBe('Rate eq 21.5')
        ->and(Filter::eq('DueDate', null)->expression)->toBe('DueDate eq null');
});

it('rejects a value type it cannot format', function (): void {
    Filter::eq('Whatever', new stdClass());
})->throws(InvalidArgumentException::class);

it('passes a raw expression through untouched', function (): void {
    expect(Filter::raw("substringof('emeq', Name) eq true")->expression)
        ->toBe("substringof('emeq', Name) eq true");
});
