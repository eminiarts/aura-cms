<?php

namespace Aura\Base\Support;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Contracts\FieldValueStorage;
use Aura\Base\Exceptions\InvalidFieldValue;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
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
            self::timezone($field['input_timezone'] ?? null, self::applicationTimezone(), $field, 'input timezone'),
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
            self::timezone($field['input_timezone'] ?? null, self::applicationTimezone(), $field, 'input timezone'),
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

        $datetime = self::parseStoredDatetime($value, $field);

        if (! $datetime) {
            return $value;
        }

        // Presentation contexts keep the instant attached to the value. A
        // formatted wall-clock string loses which side of a DST overlap it
        // came from and can therefore render with the wrong offset later.
        if (in_array($context, [FieldValueContext::Export, FieldValueContext::Index, FieldValueContext::View], true)) {
            return $datetime;
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
            self::timezone($field['input_timezone'] ?? null, self::applicationTimezone(), $field, 'input timezone'),
        );

        return $date?->format('Y-m-d') ?? $value;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public static function normalizeDatetime(
        mixed $value,
        array $field,
        ?Model $model = null,
        FieldValueStorage $storage = FieldValueStorage::Meta,
    ): mixed {
        if ($value === null || $value === '') {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            $datetime = CarbonImmutable::instance($value);
        } elseif (is_string($value)) {
            $input = trim($value);
            $datetime = self::parseExplicitOffsetDatetime($input, $field)
                ?? self::resolveLocalDatetime($input, $field, $model, $storage);
        } else {
            throw InvalidFieldValue::forField($field['slug'] ?? null, 'expected a datetime string or DateTimeInterface instance');
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
            'Y-m-d\TH:iP',
            'Y-m-d\TH:i:sO',
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
            $field,
            'display timezone',
        );
    }

    private static function formatContainsTimezone(string $format): bool
    {
        $escaped = false;

        foreach (str_split($format) as $character) {
            if ($escaped) {
                $escaped = false;

                continue;
            }

            if ($character === '\\') {
                $escaped = true;

                continue;
            }

            if (str_contains('eIOPTZ', $character)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function inputTimezone(array $field): DateTimeZone
    {
        return self::timezone(
            $field['input_timezone'] ?? null,
            self::displayTimezone($field)->getName(),
            $field,
            'input timezone',
        );
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private static function localDatetimeCandidates(CarbonImmutable $wallClock, DateTimeZone $timezone): array
    {
        $nominalTimestamp = $wallClock->getTimestamp();
        $transitions = $timezone->getTransitions($nominalTimestamp - 172800, $nominalTimestamp + 172800);
        $offsets = [];

        if (is_array($transitions)) {
            foreach ($transitions as $transition) {
                $offsets[(int) $transition['offset']] = (int) $transition['offset'];
            }
        }

        if ($offsets === []) {
            $reference = (new DateTimeImmutable('@'.$nominalTimestamp))->setTimezone($timezone);
            $offset = $timezone->getOffset($reference);
            $offsets[$offset] = $offset;
        }

        $wallClockKey = $wallClock->format('Y-m-d H:i:s.u');
        $microseconds = (int) $wallClock->format('u');
        $candidates = [];

        foreach ($offsets as $offset) {
            $candidate = CarbonImmutable::createFromTimestampUTC($nominalTimestamp - $offset)
                ->setMicrosecond($microseconds)
                ->setTimezone($timezone);

            if ($candidate->format('Y-m-d H:i:s.u') === $wallClockKey) {
                $candidates[$candidate->getTimestamp()] = $candidate;
            }
        }

        ksort($candidates);

        return array_values($candidates);
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function originalStoredDatetime(
        ?Model $model,
        array $field,
        FieldValueStorage $storage,
    ): ?CarbonImmutable {
        $slug = $field['slug'] ?? null;

        if (! $model || ! is_string($slug) || $slug === '') {
            return null;
        }

        $original = null;

        if ($storage === FieldValueStorage::Physical) {
            $original = $model->getRawOriginal($slug);
        } elseif ($model->relationLoaded('meta')) {
            $meta = collect($model->getRelation('meta'))->first(
                fn (mixed $item): bool => data_get($item, 'key') === $slug,
            );

            if ($meta instanceof Model) {
                $original = $meta->getRawOriginal('value');
            } else {
                $original = data_get($meta, 'value');
            }
        }

        if ($original === null || $original === '') {
            return null;
        }

        return self::parseStoredDatetime($original, $field);
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
    private static function parseExplicitOffsetDatetime(string $value, array $field): ?CarbonImmutable
    {
        if (preg_match('/(?:Z|[+-]\d{2}:?\d{2})$/i', $value) !== 1) {
            return null;
        }

        if (str_ends_with(strtoupper($value), 'Z')) {
            $value = substr($value, 0, -1).'+00:00';
        }

        $formats = array_values(array_filter(
            self::datetimeFormats($field),
            self::formatContainsTimezone(...),
        ));

        return self::parseStrict($value, $formats, self::inputTimezone($field));
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function parseStoredDatetime(mixed $value, array $field): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (! is_string($value)) {
            return null;
        }

        $explicit = self::parseExplicitOffsetDatetime($value, $field);

        if ($explicit) {
            return $explicit;
        }

        $wallClock = self::parseStrict(
            $value,
            array_values(array_filter(
                self::datetimeFormats($field),
                fn (string $format): bool => ! self::formatContainsTimezone($format),
            )),
            new DateTimeZone('UTC'),
        );

        if (! $wallClock) {
            return null;
        }

        $candidates = self::localDatetimeCandidates($wallClock, self::storageTimezone($field));

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    /**
     * @param  array<int, string>  $formats
     */
    private static function parseStrict(string $value, array $formats, DateTimeZone $timezone): ?CarbonImmutable
    {
        foreach ($formats as $format) {
            try {
                $parsed = CarbonImmutable::createFromFormat('!'.$format, $value, $timezone);
                $errors = CarbonImmutable::getLastErrors();

                if ($parsed
                    && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                    && $parsed->format($format) === $value) {
                    return $parsed;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Resolve an offset-less wall clock without relying on PHP's DST guessing.
     *
     * @param  array<string, mixed>  $field
     */
    private static function resolveLocalDatetime(
        string $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
    ): CarbonImmutable {
        $formats = array_values(array_filter(
            self::datetimeFormats($field),
            fn (string $format): bool => ! self::formatContainsTimezone($format),
        ));
        $wallClock = self::parseStrict($value, $formats, new DateTimeZone('UTC'));

        if (! $wallClock) {
            throw InvalidFieldValue::forField($field['slug'] ?? null, 'the datetime format is not recognized');
        }

        $timezone = self::inputTimezone($field);
        $candidates = self::localDatetimeCandidates($wallClock, $timezone);

        if ($candidates === []) {
            throw InvalidFieldValue::forField($field['slug'] ?? null, "the local time does not exist in {$timezone->getName()}");
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        $original = self::originalStoredDatetime($model, $field, $storage);

        if ($original) {
            $originalTimestamp = $original->getTimestamp();
            $wallClockKey = $wallClock->format('Y-m-d H:i:s.u');
            $originalWallClockKey = $original->setTimezone($timezone)->format('Y-m-d H:i:s.u');

            if ($wallClockKey === $originalWallClockKey) {
                foreach ($candidates as $candidate) {
                    if ($candidate->getTimestamp() === $originalTimestamp) {
                        return $candidate;
                    }
                }
            }
        }

        throw InvalidFieldValue::forField(
            $field['slug'] ?? null,
            "the local time is ambiguous in {$timezone->getName()}; submit an explicit UTC offset",
        );
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
            $field,
            'storage timezone',
        );
    }

    /** @param  array<string, mixed>  $field */
    private static function timezone(
        mixed $configured,
        string $fallback,
        array $field,
        string $setting,
    ): DateTimeZone {
        $timezone = is_string($configured) && $configured !== '' ? $configured : $fallback;

        try {
            return new DateTimeZone($timezone);
        } catch (Throwable) {
            throw InvalidFieldValue::forField(
                $field['slug'] ?? null,
                "configured {$setting} [{$timezone}] is not a valid timezone",
            );
        }
    }
}
