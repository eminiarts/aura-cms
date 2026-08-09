<?php

namespace Aura\Base\Schema;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class ColumnValuePreflight
{
    /** @var array<string, array{0: string, 1: string}> */
    private const INTEGER_BOUNDS = [
        'tinyInteger' => ['-128', '127'],
        'unsignedTinyInteger' => ['0', '255'],
        'smallInteger' => ['-32768', '32767'],
        'unsignedSmallInteger' => ['0', '65535'],
        'mediumInteger' => ['-8388608', '8388607'],
        'unsignedMediumInteger' => ['0', '16777215'],
        'integer' => ['-2147483648', '2147483647'],
        'unsignedInteger' => ['0', '4294967295'],
        'bigInteger' => ['-9223372036854775808', '9223372036854775807'],
        'unsignedBigInteger' => ['0', '18446744073709551615'],
    ];

    public static function assertTableColumnCanConvert(string $table, string $column, FieldColumn $target): void
    {
        if (! Schema::hasColumn($table, $column)) {
            throw new RuntimeException("Cannot preflight missing column {$table}.{$column}.");
        }

        $target = $target->forDriver(Schema::getConnection()->getDriverName());

        foreach (DB::table($table)->whereNotNull($column)->select($column)->cursor() as $row) {
            self::assertValueFits($row->{$column}, $target, "{$table}.{$column}");
        }
    }

    public static function assertValueFits(mixed $value, FieldColumn $target, string $label = 'column'): void
    {
        if ($value === null) {
            if (! $target->nullable) {
                throw new RuntimeException("Refusing lossy conversion of {$label}: NULL is not allowed.");
            }

            return;
        }

        if (isset(self::INTEGER_BOUNDS[$target->type])) {
            [$minimum, $maximum] = self::INTEGER_BOUNDS[$target->type];
            $integer = self::exactInteger($value, $label);

            if (self::compareIntegers($integer, $minimum) < 0 || self::compareIntegers($integer, $maximum) > 0) {
                throw new RuntimeException("Refusing lossy conversion of {$label}: value [{$integer}] is outside {$target->type} bounds.");
            }

            return;
        }

        if (in_array($target->type, ['decimal', 'unsignedDecimal'], true)) {
            self::assertDecimalFits($value, $target, $label);

            return;
        }

        if (in_array($target->type, ['boolean', 'tinyInteger'], true)
            && ! in_array($value, [0, 1, '0', '1', false, true], true)) {
            throw new RuntimeException("Refusing lossy conversion of {$label}: value is not boolean-representable.");
        }

        if (in_array($target->type, ['string', 'char'], true)) {
            $limit = (int) ($target->arguments[0] ?? 255);

            if (mb_strlen((string) $value) > $limit) {
                throw new RuntimeException("Refusing lossy conversion of {$label}: value exceeds {$limit} characters.");
            }

            return;
        }

        if ($target->type === 'date') {
            self::assertMatchesDateFormat($value, 'Y-m-d', $label, 'date');

            return;
        }

        if (in_array($target->type, ['dateTime', 'dateTimeTz', 'timestamp', 'timestampTz'], true)) {
            self::assertMatchesAnyDateFormat(
                $value,
                ['Y-m-d H:i:s', 'Y-m-d H:i:s.u', 'Y-m-d H:i:sP', 'Y-m-d H:i:s.uP'],
                $label,
                'datetime',
            );

            return;
        }

        if (in_array($target->type, ['time', 'timeTz'], true)) {
            self::assertMatchesAnyDateFormat($value, ['H:i:s', 'H:i:s.u', 'H:i:sP', 'H:i:s.uP'], $label, 'time');
        }
    }

    private static function assertDecimalFits(mixed $value, FieldColumn $target, string $label): void
    {
        $string = self::plainNumber($value, $label);

        if (preg_match('/^(?<sign>[+-]?)(?<integer>\d+)(?:\.(?<fraction>\d+))?$/D', $string, $matches) !== 1) {
            throw new RuntimeException("Refusing lossy conversion of {$label}: value [{$string}] is not an exact decimal.");
        }

        if ($target->type === 'unsignedDecimal' && ($matches['sign'] ?? '') === '-') {
            throw new RuntimeException("Refusing lossy conversion of {$label}: negative value [{$string}] is unsigned.");
        }

        $precision = (int) ($target->arguments[0] ?? 8);
        $scale = (int) ($target->arguments[1] ?? 2);
        $integer = ltrim($matches['integer'], '0');
        $fraction = $matches['fraction'] ?? '';

        if (strlen($integer) > $precision - $scale
            || (strlen($fraction) > $scale && trim(substr($fraction, $scale), '0') !== '')) {
            throw new RuntimeException("Refusing lossy conversion of {$label}: value [{$string}] does not fit decimal({$precision}, {$scale}).");
        }
    }

    /**
     * @param  array<int, string>  $formats
     */
    private static function assertMatchesAnyDateFormat(mixed $value, array $formats, string $label, string $type): void
    {
        foreach ($formats as $format) {
            if (self::matchesDateFormat($value, $format)) {
                return;
            }
        }

        $displayValue = is_scalar($value) ? (string) $value : get_debug_type($value);

        throw new RuntimeException("Refusing lossy conversion of {$label}: value [{$displayValue}] is not a valid {$type}.");
    }

    private static function assertMatchesDateFormat(mixed $value, string $format, string $label, string $type): void
    {
        if (self::matchesDateFormat($value, $format)) {
            return;
        }

        $displayValue = is_scalar($value) ? (string) $value : get_debug_type($value);

        throw new RuntimeException("Refusing lossy conversion of {$label}: value [{$displayValue}] is not a valid {$type}.");
    }

    private static function compareIntegers(string $left, string $right): int
    {
        $leftNegative = str_starts_with($left, '-');
        $rightNegative = str_starts_with($right, '-');

        if ($leftNegative !== $rightNegative) {
            return $leftNegative ? -1 : 1;
        }

        $leftDigits = ltrim($left, '-');
        $rightDigits = ltrim($right, '-');
        $comparison = strlen($leftDigits) <=> strlen($rightDigits);

        if ($comparison === 0) {
            $comparison = strcmp($leftDigits, $rightDigits) <=> 0;
        }

        return $leftNegative ? -$comparison : $comparison;
    }

    private static function exactInteger(mixed $value, string $label): string
    {
        $string = self::plainNumber($value, $label);

        if (preg_match('/^(?<sign>[+-]?)(?<integer>\d+)(?:\.(?<fraction>\d+))?$/D', $string, $matches) !== 1
            || (isset($matches['fraction']) && trim($matches['fraction'], '0') !== '')) {
            throw new RuntimeException("Refusing lossy conversion of {$label}: value [{$string}] is not an exact integer.");
        }

        $integer = ltrim($matches['integer'], '0');
        $integer = $integer === '' ? '0' : $integer;

        return ($matches['sign'] ?? '') === '-' && $integer !== '0' ? '-'.$integer : $integer;
    }

    private static function matchesDateFormat(mixed $value, string $format): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format($format) === $value;
    }

    private static function plainNumber(mixed $value, string $label): string
    {
        if (! is_int($value) && ! is_string($value)) {
            throw new RuntimeException("Refusing lossy conversion of {$label}: the database returned a non-exact numeric value.");
        }

        $string = trim((string) $value);

        if ($string === '' || str_contains(strtolower($string), 'e')) {
            throw new RuntimeException("Refusing lossy conversion of {$label}: value [{$string}] is not plain decimal notation.");
        }

        return $string;
    }
}
