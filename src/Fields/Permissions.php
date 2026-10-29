<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Str;

class Permissions extends Field
{
    /**
     * The abilities GenerateResourcePermissions creates for every resource, in
     * matrix column order. "scope" limits a role to the records its users own,
     * so bulk selection skips it.
     */
    public const ABILITIES = [
        'view' => 'View',
        'viewAny' => 'View any',
        'create' => 'Create',
        'update' => 'Update',
        'restore' => 'Restore',
        'delete' => 'Delete',
        'forceDelete' => 'Force delete',
        'scope' => 'Own only',
    ];

    public $edit = 'aura::fields.permissions';

    public $view = 'aura::fields.permissions-view';

    public function get($class, $value, $field = null)
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Permissions',
                'name' => 'Permissions',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'select',
                'style' => [],
            ],
            [
                'label' => 'Resource',
                'name' => 'Resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'resource',
            ],
        ]);
    }

    /**
     * Arrange permissions into one row per group with one cell per standard
     * ability, keyed by the slug prefix ("delete-post" fills "delete").
     * Custom permissions that fit no free cell land in "other" so they stay
     * assignable.
     *
     * @param  iterable<array{name: string, description: ?string, slug: string, group: ?string}>  $permissions
     * @return array<string, array{cells: array<string, ?array>, other: list<array>}>
     */
    public static function matrix(iterable $permissions): array
    {
        $rows = [];

        foreach ($permissions as $permission) {
            $group = (string) ($permission['group'] ?? '');
            $rows[$group] ??= ['cells' => array_fill_keys(array_keys(self::ABILITIES), null), 'other' => []];

            $ability = Str::before($permission['slug'], '-');

            if (array_key_exists($ability, self::ABILITIES) && $rows[$group]['cells'][$ability] === null) {
                $rows[$group]['cells'][$ability] = $permission;
            } else {
                $rows[$group]['other'][] = $permission;
            }
        }

        return $rows;
    }

    public function set($post, $field, $value)
    {
        return json_encode($value);
    }
}
