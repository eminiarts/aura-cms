<?php

namespace Aura\Base\Support;

use Illuminate\Database\Connection;

final class ExactDecimal
{
    public static function registerSqliteFunction(Connection $connection): void
    {
        if ($connection->getDriverName() !== 'sqlite') {
            return;
        }

        $connection->getPdo()->sqliteCreateFunction(
            'aura_decimal_sort_key',
            self::sortableKey(...),
            1,
            true,
        );
    }

    public static function sortableKey(mixed $value): string
    {
        $value = trim((string) $value);

        if (preg_match('/^([+-]?)(\d+)(?:\.(\d+))?$/', $value, $matches) !== 1) {
            return '3'.$value;
        }

        $integer = ltrim($matches[2], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = rtrim($matches[3] ?? '', '0');
        $fraction = str_pad($fraction, 65, '0');
        $negative = $matches[1] === '-' && ($integer !== '0' || trim($fraction, '0') !== '');

        if (! $negative && $integer === '0' && trim($fraction, '0') === '') {
            return '1';
        }

        if (! $negative) {
            return '2'.sprintf('%03d', strlen($integer)).$integer.$fraction;
        }

        return '0'.sprintf('%03d', 999 - strlen($integer)).self::complement($integer.$fraction);
    }

    private static function complement(string $digits): string
    {
        return strtr($digits, '0123456789', '9876543210');
    }
}
