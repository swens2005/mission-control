<?php

use App\Support\Money\Discount;
use App\Support\Money\Money;
use App\Support\Money\Quantity;

test('amounts are parsed the way people type them', function (string $input, int $cents) {
    expect(Money::parse($input))->toBe($cents);
})->with([
    'whole euros' => ['1250', 125000],
    'one decimal' => ['1250.5', 125050],
    'dutch' => ['1.250,50', 125050],
    'english' => ['1,250.50', 125050],
    'euro sign and space' => ['€ 99', 9900],
    'euro sign, no space' => ['€99,95', 9995],
    'dutch decimals' => ['99,95', 9995],
    'thousands only, dot' => ['1.250', 125000],
    'thousands only, comma' => ['1,250', 125000],
    'millions' => ['1.250.000', 125000000],
    'cents only' => ['0,05', 5],
    'zero' => ['0', 0],
    'space as thousands' => ['1 250', 125000],
    'surrounding space' => ['  85 ', 8500],
]);

test('anything else is refused', function (string $input) {
    expect(Money::parse($input))->toBeNull();
})->with([
    'empty' => [''],
    'only a sign' => ['€'],
    'negative' => ['-5'],
    'three decimals' => ['12.345.6'],
    'same separator twice' => ['1,250,50'],
    'bad groups' => ['12.34.567'],
    'four decimals' => ['1.2345'],
    'trailing separator' => ['5.'],
    'letters' => ['twelve'],
    'exponent' => ['1e5'],
    'too large' => ['99999999999'],
]);

test('cents are formatted for display and for inputs', function () {
    expect(Money::format(123456))->toBe('€1,234.56')
        ->and(Money::format(5))->toBe('€0.05')
        ->and(Money::format(0))->toBe('€0.00')
        ->and(Money::format(-1250))->toBe('−€12.50')
        ->and(Money::format(123456789000))->toBe('€1,234,567,890.00')
        ->and(Money::toInput(125000))->toBe('1250')
        ->and(Money::toInput(125050))->toBe('1250.50')
        ->and(Money::toInput(5))->toBe('0.05');
});

test('quantities are parsed as hundredths and formatted back', function () {
    expect(Quantity::parse('1'))->toBe(100)
        ->and(Quantity::parse('1.5'))->toBe(150)
        ->and(Quantity::parse('1,25'))->toBe(125)
        ->and(Quantity::parse('0.25'))->toBe(25)
        ->and(Quantity::parse('-1'))->toBeNull()
        ->and(Quantity::parse('1.255'))->toBeNull()
        ->and(Quantity::parse('abc'))->toBeNull()
        ->and(Quantity::format(100))->toBe('1')
        ->and(Quantity::format(150))->toBe('1.5')
        ->and(Quantity::format(125))->toBe('1.25')
        ->and(Quantity::format(25))->toBe('0.25');
});

test('discount percentages are parsed as basis points', function () {
    expect(Discount::parsePercentage('10'))->toBe(1000)
        ->and(Discount::parsePercentage('12,5'))->toBe(1250)
        ->and(Discount::parsePercentage('7.25 %'))->toBe(725)
        ->and(Discount::parsePercentage('100'))->toBe(10000)
        ->and(Discount::parsePercentage('-5'))->toBeNull()
        ->and(Discount::parsePercentage('1000'))->toBeNull()
        ->and(Discount::parsePercentage('ten'))->toBeNull()
        ->and(Discount::formatPercentage(1250))->toBe('12.5%');
});
