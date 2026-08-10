<?php

namespace Aura\Base\Support;

use Aura\Base\Fields\Number;
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
        ?array $numberConfiguration = null,
    ): void {
        if (! in_array($operator, ['=', '!=', '>', '<', '>=', '<='], true)) {
            throw new InvalidArgumentException("Unsupported exact-decimal operator [{$operator}].");
        }

        $configuration = $numberConfiguration === null
            ? null
            : self::validatedConfiguration($numberConfiguration);

        if ($configuration !== null) {
            $value = self::configuredNormalizedValue($value, $configuration);
        }

        $comparisonKey = self::sortableKey($value);

        if ($value === null || str_starts_with($comparisonKey, '3')) {
            throw new InvalidArgumentException('Exact-decimal comparisons require a valid plain base-10 value.');
        }

        $nativeNumber = self::nativeConfiguredNumber($connection, $wrappedColumn, $configuration);

        if ($nativeNumber !== null) {
            $query->whereRaw(
                "{$nativeNumber['value']} IS NOT NULL AND {$nativeNumber['value']} {$operator} {$nativeNumber['target']}",
                [(string) $value],
            );

            return;
        }

        $key = self::sqlSortableKey($connection, $wrappedColumn, $configuration);
        $query->whereRaw("{$key} IS NOT NULL AND {$key} {$operator} ?", [$comparisonKey]);
    }

    public static function applySorting(
        EloquentBuilder|QueryBuilder $query,
        Connection $connection,
        string $wrappedColumn,
        string $direction,
        ?array $numberConfiguration = null,
    ): void {
        $direction = strtolower($direction);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException("Unsupported exact-decimal sort direction [{$direction}].");
        }

        $configuration = $numberConfiguration === null
            ? null
            : self::validatedConfiguration($numberConfiguration);
        $nativeNumber = self::nativeConfiguredNumber($connection, $wrappedColumn, $configuration);

        if ($nativeNumber !== null) {
            $query->orderByRaw("CASE WHEN {$nativeNumber['value']} IS NULL THEN 1 ELSE 0 END")
                ->orderByRaw("{$nativeNumber['value']} {$direction}");

            return;
        }

        $key = self::sqlSortableKey($connection, $wrappedColumn, $configuration);
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
        $connection->getPdo()->sqliteCreateFunction(
            'aura_number_sort_key',
            self::configuredSortableKey(...),
            4,
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

    /**
     * @param  array{mode: 'decimal'|'integer'|'legacy', precision: int|null, scale: int}  $configuration
     */
    private static function configuredNormalizedValue(mixed $value, array $configuration): int|string|null
    {
        $field = [];

        if ($configuration['mode'] !== 'legacy') {
            $field['number_type'] = $configuration['mode'];
        }

        if ($configuration['precision'] !== null) {
            $field['precision'] = $configuration['precision'];
        }

        if ($configuration['mode'] === 'decimal') {
            $field['scale'] = $configuration['scale'];
        }

        return (new Number)->normalizeForExactQuery($value, $field);
    }

    private static function configuredSortableKey(
        mixed $value,
        string $mode,
        mixed $precision,
        mixed $scale,
    ): string {
        $configuration = self::validatedConfiguration([
            'mode' => $mode,
            'precision' => $precision === null ? null : (int) $precision,
            'scale' => (int) $scale,
        ]);
        $normalized = self::configuredNormalizedValue($value, $configuration);

        return $normalized === null ? '3'.trim((string) $value) : self::sortableKey($normalized);
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

    /**
     * @param  array{mode: 'decimal'|'integer'|'legacy', precision: int|null, scale: int}  $configuration
     */
    private static function mysqlConfiguredValue(string $column, array $configuration): string
    {
        $value = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(CAST({$column} AS CHAR), CHAR(0), ' '), CHAR(9), ' '), CHAR(10), ' '), CHAR(11), ' '), CHAR(13), ' '))";
        $unsigned = "(CASE WHEN LEFT({$value}, 1) IN ('+', '-') THEN SUBSTRING({$value}, 2) ELSE {$value} END)";
        $integerRaw = "SUBSTRING_INDEX({$unsigned}, '.', 1)";
        $fractionRaw = "(CASE WHEN LOCATE('.', {$unsigned}) > 0 THEN SUBSTRING({$unsigned}, LOCATE('.', {$unsigned}) + 1) ELSE '' END)";
        $integer = "COALESCE(NULLIF(TRIM(LEADING '0' FROM {$integerRaw}), ''), '0')";
        $integerDigits = "(CASE WHEN {$integer} = '0' THEN 0 ELSE CHAR_LENGTH({$integer}) END)";
        $digitCount = "({$integerDigits} + CHAR_LENGTH({$fractionRaw}))";
        $plain = "({$value} REGEXP '^[+-]?[0-9]+([.][0-9]+)?$' AND {$value} NOT REGEXP '[^-+0-9.]' AND {$digitCount} <= ".self::MAX_DIGITS.')';
        $hasFraction = "(LOCATE('.', {$unsigned}) > 0)";

        if ($configuration['mode'] === 'legacy') {
            $precision = $configuration['precision'];
            $valid = $precision === null
                ? $plain
                : "({$plain} AND ({$hasFraction} OR CHAR_LENGTH({$integer}) <= {$precision}))";

            return "(CASE WHEN {$valid} THEN {$value} ELSE '' END)";
        }

        if ($configuration['mode'] === 'integer') {
            $precision = $configuration['precision'];
            $valid = "({$plain} AND NOT {$hasFraction}".
                ($precision === null ? '' : " AND CHAR_LENGTH({$integer}) <= {$precision}").')';
            $precision ??= self::MAX_DIGITS;

            return "(CASE WHEN {$valid} THEN CAST({$value} AS DECIMAL({$precision}, 0)) ELSE NULL END)";
        }

        $precision = $configuration['precision'];
        $scale = $configuration['scale'];
        $integerCapacity = $precision - $scale;
        $roundingDigit = "SUBSTRING(RPAD({$fractionRaw}, ".($scale + 1).", '0'), ".($scale + 1).', 1)';
        $keptFraction = $scale === 0 ? "''" : "LEFT(RPAD({$fractionRaw}, {$scale}, '0'), {$scale})";
        $maximumInteger = $integerCapacity === 0 ? '0' : str_repeat('9', $integerCapacity);
        $maximumFraction = $scale === 0 ? '' : str_repeat('9', $scale);
        $roundingOverflow = "({$roundingDigit} IN ('5', '6', '7', '8', '9') AND {$integer} = '{$maximumInteger}' AND {$keptFraction} = '{$maximumFraction}')";
        $valid = "({$plain} AND {$integerDigits} <= {$integerCapacity} AND NOT {$roundingOverflow})";

        return "(CASE WHEN {$valid} THEN CAST({$value} AS DECIMAL({$precision}, {$scale})) ELSE NULL END)";
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

    /**
     * @param  array{mode: 'decimal'|'integer'|'legacy', precision: int|null, scale: int}|null  $configuration
     * @return array{value: string, target: string}|null
     */
    private static function nativeConfiguredNumber(
        Connection $connection,
        string $wrappedColumn,
        ?array $configuration,
    ): ?array {
        if ($configuration === null
            || $configuration['mode'] === 'legacy'
            || ! in_array($connection->getDriverName(), ['mysql', 'pgsql'], true)) {
            return null;
        }

        $precision = $configuration['precision'] ?? self::MAX_DIGITS;
        $scale = $configuration['scale'];

        return match ($connection->getDriverName()) {
            'mysql' => [
                'value' => self::mysqlConfiguredValue($wrappedColumn, $configuration),
                'target' => "CAST(? AS DECIMAL({$precision}, {$scale}))",
            ],
            'pgsql' => [
                'value' => self::postgresConfiguredValue($wrappedColumn, $configuration),
                'target' => "CAST(? AS NUMERIC({$precision}, {$scale}))",
            ],
        };
    }

    /**
     * @param  array{mode: 'decimal'|'integer'|'legacy', precision: int|null, scale: int}  $configuration
     */
    private static function postgresConfiguredValue(string $column, array $configuration): string
    {
        $value = "BTRIM(REPLACE(REPLACE(REPLACE(REPLACE(CAST({$column} AS TEXT), CHR(9), ' '), CHR(10), ' '), CHR(11), ' '), CHR(13), ' '))";
        $unsigned = "(CASE WHEN LEFT({$value}, 1) IN ('+', '-') THEN SUBSTRING({$value} FROM 2) ELSE {$value} END)";
        $integerRaw = "SPLIT_PART({$unsigned}, '.', 1)";
        $fractionRaw = "(CASE WHEN STRPOS({$unsigned}, '.') > 0 THEN SPLIT_PART({$unsigned}, '.', 2) ELSE '' END)";
        $integer = "COALESCE(NULLIF(LTRIM({$integerRaw}, '0'), ''), '0')";
        $integerDigits = "(CASE WHEN {$integer} = '0' THEN 0 ELSE LENGTH({$integer}) END)";
        $digitCount = "({$integerDigits} + LENGTH({$fractionRaw}))";
        $plain = "({$value} ~ '^[+-]?[0-9]+([.][0-9]+)?$' AND {$value} !~ '[^-+0-9.]' AND {$digitCount} <= ".self::MAX_DIGITS.')';
        $hasFraction = "(STRPOS({$unsigned}, '.') > 0)";

        if ($configuration['mode'] === 'legacy') {
            $precision = $configuration['precision'];
            $valid = $precision === null
                ? $plain
                : "({$plain} AND ({$hasFraction} OR LENGTH({$integer}) <= {$precision}))";

            return "(CASE WHEN {$valid} THEN {$value} ELSE '' END)";
        }

        if ($configuration['mode'] === 'integer') {
            $precision = $configuration['precision'];
            $valid = "({$plain} AND NOT {$hasFraction}".
                ($precision === null ? '' : " AND LENGTH({$integer}) <= {$precision}").')';
            $precision ??= self::MAX_DIGITS;

            return "(CASE WHEN {$valid} THEN CAST({$value} AS NUMERIC({$precision}, 0)) ELSE NULL END)";
        }

        $precision = $configuration['precision'];
        $scale = $configuration['scale'];
        $integerCapacity = $precision - $scale;
        $roundingDigit = "SUBSTRING(RPAD({$fractionRaw}, ".($scale + 1).", '0') FROM ".($scale + 1).' FOR 1)';
        $keptFraction = $scale === 0 ? "''" : "LEFT(RPAD({$fractionRaw}, {$scale}, '0'), {$scale})";
        $maximumInteger = $integerCapacity === 0 ? '0' : str_repeat('9', $integerCapacity);
        $maximumFraction = $scale === 0 ? '' : str_repeat('9', $scale);
        $roundingOverflow = "({$roundingDigit} IN ('5', '6', '7', '8', '9') AND {$integer} = '{$maximumInteger}' AND {$keptFraction} = '{$maximumFraction}')";
        $valid = "({$plain} AND {$integerDigits} <= {$integerCapacity} AND NOT {$roundingOverflow})";

        return "(CASE WHEN {$valid} THEN CAST({$value} AS NUMERIC({$precision}, {$scale})) ELSE NULL END)";
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

    /**
     * @param  array{mode: 'decimal'|'integer'|'legacy', precision: int|null, scale: int}|null  $configuration
     */
    private static function sqliteSortableKey(Connection $connection, string $column, ?array $configuration): string
    {
        self::registerSqliteFunction($connection);
        $key = $configuration === null
            ? "aura_decimal_sort_key({$column})"
            : "aura_number_sort_key({$column}, '{$configuration['mode']}', ".($configuration['precision'] ?? 'NULL').", {$configuration['scale']})";

        return "(CASE WHEN SUBSTR({$key}, 1, 1) IN ('0', '1', '2') THEN {$key} ELSE NULL END)";
    }

    /**
     * Valid values become a binary-collated key: reversed negative magnitude,
     * zero, then positive magnitude. Invalid values become NULL. String
     * operations avoid all float and DECIMAL narrowing.
     */
    private static function sqlSortableKey(Connection $connection, string $wrappedColumn, ?array $numberConfiguration): string
    {
        $configuration = $numberConfiguration === null
            ? null
            : self::validatedConfiguration($numberConfiguration);

        if ($configuration !== null
            && $configuration['mode'] === 'legacy'
            && $configuration['precision'] === null) {
            $configuration = null;
        }

        return match ($connection->getDriverName()) {
            'mysql' => self::mysqlSortableKey($configuration === null
                ? $wrappedColumn
                : self::mysqlConfiguredValue($wrappedColumn, $configuration)),
            'pgsql' => self::postgresSortableKey($configuration === null
                ? $wrappedColumn
                : self::postgresConfiguredValue($wrappedColumn, $configuration)),
            'sqlite' => self::sqliteSortableKey($connection, $wrappedColumn, $configuration),
            default => throw new InvalidArgumentException("Exact-decimal queries are not supported for database driver [{$connection->getDriverName()}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array{mode: 'decimal'|'integer'|'legacy', precision: int|null, scale: int}
     */
    private static function validatedConfiguration(array $configuration): array
    {
        $mode = $configuration['mode'] ?? null;
        $precision = $configuration['precision'] ?? null;
        $scale = $configuration['scale'] ?? null;

        if (! in_array($mode, ['decimal', 'integer', 'legacy'], true)
            || ($precision !== null && (! is_int($precision) || $precision < 1 || $precision > self::MAX_DIGITS))
            || ! is_int($scale)
            || $scale < 0
            || $scale > 30
            || ($mode === 'decimal' && ($precision === null || $scale > $precision))
            || ($mode !== 'decimal' && $scale !== 0)) {
            throw new InvalidArgumentException('Invalid exact-number query configuration.');
        }

        return [
            'mode' => $mode,
            'precision' => $precision,
            'scale' => $scale,
        ];
    }
}
