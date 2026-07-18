<?php

namespace Aura\Base\Fields;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;

class BelongsToMany extends Field
{
    public $edit = 'aura::fields.has-many';

    public bool $group = true;

    public $optionGroup = 'Relationship Fields';

    public string $type = 'relation';

    // public $view = 'components.fields.hasmany';

    /**
     * Scope the embedded table (the target resource, e.g. Team) to exactly the
     * records related to the PARENT record through the many-to-many pivot.
     *
     * The table renders with the target resource as its model and the record
     * being viewed as $component->parent (e.g. a User's Teams tab: model = Team,
     * parent = User). The relationship — and therefore the correct set of rows —
     * is derived from the PARENT, not from the table model. The previous branches
     * keyed off the table model ($component->model), which is why the Teams tab
     * showed the full, unfiltered set instead of the user's Memberships.
     *
     * Resolution is generic: whichever many-to-many relation the parent exposes
     * for this field (by declared relation name, else the field slug) is used as
     * a pivot-resolved subquery, so search/sort/pagination applied by the Table
     * afterwards compose cleanly. With no parent (a standalone index) the field
     * lists the full target set, unchanged.
     */
    public function queryFor($query, $component)
    {
        $field = $component->field;
        $parent = optional($component)->parent;

        // Standalone / parentless context (bare index): no parent record to
        // scope by, so list the full target set unchanged.
        if (! $parent) {
            return $query;
        }

        $relation = $this->parentRelation($parent, $field);

        // The parent exposes a genuine many-to-many relation to the target — the
        // set of rows is the parent's related records, resolved through the pivot.
        // A whereIn against a pivot-constrained subquery of the target key leaves
        // the Table's later modifications (search, sort, pagination) intact.
        if ($relation instanceof EloquentBelongsToMany) {
            $relatedKey = $relation->getRelated()->getQualifiedKeyName();

            return $query->whereIn(
                $relatedKey,
                $relation->getQuery()->select($relatedKey)
            );
        }

        // Parent present but no matching relation: show nothing rather than
        // leaking the full set.
        return $query->whereRaw('1 = 0');
    }

    /**
     * Resolve the parent's many-to-many relation for this field: prefer an
     * explicitly declared relation-method name, otherwise fall back to the field
     * slug (the convention the User → Teams tab follows). Returns null when the
     * parent has no such relation or it is not a BelongsToMany.
     */
    protected function parentRelation($parent, $field): ?EloquentBelongsToMany
    {
        $method = (isset($field['relation']) && is_string($field['relation']))
            ? $field['relation']
            : ($field['slug'] ?? null);

        if (! is_string($method) || ! method_exists($parent, $method)) {
            return null;
        }

        $relation = $parent->{$method}();

        return $relation instanceof EloquentBelongsToMany ? $relation : null;
    }
}
