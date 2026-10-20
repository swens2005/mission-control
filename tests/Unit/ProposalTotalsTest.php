<?php

use App\Support\Money\Discount;
use App\Support\Money\ProposalTotals;

/**
 * @return array{unitCents: int, quantity: int}
 */
function line(int $unitCents, int $quantity = 100): array
{
    return ['unitCents' => $unitCents, 'quantity' => $quantity];
}

test('an empty proposal is all zeros', function () {
    expect(ProposalTotals::calculate([])->toArray())->toBe([
        'lineTotals' => [],
        'subtotal' => 0,
        'discount' => 0,
        'net' => 0,
        'vat' => 0,
        'total' => 0,
    ]);
});

test('fixed lines add up, with 21% VAT on top', function () {
    $totals = ProposalTotals::calculate([line(125000), line(45000, 300)]);

    // €1,250 + 3 × €450 = €2,600; VAT €546.
    expect($totals->lineTotals)->toBe([125000, 135000])
        ->and($totals->subtotal)->toBe(260000)
        ->and($totals->vat)->toBe(54600)
        ->and($totals->total)->toBe(314600);
});

test('hourly lines in quarter hours round half up to the cent', function (int $unit, int $quantity, int $expected) {
    expect(ProposalTotals::lineTotal($unit, $quantity))->toBe($expected);
})->with([
    // €85.50 × 1.25 h = €106.875 → €106.88 (exactly half a cent: up)
    'half a cent rounds up' => [8550, 125, 10688],
    // €85.30 × 0.25 h = €21.325 → €21.33
    'quarter hour, half cent' => [8530, 25, 2133],
    // €85.10 × 0.75 h = €63.825 → €63.83
    'three quarters, half cent' => [8510, 75, 6383],
    // €85.01 × 0.25 h = €21.2525 → €21.25 (a quarter cent: down)
    'quarter cent rounds down' => [8501, 25, 2125],
    // €85.03 × 0.75 h = €63.7725 → €63.77
    'three quarter cents round down' => [8503, 75, 6377],
    'whole hours never round' => [8550, 300, 25650],
]);

test('a percentage discount is rounded once, before VAT', function () {
    // 10% of €1,234.55 = €123.455 → €123.46; net €1,111.09; VAT €233.3289 → €233.33.
    $totals = ProposalTotals::calculate([line(123455)], Discount::percentage(1000));

    expect($totals->discount)->toBe(12346)
        ->and($totals->net())->toBe(111109)
        ->and($totals->vat)->toBe(23333)
        ->and($totals->total)->toBe(134442);
});

test('a fixed discount comes off the subtotal', function () {
    $totals = ProposalTotals::calculate([line(250000)], Discount::fixed(25000));

    expect($totals->discount)->toBe(25000)
        ->and($totals->vat)->toBe(47250)
        ->and($totals->total)->toBe(272250);
});

test('a discount can take the whole subtotal but not more', function () {
    expect(ProposalTotals::calculate([line(10000)], Discount::fixed(10000))->total)->toBe(0)
        ->and(ProposalTotals::calculate([line(10000)], Discount::percentage(10000))->total)->toBe(0);

    ProposalTotals::calculate([line(10000)], Discount::fixed(10001));
})->throws(InvalidArgumentException::class, 'larger than the subtotal');

test('discounts outside 0 to 100% or below zero are refused', function (Closure $make) {
    $make();
})->throws(InvalidArgumentException::class)->with([
    'over 100%' => [fn () => Discount::percentage(10001)],
    'negative percentage' => [fn () => Discount::percentage(-1)],
    'negative amount' => [fn () => Discount::fixed(-1)],
]);

test('negative prices or quantities are refused', function () {
    ProposalTotals::calculate([line(-100)]);
})->throws(InvalidArgumentException::class);

test('VAT at exactly half a cent rounds up, just below rounds down', function (int $net, int $vat) {
    expect(ProposalTotals::calculate([line($net)])->vat)->toBe($vat);
})->with([
    // 21% of €0.50 = 10.5 cents → 11
    'half a cent' => [50, 11],
    // 21% of €1.50 = 31.5 cents → 32
    'half a cent again' => [150, 32],
    // 21% of €0.02 = 0.42 cents → 0
    'less than half' => [2, 0],
    // 21% of €0.03 = 0.63 cents → 1
    'more than half' => [3, 1],
]);

test('VAT on the total differs from VAT per line, and the total wins', function () {
    // Three lines of €10.02. Per line: 21% = 210.42 cents → €2.10, three
    // times €6.30. On the total: 21% of €30.06 = 631.26 cents → €6.31.
    $lines = [line(1002), line(1002), line(1002)];
    $perLine = array_sum(array_map(fn ($l) => ProposalTotals::roundedShare($l['unitCents'], ProposalTotals::VAT_RATE), $lines));

    expect($perLine)->toBe(630)
        ->and(ProposalTotals::calculate($lines)->vat)->toBe(631);
});

test('large amounts stay exact', function () {
    // €1,234,567.89 × 10,000 hours: well past where floats lose cents.
    $totals = ProposalTotals::calculate([line(123456789, 1000000)]);

    expect($totals->subtotal)->toBe(1234567890000)
        ->and($totals->vat)->toBe(259259256900)
        ->and($totals->total)->toBe(1493827146900);
});
