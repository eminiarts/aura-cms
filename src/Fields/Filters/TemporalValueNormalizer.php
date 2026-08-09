<?php

namespace Aura\Base\Fields\Filters;

use DateTimeImmutable;
use DateTimeInterface;

final class TemporalValueNormalizer
{
    public function normalize(mixed $value, bool $includeTime, ?string $storageFormat = null): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format($includeTime ? 'Y-m-d H:i:s' : 'Y-m-d');
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $formats = $includeTime
            ? ['Y-m-d\\TH:i:s', 'Y-m-d\\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i', $storageFormat]
            : ['Y-m-d', $storageFormat];

        foreach (array_unique(array_filter($formats, 'is_string')) as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format($format) !== $value) {
                continue;
            }

            return $date->format($includeTime ? 'Y-m-d H:i:s' : 'Y-m-d');
        }

        return null;
    }
}
