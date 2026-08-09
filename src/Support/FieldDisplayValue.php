<?php

namespace Aura\Base\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use JsonSerializable;
use Stringable;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Traversable;

final class FieldDisplayValue
{
    public static function escape(mixed $value, bool $nested = false): string
    {
        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        } elseif ($value instanceof JsonSerializable) {
            $value = $value->jsonSerialize();
        } elseif ($value instanceof Traversable) {
            $value = iterator_to_array($value);
        }

        if (is_array($value)) {
            $items = array_map(
                fn (mixed $item): string => self::escape($item, nested: true),
                array_values($value),
            );
            $formatted = implode(', ', $items);

            return $nested ? '['.$formatted.']' : $formatted;
        }

        if ($value === null) {
            return '';
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            return e((string) $value);
        }

        return e(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }

    public static function sanitizedHtml(string $html): Htmlable
    {
        $sanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig)->allowSafeElements(),
        );

        return new HtmlString($sanitizer->sanitize($html));
    }

    /**
     * Convert untrusted display output into a value safe for raw Blade slots.
     * Htmlable is the only bypass; every other leaf is escaped as plain text.
     */
    public static function secure(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if ($value instanceof Htmlable) {
            return $value;
        }

        return new HtmlString(self::escape($value));
    }
}
