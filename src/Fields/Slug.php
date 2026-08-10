<?php

namespace Aura\Base\Fields;

class Slug extends Field
{
    /** ECMAScript String.prototype.trim whitespace and line terminators. */
    private const ECMASCRIPT_TRIM_PATTERN = '/^[\x{0009}-\x{000D}\x{0020}\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]+|[\x{0009}-\x{000D}\x{0020}\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]+$/u';

    public $edit = 'aura::fields.slug';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    public function deriveValue(mixed $value): string
    {
        if (! is_scalar($value) && ! $value instanceof \Stringable) {
            return '';
        }

        $normalized = \Normalizer::normalize((string) $value, \Normalizer::FORM_D);
        $normalized = preg_replace('/[\x{0300}-\x{036f}]/u', '', $normalized === false ? '' : $normalized) ?? '';
        $normalized = strtolower($normalized);
        $normalized = preg_replace(self::ECMASCRIPT_TRIM_PATTERN, '', $normalized) ?? '';

        return preg_replace('/[^A-Za-z0-9_]+/u', '_', $normalized) ?? '';
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Slug',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'slug-tab',
                'style' => [],
            ],
            [
                'name' => 'Based on',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'instructions' => 'Based on this field',
                'slug' => 'based_on',
            ],
            [
                'name' => 'Custom',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'If you want to allow a custom slug',
                'slug' => 'custom',
            ],
            [
                'name' => 'Disabled',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'instructions' => 'If you want to show the slug field as disabled',
                'slug' => 'disabled',
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'slug' => 'default',
            ],
            [
                'name' => 'Placeholder',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'placeholder',
            ],
        ]);
    }
}
