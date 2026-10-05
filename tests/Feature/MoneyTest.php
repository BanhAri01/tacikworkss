<?php

use App\Models\Setting;
use App\Support\Fees;
use App\Support\Money;

test('decimal strings convert to cents without floats', function (string $in, int $out) {
    expect(Money::toCents($in))->toBe($out);
})->with([
    ['12', 1200], ['12.5', 1250], ['12.50', 1250], ['0.01', 1], ['0.10', 10], ['999999.99', 99999999],
]);

test('invalid amounts are rejected', function (string $in) {
    Money::toCents($in);
})->throws(InvalidArgumentException::class)->with(['', '-1', '1.234', 'abc', '1,50']);

test('cents format back to strings', function () {
    expect(Money::toDecimal(1250))->toBe('12.50')
        ->and(Money::toDecimal(5))->toBe('0.05')
        ->and(Money::format(123456))->toBe('$1,234.56')
        ->and(Money::percent(1000))->toBe('10%')
        ->and(Money::percent(440))->toBe('4.4%');
});

test('basis points round half up', function () {
    expect(Money::bps(1000, 3000))->toBe(300)
        ->and(Money::bps(999, 1000))->toBe(100)   // 99.9 → 100
        ->and(Money::bps(994, 1000))->toBe(99);   // 99.4 → 99
});

test('allocation always sums to the total', function () {
    $parts = Money::allocate(100, [1, 1, 1]);
    expect(array_sum($parts))->toBe(100)->and($parts)->toEqualCanonicalizing([34, 33, 33]);

    $parts = Money::allocate(77, [1999, 500, 1]);
    expect(array_sum($parts))->toBe(77);

    expect(Money::allocate(10, [0, 0]))->toBe([5, 5]);
});

test('buyer processing fee covers PayPal so the seller nets the item price', function () {
    // Defaults: 4.4% + 30¢
    $fee = Fees::processingFee(1000);
    expect($fee)->toBe(78);

    $gross = 1000 + $fee;
    $paypalFee = Money::bps($gross, 440) + 30;
    expect($gross - $paypalFee)->toBeGreaterThanOrEqual(1000);

    expect(Fees::processingFee(0))->toBe(0);
});

test('processing fee follows admin settings', function () {
    Setting::put('processing_fee_bps', 0);
    Setting::put('processing_fee_fixed_cents', 0);

    expect(Fees::processingFee(1000))->toBe(0);
});
