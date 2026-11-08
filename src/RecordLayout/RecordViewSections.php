<?php

namespace Aura\Base\RecordLayout;

use Aura\Base\Fields\Panel;

/**
 * Arranges a record's view-field tree for the record page: the record's own
 * values go into the details card, embedded record lists (HasMany-style
 * tables) become related sections beside it. Values the page header already
 * shows are dropped: the field whose value is the record title, and the
 * panel heading when the details hold a single panel.
 *
 * Trees with other groups (tabs) are returned unchanged with no related
 * sections, so custom layouts keep rendering as before.
 */
final class RecordViewSections
{
    private const RELATED_LIST_VIEW = 'aura::fields.has-many';

    /**
     * @param  array<int, array<string, mixed>>  $fields  Output of Resource::viewFields().
     * @return array{details: array<int, array<string, mixed>>, related: array<int, array<string, mixed>>}
     */
    public static function split(array $fields, $model): array
    {
        $title = trim((string) $model->title());
        $details = [];
        $related = [];

        foreach ($fields as $field) {
            $instance = $field['field'] ?? null;

            if ($instance instanceof Panel) {
                $children = [];

                foreach ($field['fields'] ?? [] as $child) {
                    if (self::isRelatedList($child)) {
                        $related[] = $child;
                    } elseif (self::isGroup($child)) {
                        return ['details' => $fields, 'related' => []];
                    } elseif (! self::repeatsTitle($child, $model, $title)) {
                        $children[] = $child;
                    }
                }

                if ($children !== []) {
                    $field['fields'] = $children;
                    $details[] = $field;
                }

                continue;
            }

            if (self::isRelatedList($field)) {
                $related[] = $field;
            } elseif (self::isGroup($field)) {
                return ['details' => $fields, 'related' => []];
            } elseif (! self::repeatsTitle($field, $model, $title)) {
                $details[] = $field;
            }
        }

        $panels = array_keys(array_filter($details, fn ($field) => ($field['field'] ?? null) instanceof Panel));

        if (count($panels) === 1) {
            $details[$panels[0]]['hide_title'] = true;
        }

        return ['details' => $details, 'related' => $related];
    }

    private static function isGroup(array $field): bool
    {
        $instance = $field['field'] ?? null;

        return ($instance !== null && ($instance->group ?? false) === true) || ! empty($field['fields']);
    }

    private static function isRelatedList(array $field): bool
    {
        $instance = $field['field'] ?? null;

        return $instance !== null && method_exists($instance, 'view') && $instance->view() === self::RELATED_LIST_VIEW;
    }

    private static function repeatsTitle(array $field, $model, string $title): bool
    {
        if ($title === '' || empty($field['slug']) || ($field['field_type'] ?? null) !== 'input') {
            return false;
        }

        $value = $model->display($field['slug']);

        if (! is_scalar($value)) {
            return false;
        }

        return trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES)) === $title;
    }
}
