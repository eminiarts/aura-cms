<?php

namespace Aura\Base\RecordLayout;

use Aura\Base\Fields\BelongsTo;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Panel;
use Aura\Base\Fields\Status;
use Aura\Base\Resource;

/**
 * Arranges a record's view-field tree for the record page: the record's own
 * values go into the details column, embedded record lists (HasMany-style
 * tables) become related sections beside it. The field whose value is the
 * record title is dropped (the header shows it), and a single panel is
 * retitled "Details" since its name usually repeats the resource name.
 *
 * Trees with other groups (tabs) are returned unchanged with no related
 * sections, no header facts and no cards, so custom layouts keep rendering
 * as before.
 *
 * A record with nothing beside its values (no embedded lists, no relation
 * cards, no rail panel) does not use the record layout at all: its view
 * takes its form's frame (formFrame()).
 */
final class RecordViewSections
{
    private const CARD_FACT_TYPES = ['Text', 'Email', 'Phone', 'Number', 'Date', 'Datetime', 'Time', 'Select', 'Status', 'Boolean', 'Slug', 'Tags'];

    private const CARD_FACTS = 3;

    private const RELATED_LIST_VIEW = 'aura::fields.has-many';

    /**
     * Related-record cards for the BelongsTo values of a record that has no
     * embedded lists: the related record, and up to three of its own list
     * values (its title and relations excluded) to give the card substance.
     *
     * @param  array<int, array<string, mixed>>  $fields  Output of Resource::viewFields().
     * @return array<int, array{field: array<string, mixed>, record: resource, facts: array<int, array{name: string, value: mixed}>}>
     */
    public static function cards(array $fields, $model): array
    {
        $flat = self::flatten($fields);

        if ($flat === null) {
            return [];
        }

        $cards = [];

        foreach ($flat as $field) {
            if (! (($field['field'] ?? null) instanceof BelongsTo) || empty($field['resource']) || empty($field['slug'])) {
                continue;
            }

            $id = $model->{$field['slug']} ?? null;

            if (is_array($id)) {
                $id = $id[0] ?? null;
            }

            $related = ($id !== null && $id !== '' && class_exists($field['resource'])) ? $field['resource']::find($id) : null;

            if (! $related instanceof Resource) {
                continue;
            }

            $cards[] = [
                'field' => $field,
                'record' => $related,
                'facts' => self::cardFacts($related),
            ];
        }

        return $cards;
    }

    /**
     * The form's frame for a record view that has nothing beside its values:
     * no embedded lists, no relation cards and no rail panel. Trees with tabs
     * qualify (their lists stay inside the tabs). The view page then mirrors
     * the edit form: RecordFormSections::arrange() of the view tree decides the
     * page width, the lone panel's title and the card around plain values, the
     * same as on the form. Null when the record page keeps its own layout.
     *
     * @param  array<int, array<string, mixed>>  $fields  Output of Resource::viewFields().
     * @return array{fields: array<int|string, array<string, mixed>>, single: bool, card: bool}|null
     */
    public static function formFrame(array $fields, $model): ?array
    {
        $sections = self::partition($fields, $model) ?? ['details' => $fields, 'related' => []];

        if ($sections['related'] !== [] || self::hasRail($sections['details']) || self::cards($fields, $model) !== []) {
            return null;
        }

        // Values the header facts strip shows are not repeated, as on the record layout.
        $shownElsewhere = array_column(self::header($fields, $model), 'slug');

        if ($shownElsewhere !== []) {
            $sections = self::partition($fields, $model, $shownElsewhere) ?? $sections;
        }

        return RecordFormSections::arrange($sections['details']);
    }

    /**
     * Fields promoted into the page header. The resource's
     * recordHeaderFields() wins; otherwise the first Status and the first
     * Number field shown in lists, in field order.
     *
     * @param  array<int, array<string, mixed>>  $fields  Output of Resource::viewFields().
     * @return array<int, array<string, mixed>>
     */
    public static function header(array $fields, $model): array
    {
        $flat = self::flatten($fields);

        if ($flat === null || $flat === []) {
            return [];
        }

        $explicit = method_exists($model, 'recordHeaderFields') ? $model->recordHeaderFields() : null;

        if (is_array($explicit)) {
            $bySlug = collect($flat)->filter(fn ($field) => ! empty($field['slug']))->keyBy('slug');

            return collect($explicit)->map(fn ($slug) => $bySlug->get($slug))->filter()->values()->all();
        }

        $status = null;
        $number = null;

        foreach ($flat as $field) {
            if (($field['on_index'] ?? true) === false) {
                continue;
            }

            $instance = $field['field'] ?? null;

            if ($status === null && $instance instanceof Status) {
                $status = $field;
            } elseif ($number === null && $instance instanceof Number) {
                $number = $field;
            }
        }

        return array_values(array_filter([$number, $status]));
    }

    /**
     * A narrow panel (style width up to 33%, the form's sidebar card). Beside
     * other values the record page and the form show it as a right rail.
     */
    public static function isRailPanel(array $field): bool
    {
        return ($field['field'] ?? null) instanceof Panel
            && is_numeric($field['style']['width'] ?? null) && (float) $field['style']['width'] <= 33;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields  Output of Resource::viewFields().
     * @param  array<int, string>  $shownElsewhere  Slugs the page already shows (header facts, relation cards).
     * @return array{details: array<int, array<string, mixed>>, related: array<int, array<string, mixed>>}
     */
    public static function split(array $fields, $model, array $shownElsewhere = []): array
    {
        $sections = self::partition($fields, $model, $shownElsewhere);

        if ($sections === null) {
            return ['details' => $fields, 'related' => []];
        }

        $panels = array_keys(array_filter($sections['details'], fn ($field) => ($field['field'] ?? null) instanceof Panel));

        // A single panel usually carries the resource name, which the header shows already.
        if (count($panels) === 1) {
            $sections['details'][$panels[0]]['name'] = __('Details');
        }

        return $sections;
    }

    /**
     * @return array<int, array{name: string, value: mixed}>
     */
    private static function cardFacts(Resource $related): array
    {
        $title = trim((string) $related->title());
        $facts = [];

        foreach ($related->indexFields() as $field) {
            // One-line values only: no images, files, rich text or relations in a card.
            if (empty($field['slug']) || ($field['field_type'] ?? null) !== 'input' || ! in_array(class_basename($field['field'] ?? ''), self::CARD_FACT_TYPES, true)) {
                continue;
            }

            $value = $related->display($field['slug']);

            if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
                continue;
            }

            if (is_scalar($value) && trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES)) === $title) {
                continue;
            }

            $facts[] = ['name' => (string) ($field['name'] ?? $field['slug']), 'value' => $value];

            if (count($facts) === self::CARD_FACTS) {
                break;
            }
        }

        return $facts;
    }

    /**
     * The record's value fields, panels unwrapped. Null when the tree holds
     * other groups (tabs), the custom-layout case.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private static function flatten(array $fields): ?array
    {
        $flat = [];

        foreach ($fields as $field) {
            if (($field['field'] ?? null) instanceof Panel) {
                foreach ($field['fields'] ?? [] as $child) {
                    if (self::isRelatedList($child)) {
                        continue;
                    }

                    if (self::isGroup($child)) {
                        return null;
                    }

                    $flat[] = $child;
                }

                continue;
            }

            if (self::isRelatedList($field)) {
                continue;
            }

            if (self::isGroup($field)) {
                return null;
            }

            $flat[] = $field;
        }

        return $flat;
    }

    private static function hasRail(array $details): bool
    {
        return collect($details)->contains(fn ($field) => self::isRailPanel($field))
            && collect($details)->contains(fn ($field) => ! self::isRailPanel($field));
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

    /**
     * The record's values (minus the title and the slugs shown elsewhere) and
     * its embedded lists. Null when the tree holds other groups (tabs).
     *
     * @return array{details: array<int, array<string, mixed>>, related: array<int, array<string, mixed>>}|null
     */
    private static function partition(array $fields, $model, array $shownElsewhere = []): ?array
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
                        return null;
                    } elseif (! self::repeatsTitle($child, $model, $title) && ! in_array($child['slug'] ?? null, $shownElsewhere, true)) {
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
                return null;
            } elseif (! self::repeatsTitle($field, $model, $title) && ! in_array($field['slug'] ?? null, $shownElsewhere, true)) {
                $details[] = $field;
            }
        }

        return ['details' => $details, 'related' => $related];
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
