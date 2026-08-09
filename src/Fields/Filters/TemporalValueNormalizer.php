<?php

namespace Aura\Base\Fields\Filters;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Throwable;

final class TemporalValueNormalizer
{
    public function normalize(
        mixed $value,
        bool $includeTime,
        ?string $storageFormat = null,
        ?string $timezone = null,
    ): ?string {
        $dateTimezone = $includeTime ? $this->timezone($timezone) : null;

        if ($includeTime && $dateTimezone === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            $date = DateTimeImmutable::createFromInterface($value);

            if ($dateTimezone !== null) {
                $date = $date->setTimezone($dateTimezone);
            }

            $canonical = $date->format($includeTime ? 'Y-m-d H:i:s' : 'Y-m-d');

            if ($includeTime && $dateTimezone !== null && $this->isDstTransitionWallTime($canonical, $dateTimezone)) {
                return null;
            }

            return $canonical;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $formats = $includeTime
            ? ['Y-m-d\\TH:i:s', 'Y-m-d\\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i', $storageFormat]
            : ['Y-m-d', $storageFormat];

        foreach (array_unique(array_filter($formats, 'is_string')) as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value, $dateTimezone);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format($format) !== $value) {
                continue;
            }

            $canonical = $date->format($includeTime ? 'Y-m-d H:i:s' : 'Y-m-d');

            if ($includeTime && $dateTimezone !== null && $this->isDstTransitionWallTime($canonical, $dateTimezone)) {
                return null;
            }

            return $canonical;
        }

        return null;
    }

    public function unixTimestamp(string $canonical, string $timezone): ?int
    {
        $dateTimezone = $this->timezone($timezone);

        if ($dateTimezone === null || $this->isDstTransitionWallTime($canonical, $dateTimezone)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $canonical, $dateTimezone);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d H:i:s') !== $canonical) {
            return null;
        }

        return $date->getTimestamp();
    }

    private function isDstTransitionWallTime(string $canonical, DateTimeZone $timezone): bool
    {
        $wallTime = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $canonical,
            new DateTimeZone('UTC'),
        );

        if ($wallTime === false) {
            return true;
        }

        $wallTimestamp = $wallTime->getTimestamp();
        $transitions = $timezone->getTransitions($wallTimestamp - 172800, $wallTimestamp + 172800);

        if (! is_array($transitions) || count($transitions) < 2) {
            return false;
        }

        $previousOffset = $transitions[0]['offset'];

        foreach (array_slice($transitions, 1) as $transition) {
            $currentOffset = $transition['offset'];

            if ($currentOffset !== $previousOffset) {
                $transitionStart = $transition['ts'] + min($previousOffset, $currentOffset);
                $transitionEnd = $transition['ts'] + max($previousOffset, $currentOffset);

                if ($wallTimestamp >= $transitionStart && $wallTimestamp < $transitionEnd) {
                    return true;
                }
            }

            $previousOffset = $currentOffset;
        }

        return false;
    }

    private function timezone(?string $timezone): ?DateTimeZone
    {
        $timezone ??= date_default_timezone_get();

        try {
            return new DateTimeZone($timezone);
        } catch (Throwable) {
            return null;
        }
    }
}
