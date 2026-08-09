<?php

namespace Aura\Base\Support;

use Aura\Base\Contracts\FieldValueContext;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Throwable;

class TemporalValue
{
    /**
     * @param  array<string, mixed>  $field
     */
    public static function displayDate(mixed $value, array $field): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $date = self::parse(
            $value,
            self::dateFormats($field),
            self::timezone($field['input_timezone'] ?? null, self::applicationTimezone()),
        );

        if (! $date) {
            return is_scalar($value) ? (string) $value : '';
        }

        return $date->format((string) ($field['display_format'] ?? config('aura.fields.date.display_format', 'd.m.Y')));
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public static function displayDatetime(mixed $value, array $field, bool $hydrated = false): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $displayTimezone = self::displayTimezone($field);
        $sourceTimezone = $hydrated
            ? $displayTimezone
            : self::storageTimezone($field);
        $datetime = self::parse($value, self::datetimeFormats($field), $sourceTimezone);

        if (! $datetime) {
            return is_scalar($value) ? (string) $value : '';
        }

        return $datetime
            ->setTimezone($displayTimezone)
            ->format((string) ($field['display_format'] ?? config('aura.fields.datetime.display_format', 'd.m.Y H:i')));
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public static function hydrateDate(mixed $value, array $field, FieldValueContext $context): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $date = self::parse(
            $value,
            self::dateFormats($field),
            self::timezone($field['input_timezone'] ?? null, self::applicationTimezone()),
        );

        if (! $date) {
            return $value;
        }

        if (in_array($context, [FieldValueContext::Create, FieldValueContext::Edit], true)) {
            return $date->format((string) ($field['format'] ?? 'Y-m-d'));
        }

        return $date->format('Y-m-d');
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public static function hydrateDatetime(mixed $value, array $field, FieldValueContext $context): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $datetime = self::parse($value, self::datetimeFormats($field), self::storageTimezone($field));

        if (! $datetime) {
            return $value;
        }

        $datetime = $datetime->setTimezone(self::displayTimezone($field));

        if (in_array($context, [FieldValueContext::Create, FieldValueContext::Edit], true)) {
            return $datetime->format((string) ($field['format'] ?? 'Y-m-d H:i'));
        }

        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public static function normalizeDate(mixed $value, array $field): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $date = self::parse(
            $value,
            self::dateFormats($field),
            self::timezone($field['input_timezone'] ?? null, self::applicationTimezone()),
        );

        return $date?->format('Y-m-d') ?? $value;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public static function normalizeDatetime(mixed $value, array $field): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $datetime = self::parse($value, self::datetimeFormats($field), self::inputTimezone($field));

        if (! $datetime) {
            return $value;
        }

        return $datetime
            ->setTimezone(self::storageTimezone($field))
            ->format('Y-m-d H:i:s');
    }

    private static function applicationTimezone(): string
    {
        $timezone = config('app.timezone', 'UTC');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, string>
     */
    private static function dateFormats(array $field): array
    {
        return array_values(array_unique(array_filter([
            is_string($field['format'] ?? null) ? $field['format'] : null,
            'Y-m-d',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd.m.Y',
        ])));
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, string>
     */
    private static function datetimeFormats(array $field): array
    {
        return array_values(array_unique(array_filter([
            is_string($field['format'] ?? null) ? $field['format'] : null,
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i:sP',
            'Y-m-d\TH:i:s.uP',
            'd.m.Y H:i:s',
            'd.m.Y H:i',
        ])));
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function displayTimezone(array $field): DateTimeZone
    {
        $configuredDefault = config('aura.fields.datetime.display_timezone');

        return self::timezone(
            $field['display_timezone'] ?? null,
            is_string($configuredDefault) && $configuredDefault !== ''
                ? $configuredDefault
                : self::applicationTimezone(),
        );
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function inputTimezone(array $field): DateTimeZone
    {
        return self::timezone($field['input_timezone'] ?? null, self::displayTimezone($field)->getName());
    }

    /**
     * @param  array<int, string>  $formats
     */
    private static function parse(mixed $value, array $formats, DateTimeZone $timezone): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (! is_string($value)) {
            return null;
        }

        foreach ($formats as $format) {
            try {
                $parsed = CarbonImmutable::createFromFormat('!'.$format, $value, $timezone);
                $errors = CarbonImmutable::getLastErrors();

                if ($parsed && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                    return $parsed;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function storageTimezone(array $field): DateTimeZone
    {
        $configuredDefault = config('aura.fields.datetime.storage_timezone');

        return self::timezone(
            $field['storage_timezone'] ?? null,
            is_string($configuredDefault) && $configuredDefault !== ''
                ? $configuredDefault
                : self::applicationTimezone(),
        );
    }

    private static function timezone(mixed $configured, string $fallback): DateTimeZone
    {
        try {
            return new DateTimeZone(is_string($configured) && $configured !== '' ? $configured : $fallback);
        } catch (Throwable) {
            return new DateTimeZone('UTC');
        }
    }
}
