<?php

namespace Aura\Base\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;

final class ExactDecimal
{
    public const MAX_DIGITS = 65;

    public static function applyComparison(
        EloquentBuilder|QueryBuilder $query,
        Connection $connection,
        string $wrappedColumn,
        string $operator,
        mixed $value,
    ): void {
        if (! in_array($operator, ['=', '!=', '>', '<', '>=', '<='], true)) {
            throw new InvalidArgumentException("Unsupported exact-decimal operator [{$operator}].");
        }

        $comparisonKey = self::sortableKey($value);

        if (str_starts_with($comparisonKey, '3')) {
            throw new InvalidArgumentException('Exact-decimal comparisons require a valid plain base-10 value.');
        }

        $key = self::sqlSortableKey($connection, $wrappedColumn);
        $query->whereRaw("{$key} IS NOT NULL AND {$key} {$operator} ?", [$comparisonKey]);
    }

    public static function applySorting(
        EloquentBuilder|QueryBuilder $query,
        Connection $connection,
        string $wrappedColumn,
        string $direction,
    ): void {
        $direction = strtolower($direction);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException("Unsupported exact-decimal sort direction [{$direction}].");
        }

        $key = self::sqlSortableKey($connection, $wrappedColumn);
        $query->orderByRaw("CASE WHEN {$key} IS NULL THEN 1 ELSE 0 END")
            ->orderByRaw("{$key} {$direction}");
    }

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

        if (preg_match('/\A([+-]?)(\d+)(?:\.(\d+))?\z/', $value, $matches) !== 1) {
            return '3'.$value;
        }

        $integer = ltrim($matches[2], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = $matches[3] ?? '';
        $digitCount = ($integer === '0' ? 0 : strlen($integer)) + strlen($fraction);

        if ($digitCount > self::MAX_DIGITS) {
            return '3'.$value;
        }

        $fraction = rtrim($fraction, '0');
        $fraction = str_pad($fraction, self::MAX_DIGITS, '0');
        $negative = $matches[1] === '-' && ($integer !== '0' || trim($fraction, '0') !== '');

        if (! $negative && $integer === '0' && trim($fraction, '0') === '') {
            return '1';
        }

        if (! $negative) {
            return '2'.sprintf('%03d', strlen($integer)).$integer.$fraction;
        }

        return '0'.sprintf('%03d', 999 - strlen($integer)).self::complement($integer.$fraction);
    }

    public static function supportsSql(Connection $connection): bool
    {
        return in_array($connection->getDriverName(), ['mysql', 'pgsql', 'sqlite'], true);
    }

    private static function complement(string $digits): string
    {
        return strtr($digits, '0123456789', '9876543210');
    }

    private static function mysqlComplement(string $expression): string
    {
        // Replace through non-digit placeholders so 0 -> 9 cannot be changed
        // again by the later 9 -> 0 pass.
        $temporary = range('A', 'J');

        foreach (range(0, 9) as $digit) {
            $expression = "REPLACE({$expression}, '{$digit}', '{$temporary[$digit]}')";
        }

        foreach (range(0, 9) as $digit) {
            $expression = "REPLACE({$expression}, '{$temporary[$digit]}', '".(9 - $digit)."')";
        }

        return $expression;
    }

    private static function mysqlSortableKey(string $column): string
    {
        $value = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(CAST({$column} AS CHAR), CHAR(0), ' '), CHAR(9), ' '), CHAR(10), ' '), CHAR(11), ' '), CHAR(13), ' '))";
        $unsigned = "(CASE WHEN LEFT({$value}, 1) IN ('+', '-') THEN SUBSTRING({$value}, 2) ELSE {$value} END)";
        $integerRaw = "SUBSTRING_INDEX({$unsigned}, '.', 1)";
        $fractionRaw = "(CASE WHEN LOCATE('.', {$unsigned}) > 0 THEN SUBSTRING({$unsigned}, LOCATE('.', {$unsigned}) + 1) ELSE '' END)";
        $integer = "COALESCE(NULLIF(TRIM(LEADING '0' FROM {$integerRaw}), ''), '0')";
        $fraction = "TRIM(TRAILING '0' FROM {$fractionRaw})";
        $paddedFraction = "RPAD({$fraction}, ".self::MAX_DIGITS.", '0')";
        $magnitude = "CONCAT({$integer}, {$paddedFraction})";
        $digitCount = "((CASE WHEN {$integer} = '0' THEN 0 ELSE CHAR_LENGTH({$integer}) END) + CHAR_LENGTH({$fractionRaw}))";
        $zero = "({$integer} = '0' AND {$fraction} = '')";
        $valid = "({$value} REGEXP '^[+-]?[0-9]+([.][0-9]+)?$' AND {$value} NOT REGEXP '[^-+0-9.]' AND {$digitCount} <= ".self::MAX_DIGITS.')';
        $negative = "(LEFT({$value}, 1) = '-' AND NOT {$zero})";
        $negativeKey = "CONCAT('0', LPAD(999 - CHAR_LENGTH({$integer}), 3, '0'), ".self::mysqlComplement($magnitude).')';
        $positiveKey = "CONCAT('2', LPAD(CHAR_LENGTH({$integer}), 3, '0'), {$magnitude})";

        return "(CASE WHEN {$valid} THEN CAST(CASE WHEN {$zero} THEN '1' WHEN {$negative} THEN {$negativeKey} ELSE {$positiveKey} END AS BINARY) ELSE NULL END)";
    }

    private static function postgresSortableKey(string $column): string
    {
        $value = "BTRIM(REPLACE(REPLACE(REPLACE(REPLACE(CAST({$column} AS TEXT), CHR(9), ' '), CHR(10), ' '), CHR(11), ' '), CHR(13), ' '))";
        $unsigned = "(CASE WHEN LEFT({$value}, 1) IN ('+', '-') THEN SUBSTRING({$value} FROM 2) ELSE {$value} END)";
        $integerRaw = "SPLIT_PART({$unsigned}, '.', 1)";
        $fractionRaw = "(CASE WHEN STRPOS({$unsigned}, '.') > 0 THEN SPLIT_PART({$unsigned}, '.', 2) ELSE '' END)";
        $integer = "COALESCE(NULLIF(LTRIM({$integerRaw}, '0'), ''), '0')";
        $fraction = "RTRIM({$fractionRaw}, '0')";
        $paddedFraction = "RPAD({$fraction}, ".self::MAX_DIGITS.", '0')";
        $magnitude = "({$integer} || {$paddedFraction})";
        $digitCount = "((CASE WHEN {$integer} = '0' THEN 0 ELSE LENGTH({$integer}) END) + LENGTH({$fractionRaw}))";
        $zero = "({$integer} = '0' AND {$fraction} = '')";
        $valid = "({$value} ~ '^[+-]?[0-9]+([.][0-9]+)?$' AND {$value} !~ '[^-+0-9.]' AND {$digitCount} <= ".self::MAX_DIGITS.')';
        $negative = "(LEFT({$value}, 1) = '-' AND NOT {$zero})";
        $negativeKey = "('0' || LPAD((999 - LENGTH({$integer}))::TEXT, 3, '0') || TRANSLATE({$magnitude}, '0123456789', '9876543210'))";
        $positiveKey = "('2' || LPAD(LENGTH({$integer})::TEXT, 3, '0') || {$magnitude})";

        return "((CASE WHEN {$valid} THEN CASE WHEN {$zero} THEN '1' WHEN {$negative} THEN {$negativeKey} ELSE {$positiveKey} END ELSE NULL END) COLLATE \"C\")";
    }

    private static function sqliteSortableKey(Connection $connection, string $column): string
    {
        self::registerSqliteFunction($connection);
        $key = "aura_decimal_sort_key({$column})";

        return "(CASE WHEN SUBSTR({$key}, 1, 1) IN ('0', '1', '2') THEN {$key} ELSE NULL END)";
    }

    /**
     * Valid values become a binary-collated key: reversed negative magnitude,
     * zero, then positive magnitude. Invalid values become NULL. String
     * operations avoid all float and DECIMAL narrowing.
     */
    private static function sqlSortableKey(Connection $connection, string $wrappedColumn): string
    {
        return match ($connection->getDriverName()) {
            'mysql' => self::mysqlSortableKey($wrappedColumn),
            'pgsql' => self::postgresSortableKey($wrappedColumn),
            'sqlite' => self::sqliteSortableKey($connection, $wrappedColumn),
            default => throw new InvalidArgumentException("Exact-decimal queries are not supported for database driver [{$connection->getDriverName()}]."),
        };
    }
}
