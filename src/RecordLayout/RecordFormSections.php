<?php

namespace Aura\Base\RecordLayout;

use Aura\Base\Fields\Panel;

/**
 * Arranges a create/edit field tree for the form page. A simple form (plain
 * inputs, or one full-width panel holding them) is a single section: the page
 * header already names the record, so the panel drops its heading (the same
 * rule as RecordViewSections), and the page keeps the form at a readable width
 * instead of stretching short inputs across the content area.
 *
 * Forms with several panels, side-by-side panels or tabs are returned unchanged.
 * `card` marks a simple form of plain inputs without a panel: the page puts them in
 * the same card a panel would give them, so every form reads as one.
 */
final class RecordFormSections
{
    /**
     * @param  array<int|string, array<string, mixed>>  $fields  Output of Resource::editFields() / createFields().
     * @return array{fields: array<int|string, array<string, mixed>>, single: bool, card: bool}
     */
    public static function arrange(array $fields): array
    {
        $panels = [];

        foreach ($fields as $key => $field) {
            if (($field['field'] ?? null) instanceof Panel) {
                $panels[] = $key;
            } elseif (self::isGroup($field)) {
                return ['fields' => $fields, 'single' => false, 'card' => false];
            }
        }

        if (count($panels) > 1) {
            return ['fields' => $fields, 'single' => false, 'card' => false];
        }

        if ($panels !== []) {
            $panel = $fields[$panels[0]];

            if (count($fields) > 1 || (string) ($panel['style']['width'] ?? '100') !== '100') {
                return ['fields' => $fields, 'single' => false, 'card' => false];
            }

            $fields[$panels[0]]['hide_title'] = true;
        }

        return ['fields' => $fields, 'single' => $fields !== [], 'card' => $panels === [] && $fields !== []];
    }

    /**
     * A resource name as it reads inside a sentence ("New deal", "Edit company"):
     * lower case, acronyms kept ("New CRM note").
     */
    public static function inSentence(string $name): string
    {
        return collect(preg_split('/\s+/u', trim($name)))
            ->map(fn ($word) => mb_strlen($word) > 1 && mb_strtoupper($word) === $word ? $word : mb_strtolower($word))
            ->implode(' ');
    }

    private static function isGroup(array $field): bool
    {
        $instance = $field['field'] ?? null;

        return ($instance !== null && ($instance->group ?? false) === true) || ! empty($field['fields']);
    }
}
