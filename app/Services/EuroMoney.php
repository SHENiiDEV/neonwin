<?php

namespace App\Services;

use InvalidArgumentException;

class EuroMoney
{
    public static function cents(mixed $amount): int
    {
        if (! is_scalar($amount) || is_bool($amount) || ! preg_match('/^(0|[1-9][0-9]{0,10})(?:\.([0-9]{1,2}))?$/D', (string) $amount, $parts)) {
            throw new InvalidArgumentException('Amount must be nonnegative with at most two decimals.');
        }

        return ((int) $parts[1] * 100) + (int) str_pad($parts[2] ?? '', 2, '0');
    }

    public static function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
