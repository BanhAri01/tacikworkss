<?php

namespace App\Support;

use InvalidArgumentException;

/** USD money helpers. Amounts are integer cents; never use floats for money. */
class Money
{
    public const DECIMAL_REGEX = '/^\d{1,6}(\.\d{1,2})?$/';

    /** "12", "12.5", "12.50" → 1250 (string parsing, no float math). */
    public static function toCents(string $amount): int
    {
        $amount = trim($amount);

        if (! preg_match(self::DECIMAL_REGEX, $amount)) {
            throw new InvalidArgumentException("Invalid amount [{$amount}].");
        }

        [$whole, $fraction] = array_pad(explode('.', $amount), 2, '0');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    /** 1250 → "12.50" */
    public static function toDecimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** 1250 → "$12.50" */
    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.'$'.number_format(intdiv($cents, 100)).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** 1000 → "10%", 440 → "4.4%" */
    public static function percent(int $bps): string
    {
        return rtrim(rtrim(intdiv($bps, 100).'.'.str_pad((string) ($bps % 100), 2, '0', STR_PAD_LEFT), '0'), '.').'%';
    }

    /** Percentage in basis points of an amount, rounded half up. */
    public static function bps(int $cents, int $bps): int
    {
        return intdiv($cents * $bps + 5000, 10000);
    }

    /**
     * Split $total across $weights proportionally (largest remainder), so parts always sum to $total.
     *
     * @param  array<array-key, int>  $weights
     * @return array<array-key, int>
     */
    public static function allocate(int $total, array $weights): array
    {
        $sum = array_sum($weights);

        if ($sum <= 0) {
            $weights = array_map(fn () => 1, $weights);
            $sum = count($weights);
        }

        $parts = [];
        $remainders = [];

        foreach ($weights as $key => $weight) {
            $parts[$key] = intdiv($total * $weight, $sum);
            $remainders[$key] = ($total * $weight) % $sum;
        }

        $left = $total - array_sum($parts);
        arsort($remainders);

        foreach (array_keys($remainders) as $key) {
            if ($left <= 0) {
                break;
            }
            $parts[$key]++;
            $left--;
        }

        return $parts;
    }
}
