<?php

namespace Aura\Base\GlobalSearch;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class GlobalSearchIconSanitizer
{
    private const DEFAULT_MAXIMUM_BYTES = 8_192;

    private const HARD_MAXIMUM_BYTES = 32_768;

    public function sanitize(string $icon): string
    {
        $maximumBytes = $this->maximumBytes();
        $config = (new HtmlSanitizerConfig)
            ->allowElement('svg', [
                'aria-hidden',
                'fill',
                'height',
                'role',
                'stroke',
                'stroke-linecap',
                'stroke-linejoin',
                'stroke-width',
                'viewbox',
                'width',
                'xmlns',
            ])
            ->allowElement('g', ['fill', 'stroke', 'stroke-width', 'transform'])
            ->allowElement('path', [
                'clip-rule',
                'd',
                'fill',
                'fill-rule',
                'stroke',
                'stroke-linecap',
                'stroke-linejoin',
                'stroke-width',
                'transform',
            ])
            ->allowElement('circle', ['cx', 'cy', 'fill', 'r', 'stroke', 'stroke-width'])
            ->allowElement('ellipse', ['cx', 'cy', 'fill', 'rx', 'ry', 'stroke', 'stroke-width'])
            ->allowElement('line', ['stroke', 'stroke-linecap', 'stroke-width', 'x1', 'x2', 'y1', 'y2'])
            ->allowElement('polygon', ['fill', 'points', 'stroke', 'stroke-width'])
            ->allowElement('polyline', ['fill', 'points', 'stroke', 'stroke-width'])
            ->allowElement('rect', ['fill', 'height', 'rx', 'ry', 'stroke', 'stroke-width', 'width', 'x', 'y'])
            ->withMaxInputLength($maximumBytes);
        $sanitized = trim((new HtmlSanitizer($config))->sanitize($icon));
        $sanitized = preg_replace_callback(
            '/\s(?:fill|stroke)="([^"]*)"/i',
            fn (array $matches): string => $this->isSafePaintValue($matches[1]) ? $matches[0] : '',
            $sanitized,
        );

        if (! is_string($sanitized) || $sanitized === '' || strlen($sanitized) > $maximumBytes) {
            return '';
        }

        return $sanitized;
    }

    private function isSafePaintValue(string $value): bool
    {
        $decoded = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return preg_match(
            '/\A(?:none|currentcolor|transparent|inherit|#[0-9a-f]{3,8}|[a-z]+|(?:rgb|rgba|hsl|hsla)\([0-9.,%+\-\/\s]+\))\z/i',
            $decoded,
        ) === 1;
    }

    private function maximumBytes(): int
    {
        $configured = config('aura.global_search.icon_bytes', self::DEFAULT_MAXIMUM_BYTES);
        $value = is_numeric($configured) ? (int) $configured : self::DEFAULT_MAXIMUM_BYTES;

        return min(max($value, 256), self::HARD_MAXIMUM_BYTES);
    }
}
