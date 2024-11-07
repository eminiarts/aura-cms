## ./Email.php
```
<?php

namespace Aura\Base\Fields;

class Email extends Field
{
    public $edit = 'aura::fields.email';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Email',
                'name' => 'Email',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'email-tab',
                'style' => [],
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
```

## ./Tabs.php
```
<?php

namespace Aura\Base\Fields;

class Tabs extends Field
{
    public $edit = 'aura::fields.tabs';

    public bool $group = true;

    public string $type = 'tabs';

    public $view = 'aura::fields.tabs';

    public bool $sameLevelGrouping = false;
}
```

## ./Wysiwyg.php
```
<?php

namespace Aura\Base\Fields;

class Wysiwyg extends Field
{
    public $edit = 'aura::fields.wysiwyg';

    public $optionGroup = 'JS Fields';

    public $tableColumnType = 'text';

    public $view = 'aura::fields.view-value';
}
```

## ./Datetime.php
```
<?php

namespace Aura\Base\Fields;

class Datetime extends Field
{
    public $edit = 'aura::fields.datetime';

    public $optionGroup = 'Input Fields';

    // public $view = 'components.fields.datetime';

    public $tableColumnType = 'timestamp';

    public $view = 'aura::fields.view-value';

    public function filterOptions()
    {
        return [
            'is' => __('is'),
            'is_not' => __('is not'),
            'before' => __('before'),
            'after' => __('after'),
            'on_or_before' => __('on or before'),
            'on_or_after' => __('on or after'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
        ];
    }

    public function get($class, $value, $field = null)
    {
        return $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Datetime',
                'name' => 'Datetime',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'date',
                'style' => [],
            ],
            [
                'label' => 'Format',
                'name' => 'Format',

                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'format',
                'default' => 'd.m.Y H:i',
                'instructions' => 'The format of how the date gets stored in the DB. Default is d.m.Y H:i. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'name' => 'Display Format',

                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'display_format',
                'default' => 'd.m.Y H:i',
                'instructions' => 'How the Date gets displayed. Default is d.m.Y H:i. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'label' => 'Enable Input',
                'name' => 'Enable Input',

                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'slug' => 'enable_input',
                'default' => true,
                'instructions' => 'Enable user input. Default is true.',
            ],
            [
                'label' => 'Max Date',
                'name' => 'Max Date',

                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'numeric|min:0|max:365',
                'slug' => 'maxDate',
                'default' => false,
                'instructions' => 'Number of days from today to the maximum selectable date.',
            ],

            [
                'name' => 'Min Time',
                'type' => 'Aura\\Base\\Fields\\Time',
                'validation' => '',
                'slug' => 'minTime',
                'default' => false,
                'instructions' => null,
            ],

            [
                'name' => 'Max Time',
                'type' => 'Aura\\Base\\Fields\\Time',
                'validation' => '',
                'slug' => 'maxTime',
                'default' => false,
                'instructions' => null,
            ],

            [
                'name' => 'Week starts on',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'options' => [
                    '0' => 'Sunday',
                    '1' => 'Monday',
                    '2' => 'Tuesday',
                    '3' => 'Wednesday',
                    '4' => 'Thursday',
                    '5' => 'Friday',
                    '6' => 'Saturday',
                ],
                'slug' => 'weekStartsOn',
                'default' => 1,
                'instructions' => 'The day the week starts on. 0 (Sunday) to 6 (Saturday). Default is 1 (Monday).',
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        return $value;
    }
}
```

## ./Boolean.php
```
<?php

namespace Aura\Base\Fields;

class Boolean extends Field
{
    public $edit = 'aura::fields.boolean';

    public $optionGroup = 'Choice Fields';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        if ($value) {
            return '<svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'; // Check icon from Heroicons
        } else {
            return '<svg class="w-6 h-6 text-gray-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'; // X icon from Heroicons
        }
    }

    public function get($class, $value, $field = null)
    {
        return (bool) $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Boolean',
                'name' => 'Boolean',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'boolean-tab',
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'instructions' => 'Default value on create',
                'slug' => 'default',
                'default' => false,
            ],

        ]);
    }

    public function set($post, $field, $value)
    {
        return (bool) $value;
    }

    public function value($value)
    {
        return (bool) $value;
    }
}
```

## ./Code.php
```
<?php

namespace Aura\Base\Fields;

class Code extends Field
{
    public $edit = 'aura::fields.code';

    public $optionGroup = 'JS Fields';

    // public $view = 'components.fields.code';

    public function get($class, $value, $field = null)
    {
        // If value is a JSON encoded string, decode it
        $decodedValue = json_decode($value, true);

        // Check if decoding was successful and re-encode for formatting
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decodedValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return $value;
        if (is_array($value) || $value === null) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Code',
                'name' => 'Code',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'code',
                'style' => [],
            ],
            [
                'label' => 'Language',
                'name' => 'Language',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => 'required',
                'slug' => 'language',
                'options' => [
                    'html' => 'HTML',
                    'css' => 'CSS',
                    'javascript' => 'JavaScript',
                    'php' => 'PHP',
                    'json' => 'JSON',
                    'yaml' => 'YAML',
                    'markdown' => 'Markdown',
                ],
            ],
            [
                'label' => 'Line Numbers',
                'name' => 'line_numbers',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'slug' => 'line_numbers',
            ],
            [
                'label' => 'Minimum Height',
                'name' => 'min_height',
                'type' => 'Aura\\Base\\Fields\\Number',
                'slug' => 'min_height',
                'validation' => 'nullable|numeric|min:100', // Assuming a reasonable minimum height of 100px
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        return $value;

        return json_encode($value);
    }
}
```

## ./Radio.php
```
<?php

namespace Aura\Base\Fields;

class Radio extends Field
{
    public $edit = 'aura::fields.radio';

    public $optionGroup = 'Choice Fields';

    // public $view = 'components.fields.radio';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Radio',
                'name' => 'Radio',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'radio',
                'style' => [],
            ],

            [
                'name' => 'Options',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',
            ],
            [
                'name' => 'Key',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'key',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'exclude_from_nesting' => true,
                'slug' => 'default',
            ],
        ]);
    }
}
```

## ./Time.php
```
<?php

namespace Aura\Base\Fields;

class Time extends Field
{
    public $edit = 'aura::fields.time';

    // public $view = 'components.fields.time';

    public $optionGroup = 'Input Fields';

    public function get($class, $value, $field = null)
    {
        return $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Time',
                'name' => 'Time',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'date',
                'style' => [],
            ],
            [
                'label' => 'Format',
                'name' => 'Format',

                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'format',
                'default' => 'H:i',
                'instructions' => 'The format of how the date gets stored in the DB. Default is H:i. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'name' => 'Display Format',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'display_format',
                'default' => 'H:i',
                'instructions' => 'How the Date gets displayed. Default is H:i. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'label' => 'Enable Input',
                'name' => 'Enable Input',

                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'slug' => 'enable_input',
                'default' => true,
                'instructions' => 'Enable user input. Default is true.',
            ],
            [
                'label' => 'Enable Seconds',
                'name' => 'Enable Seconds',

                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'slug' => 'enable_seconds',
                'default' => false,
                'instructions' => 'Enable seconds. Default is false.',
            ],

            [
                'name' => 'Min Time',
                'type' => 'Aura\\Base\\Fields\\Time',
                'validation' => '',
                'slug' => 'minTime',
                'default' => false,
                'instructions' => null,
            ],

            [
                'name' => 'Max Time',
                'type' => 'Aura\\Base\\Fields\\Time',
                'validation' => '',
                'slug' => 'maxTime',
                'default' => false,
                'instructions' => null,
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        return $value;
    }
}
```

## ./Roles.php
```
<?php

namespace Aura\Base\Fields;

class Roles extends AdvancedSelect
{
    public function getRelation($model, $field)
    {
        if (! $model->exists) {
            return collect();
        }

        return $this->relationship($model, $field)->get();
    }

    public function isRelation($field = null)
    {
        return true;
    }

    // public function get($class, $value, $field = null)
    // {
    //      ray('get roles........', $class, $value, $field)->blue();

    //      return $value;
    // }

    public function relationship($model, $field)
    {
        if (config('aura.teams')) {
            return $model->roles()->where('team_id', $model->current_team_id);
        }

        return $model->roles();
    }

    public function saved($post, $field, $value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $roleIds = $value;

        if (empty($roleIds)) {
            // Remove all roles for this user in the current team
            if (config('aura.teams')) {
                $post->roles()->wherePivot('team_id', $post->current_team_id)->detach();
            } else {
                $post->roles()->detach();
            }

            return;
        }

        // Get current roles for this user in the current team
        if (config('aura.teams')) {
            $currentRoleIds = $post->roles()
                ->wherePivot('team_id', $post->current_team_id)
                ->pluck('roles.id')
                ->toArray();
        } else {
            $currentRoleIds = $post->roles()
                ->pluck('roles.id')
                ->toArray();
        }

        // Roles to add
        $rolesToAdd = array_diff($roleIds, $currentRoleIds);

        // Roles to remove
        $rolesToRemove = array_diff($currentRoleIds, $roleIds);

        // Remove roles
        if (! empty($rolesToRemove)) {
            if (config('aura.teams')) {
                $post->roles()->wherePivot('team_id', $post->current_team_id)->detach($rolesToRemove);
            } else {
                $post->roles()->detach($rolesToRemove);
            }
        }

        // Add new roles
        foreach ($rolesToAdd as $roleId) {
            if (config('aura.teams')) {
                $post->roles()->attach($roleId, ['team_id' => $post->current_team_id]);
            } else {
                $post->roles()->attach($roleId);
            }
        }

        // Clear any relevant cache
        // For example:
        // Cache::forget('user.'.$post->id.'.roles');
    }
}
```

## ./Number.php
```
<?php

namespace Aura\Base\Fields;

class Number extends Field
{
    public $edit = 'aura::fields.number';

    public $optionGroup = 'Input Fields';

    public $tableColumnType = 'integer';

    public $view = 'aura::fields.view-value';

    public function filterOptions()
    {
        return [
            'equals' => __('equals'),
            'not_equals' => __('does not equal'),
            'greater_than' => __('greater than'),
            'less_than' => __('less than'),
            'greater_than_or_equal' => __('greater than or equal to'),
            'less_than_or_equal' => __('less than or equal to'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
        ];
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Number',
                'name' => 'Number',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'number-tab',
                'style' => [],
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
            [
                'name' => 'Prefix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'prefix',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Suffix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'suffix',
                'style' => [
                    'width' => '50',
                ],
            ],

        ]);
    }

    public function getFilterValues($model, $field)
    {
        // For number fields, we don't typically provide predefined values
        // But we could return min and max values if they're defined in the field config
        return [
            'min' => $field['min'] ?? null,
            'max' => $field['max'] ?? null,
        ];
    }

    public function set($post, $field, $value)
    {
        return $value;
    }

    public function value($value)
    {
        return (int) $value;
    }
}
```

## ./Group.php
```
<?php

namespace Aura\Base\Fields;

class Group extends Field
{
    public $edit = 'aura::fields.group';

    public bool $group = true;

    public $optionGroup = 'Structure Fields';

    public string $type = 'group';

    // public $view = 'components.fields.group';

    // public function get($field, $value)
    // {
    //     return $value;

    //     return json_decode($value, true);
    // }

    // public function getFields()
    // {
    //     $fields = collect(parent::getFields())->filter(function ($field) {
    //         // check if the slug of the field starts with "on", if yes filter it out
    //         return ! str_starts_with($field['slug'], 'on_');
    //     })->toArray();

    //     return array_merge($fields, [

    //     ]);
    // }

    // public function set($post, $field, $value)
    // {
    //     return $value;
    //     // dd('set group', $value);
    //     return json_encode($value);
    // }

    // public function transform($fields, $values)
    // {
    //     $slug = $this->attributes['slug'];

    //     // Create a collection of $fields, then map over it and add the slug to the item slug
    //     $fields = collect($fields)->map(function ($item) use ($slug) {
    //         $item['slug'] = $slug.'.'.$item['slug'];

    //         return $item;
    //     })->toArray();

    //     return $fields;
    // }
}
```

## ./AdvancedSelect.php
```
<?php

namespace Aura\Base\Fields;

class AdvancedSelect extends Field
{
    public $edit = 'aura::fields.advanced-select';

    public $index = 'aura::fields.advanced-select-index';

    public $optionGroup = 'JS Fields';

    public $view = 'aura::fields.advanced-select-view';

    public $filter = 'aura::fields.filters.advanced-select';

    public function filter()
    {
        if ($this->filter) {
            return $this->filter;
        }
    }

    public function filterOptions()
    {
        return [
            'contains' => __('contains'),
        ];
    }

    public function api($request)
    {
        $model = app($request->model);
        $searchableFields = $model->getSearchableFields()->pluck('slug')->toArray();

        $field = $request->fullField;

        $values = $model->searchIn($searchableFields, $request->search, $model)
            ->take(5)
            ->get()
            ->map(function ($item) use ($field) {
                return [
                    'id' => $item->id,
                    'title' => $item->title(),
                    'view' => isset($field['view_select']) ? view($field['view_select'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-select', ['item' => $item, 'field' => $field])->render(),
                    'view_selected' => isset($field['view_selected']) ? view($field['view_selected'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-selected', ['item' => $item, 'field' => $field])->render(),
                ];
            })
            ->toArray();

        return $values;
    }

    // public function display($field, $value, $model)
    // {
    //     if (! $value) {
    //         return;
    //     }

    //     $items = app($field['resource'])->find($value);

    //     if (! $items) {
    //         return;
    //     }

    //     // return $item->title;

    //     if ($items instanceof \Illuminate\Support\Collection) {
    //         return $items->map(function ($item) {
    //             return $item->title();
    //         })->implode(', ');
    //     }

    //     return $items->title();
    // }

    public function get($class, $value, $field = null)
    {
        //  ray('get ........', $class, $value, $field)->blue();

        if (isset($field['polymorphic_relation']) && $field['polymorphic_relation'] === false) {
            // ray('save meta', $field['slug'], $ids);

            //  ray('get ........', $class, $value, $field)->blue();

            if (empty($value)) {
                return;
            }

            if (is_string($value)) {
                // ray('is_string', $value);
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // ray($decoded);
                    return $decoded;
                }

                return $value;
            }

            // ray('save meta', $post->meta()->get());
            return $value;
        }

        if (! ($field['multiple'] ?? true) && ! ($field['polymorphic_relation'] ?? false)) {
            // dd('hier');
            // ray('hier before return int', $field['slug'], $value)->red();
            if ($value instanceof \Illuminate\Support\Collection) {
                if ($value->isEmpty()) {
                    return [];
                } else {
                    return [(int) $value->first()];
                }
            }

            return [(int) $value];
        }

        if (is_array($value)) {
            return array_column($value, 'id');
        } elseif (is_object($value) && method_exists($value, 'pluck')) {
            return $value->pluck('id')->toArray();
        } elseif (is_int($value)) {
            return $value;
        } else {
            return [];
        }
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Select Many',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'select-many',
                'style' => [],
            ],
            [
                'name' => 'Resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'resource',
            ],

            [
                'name' => 'Thumbnail slug',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'thumbnail',
            ],

            [
                'name' => 'Custom View Selected',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view_selected',
            ],
            [
                'name' => 'Custom View Select',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view_select',
            ],

            [
                'name' => 'Custom View View',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view_view',
            ],
            [
                'name' => 'Custom View Index',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view_index',
            ],

            [
                'name' => 'Allow Create New',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'slug' => 'create',
            ],
            [
                'name' => 'Multiple',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'slug' => 'multiple',
            ],

            // [
            //     'name' => 'Min Items',
            //     'type' => 'Aura\\Base\\Fields\\Number',
            //     'validation' => 'min:0',
            //     'slug' => 'min_items',
            // ],
            // [
            //     'name' => 'Max Items',
            //     'type' => 'Aura\\Base\\Fields\\Number',
            //     'validation' => 'min:0',
            //     'slug' => 'max_items',
            // ],
        ]);
    }

    public function getRelation($model, $field)
    {
        if (! $model->exists) {
            return collect();
        }

        return $this->relationship($model, $field)->get();
    }

    public function isRelation($field = null)
    {
        if (optional($field)['polymorphic_relation'] === false) {
            return false;
        }

        return true;
    }

    public function relationship($model, $field)
    {
        if (! ($field['polymorphic_relation'] ?? true)) {
            $resourceClass = $field['resource'];
            if ($field['multiple'] ?? true) {
                $values = $model->meta()->where('key', $field['slug'])->value('value');
                $ids = json_decode($values, true) ?: [];

                return $resourceClass::whereIn('id', $ids);
            } else {
                $value = $model->meta()->where('key', $field['slug'])->value('value');

                return $resourceClass::where('id', $value);
            }
        }

        $morphClass = $field['resource'];

        return $model
            ->morphToMany($morphClass, 'related', 'post_relations', 'related_id', 'resource_id')
            ->withTimestamps()
            ->withPivot('resource_type', 'slug', 'order')
            ->wherePivot('resource_type', $morphClass)
            ->wherePivot('slug', $field['slug'])
            ->orderBy('post_relations.order');
    }

    public function saved($post, $field, $value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $ids = $value;

        //ray('saved', $post, $field, $value, $ids);

        // dd($post->toArray(), $field, $ids);

        if (optional($field)['polymorphic_relation'] === false) {
            // ray('save meta', $field['slug'], $ids);
            // Save as meta
            $value = is_array($ids) ? json_encode($ids) : $ids;
            $post->meta()->updateOrCreate(['key' => $field['slug']], ['value' => $value ?? null]);

            // ray('save meta', $post->meta()->get());
            return;
        }

        // dd($post->toArray(), $field, $ids);

        $pivotData = [];

        if (empty($ids)) {
            return;
        }

        if (is_int($ids)) {
            return;
        }

        // Temporary fix for the issue
        if (is_string($ids) && json_decode($ids) !== null) {
            $ids = json_decode($ids, true);
        }

        // ray('ids', $ids);

        foreach ($ids as $index => $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : $item;
            if ($id !== null && (is_string($id) || is_int($id))) {
                $pivotData[$id] = [
                    'resource_type' => $field['resource'],
                    'slug' => $field['slug'],
                    'order' => $index + 1,
                ];
            }
        }

        // Get the current relations for this specific field
        $currentRelations = $post->{$field['slug']}()
            ->wherePivot('slug', $field['slug'])
            ->pluck('resource_id')
            ->toArray();

        // Detach only the relations for this specific field that are not in the new set
        $toDetach = array_diff($currentRelations, array_keys($pivotData));
        if (! empty($toDetach)) {
            $post->{$field['slug']}()->wherePivot('slug', $field['slug'])->detach($toDetach);
        }

        // ray('pivotData', $pivotData);

        // ray('1 ' . $field['slug'], $post->{$field['slug']}()->get());

        // Attach or update the new relations
        foreach ($pivotData as $id => $data) {
            $post->{$field['slug']}()->syncWithoutDetaching([$id => $data]);
        }

        // dd('2 ' . $field['slug'], $post->{$field['slug']}()->get());

        // ray('2 ' . $field['slug'], $post->{$field['slug']}()->get());
    }

    public function selectedValues($model, $values, $field)
    {
        if (! $values) {
            return [];
        }

        // if $values is a string, convert it to an array
        if (! is_array($values)) {
            $values = [$values];
        }

        return app($model)->whereIn('id', $values)->get()->map(function ($item) use ($field) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
                'view' => isset($field['view_select']) ? view($field['view_select'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-select', ['item' => $item, 'field' => $field])->render(),
                'view_selected' => isset($field['view_selected']) ? view($field['view_selected'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-selected', ['item' => $item, 'field' => $field])->render(),
            ];
        })->toArray();
    }

    public function set($post, $field, $value)
    {
        // Add logging or debugging at the start of the method
        // dd('AdvancedSelect set method called', ['field' => $field, 'value' => $value]);

        // Check if 'multiple' key exists in $field array
        $isMultiple = $field['multiple'] ?? false;

        if ($isMultiple) {
            return json_encode($value);
        }

        if (! $isMultiple && ! ($field['polymorphic_relation'] ?? false)) {
            if (is_array($value) && ! empty($value)) {
                return $value[0];
            }
        }

        return json_encode($value);
    }

    public function values($model, $field)
    {
        return app($model)->get()->map(function ($item) use ($field) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
                'view' => isset($field['view_select']) ? view($field['view_select'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-select', ['item' => $item, 'field' => $field])->render(),
                'view_selected' => isset($field['view_selected']) ? view($field['view_selected'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-selected', ['item' => $item, 'field' => $field])->render(),

            ];
        })->toArray();
    }
}
```

## ./Json.php
```
<?php

namespace Aura\Base\Fields;

class Json extends Field
{
    public $edit = 'aura::fields.json';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        return json_encode($value);
    }

    public function get($class, $value, $field = null)
    {
        if (is_array($value) || $value === null) {
            return $value;
        }

        return json_decode($value, true);
    }

    // public function getFields()
    // {
    //     return array_merge(parent::getFields(), [
    //         [
    //             'label' => 'JSON',
    //             'name' => 'JSON',
    //             'type' => 'Aura\\Base\\Fields\\Tab',
    //             'slug' => 'json',
    //             'style' => [],
    //         ],
    //         [
    //             'label' => 'Language',
    //             'name' => 'Language',
    //             'type' => 'Aura\\Base\\Fields\\Select',
    //             'validation' => 'required',
    //             'slug' => 'language',
    //             'options' => [
    //                 'html' => 'HTML',
    //                 'css' => 'CSS',
    //                 'javascript' => 'JavaScript',
    //                 'php' => 'PHP',
    //                 'json' => 'JSON',
    //                 'yaml' => 'YAML',
    //                 'markdown' => 'Markdown',
    //             ],
    //         ],
    //     ]);
    // }

    public function set($post, $field, $value)
    {
        // dd('hier', $value);

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
```

## ./HorizontalLine.php
```
<?php

namespace Aura\Base\Fields;

class HorizontalLine extends Field
{
    public $edit = 'aura::fields.hr';

    public $optionGroup = 'Layout Fields';

    // public $view = 'components.fields.hr';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
```

## ./Permissions.php
```
<?php

namespace Aura\Base\Fields;

class Permissions extends Field
{
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

    public function set($post, $field, $value)
    {
        return json_encode($value);
    }
}
```

## ./BelongsTo.php
```
<?php

namespace Aura\Base\Fields;

use Aura\Base\Models\Meta;

class BelongsTo extends Field
{
    public $edit = 'aura::fields.belongsto';

    public bool $group = false;

    public $optionGroup = 'Relationship Fields';

    public $tableColumnType = 'bigInteger';

    public string $type = 'input';

    public $view = 'aura::fields.view-value';

    // public function get($class, $model, $field)
    // {
    //     // ray($field, $model);
    //     ray()->backtrace();
    //     dd($model, $field);

    //     $relationshipQuery = $this->relationship($model, $field);

    //     return $relationshipQuery->get();
    // }

    public function api($request)
    {
        // Get $searchable from $request->model
        $searchableFields = app($request->model)->getSearchableFields()->pluck('slug');

        $metaFields = $searchableFields->filter(function ($field) use ($request) {
            // check if it is a meta field
            return app($request->model)->isMetaField($field);
        });

        if (app($request->model)->usesCustomTable()) {
            $results = app($request->model)->searchIn($searchableFields->toArray(), $request->search)->take(50)->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title(),
                ];
            })->toArray();

        } else {

            $results = app($request->model)->select('posts.*')
                ->leftJoin('meta', function ($join) use ($metaFields, $request) {
                    $join->on('posts.id', '=', 'meta.metable_id')
                        ->where('meta.metable_type', app($request->model)->getMorphClass())
                        ->whereIn('meta.key', $metaFields);
                })
                ->where(function ($query) use ($request) {
                    $query->where('posts.title', 'like', '%'.$request->search.'%')
                        ->orWhere(function ($query) use ($request) {
                            $query->where('meta.value', 'LIKE', '%'.$request->search.'%');
                        });
                })
                ->distinct()
                ->take(20)
                ->get()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title(),
                    ];
                })->toArray();

        }

        // Fetch the model instance using the ID from $request->value
        if ($request->id) {

            $modelInstance = app($request->model)->find($request->id);

            // Append the model instance to the results
            $results[] = [
                'id' => $modelInstance->id,
                'title' => $modelInstance->title(),
            ];

        }

        // $results = app($request->model)->searchIn($searchableFields, $request->search)->take(20)->get();

        return collect($results)->unique('id')->values()->toArray();

        // dd($searchableFields, $request->model, $request->search);

        return app($request->model)->searchIn($searchableFields, $request->search)->take(20)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();
    }

    public function display($field, $value, $model)
    {
        if (optional($field)['display_view']) {
            return view($field['display_view'], ['row' => $model, 'field' => $field, 'value' => $value])->render();
        }

        if ($field['resource'] && $value) {

            $slug = app($field['resource'])->getSlug();

            // return $value;

            return "<a class='font-semibold' href='".route('aura.'.$slug.'.edit', $value)."'>".optional(app($field['resource'])::find($value))->title().'</a>';
        }

        return $value;
    }

    // public function get($field, $value)
    // {
    //     return json_decode($value, true);
    // }

    // public $view = 'components.fields.belongsto';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Belongs To',
                'name' => 'Belongs To',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-belongsTo',
                'style' => [],
            ],
            [
                'label' => 'Resource',
                'name' => 'resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'resource',
            ],
        ]);
    }

    public function queryFor($model)
    {
        return function ($query) use ($model) {
            return $query->where('user_id', $model->id);
        };
    }

    public function relationship($model, $field)
    {
        // If it's a meta field
        if ($model->usesMeta()) {
            return $model->hasManyThrough(
                $field['resource'],
                Meta::class,
                'value',     // Foreign key on the meta table
                'id',        // Foreign key on the resource table
                'id',        // Local key on the model table
                'metable_id' // Local key on the meta table
            )->where('meta.key', $field['relation'])
                ->where('meta.metable_type', $model->getMorphClass());
        }

        return $model->hasMany($field['resource'], $field['relation']);
    }

    public function set($post, $field, $value)
    {
        // Set the value to the id of the model
        return $value;
    }

    public function values($model)
    {
        return app($model)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();
    }

    public function valuesForApi($model, $currentId)
    {
        $results = app($model)->take(20)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();

        // Fetch the model instance using the ID from $request->value
        if ($currentId) {

            $modelInstance = app($model)->find($currentId);

            if (! $modelInstance) {
                return $results;
            }

            // Append the model instance to the results
            $results[] = [
                'id' => $modelInstance->id,
                'title' => $modelInstance->title(),
            ];

        }

        return collect($results)->unique('id')->values()->toArray();
    }
}
```

## ./File.php
```
<?php

namespace Aura\Base\Fields;

class File extends Field
{
    public $edit = 'aura::fields.file';

    public $optionGroup = 'Media Fields';

    public $view = 'aura::fields.view-value';

    public function get($class, $value, $field = null)
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function set($post, $field, $value)
    {
        // dump('setting file here', $value);
        if (is_null($value)) {
            return;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
```

## ./Hidden.php
```
<?php

namespace Aura\Base\Fields;

class Hidden extends Field
{
    public $edit = 'aura::fields.hidden';

    public $view = 'aura::fields.view-hidden';

    public function getFields()
    {
        return array_merge(parent::getFields());
    }
}
```

## ./BelongsToMany.php
```
<?php

namespace Aura\Base\Fields;

class BelongsToMany extends Field
{
    public $edit = 'aura::fields.has-many';

    public bool $group = true;

    public $optionGroup = 'Relationship Fields';

    public string $type = 'relation';

    // public $view = 'components.fields.hasmany';

    public function queryFor($query, $component)
    {
        $field = $component->field;
        $model = $component->model;

        if ($model instanceof \Aura\Base\Resources\User) {
            return $query->where('user_id', $model->id);
        }

        if ($model instanceof \Aura\Base\Resources\Team) {
            return $query;
        }

        return $query->where('user_id', $model->id);
    }
}
```

## ./Heading.php
```
<?php

namespace Aura\Base\Fields;

class Heading extends Field
{
    public $edit = 'aura::fields.heading';

    public $optionGroup = 'Layout Fields';

    // public $view = 'components.fields.heading';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
```

## ./Tab.php
```
<?php

namespace Aura\Base\Fields;

class Tab extends Field
{
    public $edit = 'aura::fields.tab';

    public bool $group = true;

    public $wrapper = Tabs::class;

    public $optionGroup = 'Structure Fields';

    public bool $sameLevelGrouping = true;

    public string $type = 'tab';

    public $view = 'aura::fields.tab';

    // public $view = 'components.fields.tab';

    public function getFields()
    {
        $fields = collect(parent::getFields())->filter(function ($field) {
            // check if the slug of the field starts with "on", if yes filter it out
            return ! str_starts_with($field['slug'], 'on_');
        })->toArray();

        return array_merge($fields, [

        ]);
    }
}
```

## ./Repeater.php
```
<?php

namespace Aura\Base\Fields;

class Repeater extends Field
{
    public $edit = 'aura::fields.repeater';

    public bool $group = true;

    public $optionGroup = 'Structure Fields';

    public string $type = 'input';

    // public bool $showChildrenOnIndex = false;
    // TODO: $showChildrenOnIndex should be applied to children

    // public $view = 'components.fields.repeater';

    public function get($class, $value, $field = null)
    {
        // $fields = $this->getFields();
        // dd($field, $value, $fields);
        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function getFields()
    {
        $fields = collect(parent::getFields())->filter(function ($field) {
            // check if the slug of the field starts with "on", if yes filter it out
            return ! str_starts_with($field['slug'], 'on_');
        })->toArray();

        return array_merge($fields, [

            [
                'name' => 'Min Entries',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'min',
                'default' => 0,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Max Entries',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'max',
                'default' => 0,
                'style' => [
                    'width' => '50',
                ],
            ],

        ]);
    }

    public function set($post, $field, $value)
    {
        return json_encode($value);
    }

    public function transform($field, $values)
    {
        $fields = $field['fields'];
        $slug = $field['slug'];

        $new = collect();

        // dd($values, $fields, $field, $slug);
        if (! $values) {
            return $fields;
        }

        foreach ($values as $key => $value) {
            $new[] = collect($fields)->map(function ($item) use ($slug, $key) {
                $item['slug'] = $slug.'.'.$key.'.'.$item['slug'];

                return $item;
            });
        }

        return $new;

        return $new->flatten(1);
    }
}
```

## ./Field.php
```
<?php

namespace Aura\Base\Fields;

use Aura\Base\Traits\InputFields;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Livewire\Wireable;

abstract class Field implements Wireable
{
    use InputFields;
    use Macroable;
    use Tappable;

    public $edit = null;

    public $field;

    public bool $group = false;

    public $index = null;

    public bool $on_forms = true;

    public $optionGroup = 'Fields';

    public $tableColumnType = 'string';

    public $tableNullable = true;

    public bool $taxonomy = false;

    public string $type = 'input';

    public $view = null;

    public $wrapper = null;
    
    public $wrap = false;

    public function display($field, $value, $model)
    {
        if ($this->index) {
            $componentName = $this->index;
            // If the component name starts with 'aura::', remove it
            if (Str::startsWith($componentName, 'aura::')) {
                //   $componentName = Str::after($componentName, 'aura::');
            }

            // Ensure the component name starts with 'fields.'
            if (! Str::startsWith($componentName, 'fields.')) {
                // $componentName = 'fields.' . $componentName;
            }

            return Blade::render(
                '<x-dynamic-component :component="$componentName" :row="$row" :field="$field" :value="$value" />',
                [
                    'componentName' => $componentName,
                    'row' => $model,
                    'field' => $field,
                    'value' => $value,
                ]
            );
        }

        if (optional($field)['display_view']) {
            return view($field['display_view'], ['row' => $model, 'field' => $field, 'value' => $value])->render();
        }

        return $value;
    }

    public function edit()
    {
        if ($this->edit) {
            return $this->edit;
        }
    }

    // public $edit;

    public function field($field)
    {
        // $this->field = $field;
        //$this->withAttributes($field);
        return $this;

        return get_class($this);
    }

    public function filterOptions()
    {
        return [
            'contains' => __('contains'),
            'does_not_contain' => __('does not contain'),
            'is' => __('is'),
            'is_not' => __('is not'),
            'starts_with' => __('starts with'),
            'ends_with' => __('ends with'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
            'equals' => __('equals'),
            'not_equals' => __('does not equal'),
            'greater_than' => __('greater than'),
            'less_than' => __('less than'),
            'greater_than_or_equal' => __('greater than or equal to'),
            'less_than_or_equal' => __('less than or equal to'),
            'in' => __('in'),
            'not_in' => __('not in'),
            'like' => __('like'),
            'not_like' => __('not like'),
            'regex' => __('matches regex'),
            'not_regex' => __('does not match regex'),
        ];
    }

    public static function fromLivewire($data)
    {
        $field = new static;

        $field->type = $data['type'];
        $field->view = $data['view'];

        return $field;
    }

    public function get($class, $value, $field = null)
    {
        return $value;
    }

    public function getFields()
    {
        return [
            [
                'label' => 'Field',
                'name' => 'Field',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'field',
                'style' => [],
            ],
            [
                'label' => 'Name',
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'name',
            ],
            [
                'label' => 'Slug',
                'name' => 'Slug',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'validation' => 'required|regex:/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/|not_regex:/^[0-9]+$/',
                'slug' => 'slug',
                'based_on' => 'name',
                'custom' => true,
                'disabled' => true,
            ],
            [
                'label' => 'Validation',
                'name' => 'Validation',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'validation',
            ],
            [
                'label' => 'Type',
                'name' => 'Type',
                'type' => 'Aura\\Base\\Fields\\Select',
                'live' => true,
                'validation' => 'required',
                'slug' => 'type',
                'options' => app('aura')::getFieldsWithGroups(),
            ],
            [
                'name' => 'instructions',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'instructions',
            ],

            [
                'name' => 'Searchable',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'instructions' => 'Defines if the field is searchable.',
                'validation' => '',
                'slug' => 'searchable',
                'default' => false,
            ],

            [
                'name' => 'View',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],

            [
                'name' => 'On Index',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'instructions' => 'Show on the index page / table.',
                'slug' => 'on_index',
            ],
            [
                'name' => 'On Forms',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'instructions' => 'Show on the create and edit forms.',
                'slug' => 'on_forms',
            ],
            [
                'name' => 'On View',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'instructions' => 'Show on the view page.',
                'validation' => '',
                'slug' => 'on_view',
            ],

            [
                'name' => 'Width',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'suffix' => '%',
                'instructions' => 'Width of the field in the form in %.',
                'slug' => 'style.width',
            ],

            [
                'label' => 'Conditional Logic',
                'name' => 'Conditional Logic',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'conditional_logic',
                'style' => [],
            ],

            [
                'label' => 'Add Condition',
                'name' => 'Add Condition',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'conditional_logic' => [],
                'style' => [
                    'width' => '100',
                ],
                'slug' => 'conditional_logic',
            ],
            [
                'label' => 'Type',
                'name' => 'Type',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Slug of the field to check. You can also use "role"',
                'conditional_logic' => [],
                'slug' => 'field',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'label' => 'Operator',
                'name' => 'Operator',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'options' => [
                    '' => 'Please Select',
                    '==' => '==',
                    '!=' => '!=',
                    '>' => '>',
                    '>=' => '>=',
                    '<' => '<',
                    '<=' => '<=',
                ],
                'conditional_logic' => [],
                'slug' => 'operator',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'label' => 'Value',
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'value',
                'style' => [
                    'width' => '33',
                ],
            ],

        ];
    }

    // public function view($view, $data = [], $mergeData = [])
    // {
    //     $this->view = $view;

    //     return $this;
    // }

    public function getFilterValues($model, $field)
    {
        // Default implementation returns an empty array
        // Most field types don't need predefined values for filtering
        return [];
    }

    public function isDisabled($model, $field)
    {
        if (optional($field)['disabled'] instanceof \Closure) {
            return $field['disabled']($model);
        }

        return $field['disabled'] ?? false;
    }

    public function isInputField()
    {
        return in_array($this->type, ['input', 'repeater', 'group']);
    }

    public function isRelation()
    {
        return in_array($this->type, ['relation']);
    }

    public function isTaxonomyField()
    {
        return $this->taxonomy;
    }

    public function toLivewire()
    {
        return [
            'type' => $this->type,
            'view' => $this->view,
        ];
    }

    public function value($value)
    {
        return $value;
    }

    public function view()
    {
        // ray($this, $this->view);
        if ($this->view) {
            return $this->view;
        }

        if ($this->edit) {
            return $this->edit;
        }
    }
}
```

## ./Embed.php
```
<?php

namespace Aura\Base\Fields;

class Embed extends Field
{
    public $edit = 'aura::fields.embed';

    // public $view = 'components.fields.embed';

    public function getFields()
    {
        return array_merge(parent::getFields(), [

        ]);
    }
}
```

## ./Password.php
```
<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Facades\Hash;

class Password extends Field
{
    public $edit = 'aura::fields.password';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    // public function get($field, $value)
    // {
    // }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }

    // Initialize the field on a LiveWire component
    public function hydrate() {}

    public function set($post, $field, $value)
    {
        // dd('set', $value);

        if ($value) {
            // Hash the password
            return Hash::make($value);
        }

        return $value;
    }
}
```

## ./ID.php
```
<?php

namespace Aura\Base\Fields;

class ID extends Field
{
    public $edit = 'aura::fields.text';

    public bool $on_forms = false;

    public $tableColumnType = 'bigIncrements';

    public $tableNullable = false;

    public string $type = 'input';

    public $view = 'aura::fields.view-value';
}
```

## ./ViewValue.php
```
<?php

namespace Aura\Base\Fields;

class ViewValue extends Field
{
    public $edit = 'aura::fields.view-value';

    public $view = 'aura::fields.view-value';
}
```

## ./Date.php
```
<?php

namespace Aura\Base\Fields;

class Date extends Field
{
    public $edit = 'aura::fields.date';

    public $index = 'aura::fields.date-index';

    public $optionGroup = 'Input Fields';

    public $tableColumnType = 'date';

    public $view = 'aura::fields.view-value';

    public function filterOptions()
    {
        return [
            'date_is' => __('is'),
            'date_is_not' => __('is not'),
            'date_before' => __('before'),
            'date_after' => __('after'),
            'date_on_or_before' => __('on or before'),
            'date_on_or_after' => __('on or after'),
            'date_is_empty' => __('is empty'),
            'date_is_not_empty' => __('is not empty'),
        ];
    }

    public function get($class, $value, $field = null)
    {
        return $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Date',
                'name' => 'Date',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'date',
                'style' => [],
            ],
            [
                'label' => 'Format',
                'name' => 'Format',

                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'format',
                'default' => 'd.m.Y',
                'instructions' => 'The format of how the date gets stored in the DB. Default is d.m.Y. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'name' => 'Display Format',

                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'display_format',
                'default' => 'd.m.Y',
                'instructions' => 'How the Date gets displayed. Default is d.m.Y. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'label' => 'Enable Input',
                'name' => 'Enable Input',

                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'options' => [
                    'true' => 'Enable Input',
                ],
                'slug' => 'enable_input',
                'default' => true,
                'instructions' => 'Enable user input. Default is true.',
            ],
            [
                'label' => 'Max Date',
                'name' => 'Max Date',

                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'numeric|min:0|max:365',
                'slug' => 'maxDate',
                'default' => false,
                'instructions' => 'Number of days from today to the maximum selectable date.',
            ],
            [
                'name' => 'Week starts on',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'options' => [
                    '0' => 'Sunday',
                    '1' => 'Monday',
                    '2' => 'Tuesday',
                    '3' => 'Wednesday',
                    '4' => 'Thursday',
                    '5' => 'Friday',
                    '6' => 'Saturday',
                ],
                'slug' => 'weekStartsOn',
                'default' => 1,
                'instructions' => 'The day the week starts on. 0 (Sunday) to 6 (Saturday). Default is 1 (Monday).',
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        return $value;
    }
}
```

## ./Status.php
```
<?php

namespace Aura\Base\Fields;

class Status extends Field
{
    public $edit = 'aura::fields.status';

    public $index = 'aura::fields.status-index';

    public $optionGroup = 'Choice Fields';

    public $view = 'aura::fields.status-view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Select',
                'name' => 'Select',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'select',
                'style' => [],
            ],

            [
                'name' => 'Options',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',
                // 'set' => function($model, $field, $value) {

                //     // dd($model, $field, $value);
                //     $array = [];
                //     foreach ($value as $item) {
                //         $array[$item['value']] = $item['name'];
                //     }
                //     return $array;
                // },
                // 'get' => function($model, $form, $value) {
                //     $array = $value;
                //     $result = [];
                //     foreach ($array as $key => $val) {
                //         $result[] = ['value' => $key, 'name' => $val];
                //     }
                //     return $result;
                // }
            ],
            [
                'name' => 'Key',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'key',
                'style' => [
                    'width' => '33',
                ],

            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Color',
                'type' => 'Aura\\Base\\Fields\\Status',
                'validation' => '',
                'slug' => 'color',
                'style' => [
                    'width' => '33',
                ],
                'options' => [
                    [
                        'key' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                        'value' => 'Blue',
                        'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    ],
                    [
                        'key' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                        'value' => 'Green',
                        'color' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    ],
                    [
                        'key' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                        'value' => 'Red',
                        'color' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    ],
                    [
                        'key' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                        'value' => 'Yellow',
                        'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                    ],
                    [
                        'key' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                        'value' => 'Indigo',
                        'color' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                    ],
                    [
                        'key' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                        'value' => 'Purple',
                        'color' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                    ],
                    [
                        'key' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300',
                        'value' => 'Pink',
                        'color' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300',
                    ],
                    [
                        'key' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        'value' => 'Gray',
                        'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                    ],
                    [
                        'key' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                        'value' => 'Orange',
                        'color' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                    ],
                    [
                        'key' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300',
                        'value' => 'Teal',
                        'color' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300',
                    ],
                ],
            ],

            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'exclude_from_nesting' => true,
                'slug' => 'default',
            ],

            [
                'name' => 'Allow Multiple',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'exclude_from_nesting' => true,
                'slug' => 'allow_multiple',
                'instructions' => 'Allow multiple selections?',
            ],

        ]);
    }

    // public $view = 'components.fields.select';

    public function options($model, $field)
    {
        // if get"$field->slug"Options is defined on the model, use that
        if (method_exists($model, 'get'.ucfirst($field['slug']).'Options')) {
            return $model->{'get'.ucfirst($field['slug']).'Options'}();
        }

        // return the options defined in the field
        return $field['options'] ?? [];
    }
}
```

## ./Color.php
```
<?php

namespace Aura\Base\Fields;

class Color extends Field
{
    public $edit = 'aura::fields.color';

    public $optionGroup = 'JS Fields';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Color',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'color-tab',
                'style' => [],
            ],
            [
                'name' => 'Native Color Picker',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'slug' => 'native',
                'style' => [],
            ],
            [
                'name' => 'Format',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'slug' => 'format',
                'options' => [
                    'hex' => 'Hex',
                    'rgb' => 'RGB',
                    'hsl' => 'HSL',
                    'hsv' => 'HSV',
                    'cmyk' => 'CMYK',
                ],
                'conditional_logic' => [
                    // [
                    //     'field' => 'native',
                    //     'operator' => '==',
                    //     'value' => '1',
                    // ],
                ],
            ],

        ]);
    }
}
```

## ./Panel.php
```
<?php

namespace Aura\Base\Fields;

class Panel extends Field
{
    public $edit = 'aura::fields.panel';

    public bool $group = true;

    public $optionGroup = 'Structure Fields';

    // Type Panel is used for grouping fields. A Panel can't be nested inside another Panel or other grouped Fields.
    public string $type = 'panel';

    public function getFields()
    {
        $fields = collect(parent::getFields())->filter(function ($field) {
            // check if the slug of the field starts with "on", if yes filter it out
            return ! str_starts_with($field['slug'], 'on_');
        })->toArray();

        return array_merge($fields, [

        ]);
    }
}
```

## ./Tags.php
```
<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Str;
use InvalidArgumentException;

class Tags extends Field
{
    public $edit = 'aura::fields.tags';

    public $filter = 'aura::fields.filters.tags';

    public bool $taxonomy = true;

    public string $type = 'input';

    public $view = 'aura::fields.view-value';

    public function filterOptions()
    {
        return [
            'contains' => __('contains'),
            'does_not_contain' => __('does not contain'),
        ];
    }

    public function filter()
    {
        if ($this->filter) {
            return $this->filter;
        }
    }

    public function display($field, $value, $model)
    {

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value) || count($value) === 0) {
            return '';
        }

        $resource = app($field['resource'])->query()->whereIn('id', $value)->get();

        return $resource->map(function ($item) {
            $title = $item->title ?? $item->title();

            return "<span class='px-2 py-1 text-xs text-white whitespace-nowrap rounded-full bg-primary-500'>$title</span>";
        })->implode(' ');
    }

    public function get($class, $value, $field = null)
    {
        if (is_array($value)) {
            return array_column($value, 'id');
        } elseif (is_object($value) && method_exists($value, 'pluck')) {
            return $value->pluck('id')->toArray();
        } else {
            return [];
        }
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Tags',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tags-tab',
                'style' => [],
            ],
            [
                'name' => 'Create',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => 'required',
                'instructions' => 'Allow new creations of Tags',
                'slug' => 'create',
                'default' => false,
            ],
            [
                'name' => 'Resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'resource',
            ],
            [
                'name' => 'Max Tags',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'max_tags',
            ],

        ]);
    }

    public function getRelation($model, $field)
    {

        if (! $model->exists) {
            return collect();
        }

        $relationshipQuery = $this->relationship($model, $field);

        return $relationshipQuery->get();

    }

    public function isRelation()
    {
        return true;
    }

    public function relationship($model, $field)
    {
        // Check if resource is set
        if (! isset($field['resource']) || empty($field['resource'])) {
            throw new InvalidArgumentException("The 'resource' key is not set or is empty in the field configuration.");
        }

        $morphClass = $field['resource'];

        // If it's a meta field
        return $model
            ->morphToMany($field['resource'], 'related', 'post_relations', 'related_id', 'resource_id')
            ->withTimestamps()
            ->withPivot('resource_type', 'slug', 'order')
            ->wherePivot('resource_type', $morphClass)
            ->wherePivot('slug', $field['slug'])
            ->orderBy('post_relations.order');
    }

    public function saved($post, $field, $value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $ids = collect($value)->map(function ($tagName) use ($field) {

            if (is_int($tagName)) {
                return $tagName;
            } else {
                $tag = app($field['resource'])->create([
                    'title' => $tagName,
                    'slug' => Str::slug($tagName),
                ]);

                return $tag->id;
            }
        })->toArray();
        // if ($field['slug'] === 'tags') {
        //     dd($ids);
        // }

        if (is_array($ids)) {
            $post->{$field['slug']}()->syncWithPivotValues($ids, [
                'resource_type' => $field['resource'],
                'slug' => $field['slug'],
            ]);
        } else {
            $post->{$field['slug']}()->sync([]);
        }
    }
}
```

## ./View.php
```
<?php

namespace Aura\Base\Fields;

class View extends Field
{
    public $edit = 'aura::fields.view';

    // public $view = 'components.fields.view';

    public string $type = 'view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'View',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'view-tab',
                'style' => [],
            ],
            [
                'name' => 'View',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view',
            ],
        ]);
    }
}
```

## ./Slug.php
```
<?php

namespace Aura\Base\Fields;

class Slug extends Field
{
    public $edit = 'aura::fields.slug';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

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
```

## ./Phone.php
```
<?php

namespace Aura\Base\Fields;

class Phone extends Field
{
    public $edit = 'aura::fields.phone';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
```

## ./Textarea.php
```
<?php

namespace Aura\Base\Fields;

class Textarea extends Field
{
    public $edit = 'aura::fields.textarea';

    public $optionGroup = 'Input Fields';

    public $tableColumnType = 'text';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Textarea',
                'name' => 'Textarea',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'textarea-tab',
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Textarea',
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
            [
                'name' => 'Rows',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'min' => 1,
                'default' => 3,
                'slug' => 'rows',
            ],
            [
                'name' => 'Max Length',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'max_length',
                'style' => [
                    'width' => '100',
                ],

            ],
        ]);
    }
}
```

## ./Select.php
```
<?php

namespace Aura\Base\Fields;

class Select extends Field
{
    public $edit = 'aura::fields.select';

    public $optionGroup = 'Choice Fields';

    public $view = 'aura::fields.view-value';

    public function filterOptions()
    {
        return [
            'is' => __('is'),
            'is_not' => __('is not'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
        ];
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Select',
                'name' => 'Select',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'select',
                'style' => [],
            ],

            [
                'name' => 'Options',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',
                // 'set' => function($model, $field, $value) {

                //     // dd($model, $field, $value);
                //     $array = [];
                //     foreach ($value as $item) {
                //         $array[$item['value']] = $item['name'];
                //     }
                //     return $array;
                // },
                // 'get' => function($model, $form, $value) {
                //     $array = $value;
                //     $result = [];
                //     foreach ($array as $key => $val) {
                //         $result[] = ['value' => $key, 'name' => $val];
                //     }
                //     return $result;
                // }
            ],
            [
                'name' => 'Key',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'key',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],

            ],

            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'exclude_from_nesting' => true,
                'slug' => 'default',
            ],

            [
                'name' => 'Allow Multiple',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'exclude_from_nesting' => true,
                'slug' => 'allow_multiple',
                'instructions' => 'Allow multiple selections?',
            ],

        ]);
    }

    public function getFilterValues($model, $field)
    {
        return $this->options($model, $field);
    }

    // public $view = 'components.fields.select';

    public function options($model, $field)
    {
        // if get"$field->slug"Options is defined on the model, use that
        if (method_exists($model, 'get'.ucfirst($field['slug']).'Options')) {
            return $model->{'get'.ucfirst($field['slug']).'Options'}();
        }

        // return the options defined in the field
        return $field['options'] ?? [];
    }
}
```

## ./Checkbox.php
```
<?php

namespace Aura\Base\Fields;

class Checkbox extends Field
{
    public $edit = 'aura::fields.checkbox';

    public $optionGroup = 'Choice Fields';

    // public $view = 'components.fields.checkbox';

    public function get($class, $value, $field = null)
    {
        // dd($value);
        if ($value === null || $value === false) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Checkbox',
                'name' => 'Checkbox',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'checkbox',
                'style' => [],
            ],

            [
                'label' => 'options',
                'name' => 'options',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',

            ],
            [
                'name' => 'Key',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'key',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'exclude_from_nesting' => true,
                'slug' => 'default',
            ],
        ]);
    }

    public function options($model, $field)
    {
        // if get"$field->slug"Options is defined on the model, use that
        if (method_exists($model, 'get'.ucfirst($field['slug']).'Options')) {
            return $model->{'get'.ucfirst($field['slug']).'Options'}();
        }

        // return the options defined in the field
        return $field['options'] ?? [];
    }

    public function set($post, $field, $value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
```

## ./HasMany.php
```
<?php

namespace Aura\Base\Fields;

use Aura\Base\Models\Meta;
use Aura\Flows\Resources\Operation;

class HasMany extends Field
{
    public $edit = 'aura::fields.has-many';

    public bool $group = false;

    public $optionGroup = 'Relationship Fields';

    public string $type = 'relation';

    public $view = 'aura::fields.has-many-view';

    public function get($class, $value, $field = null)
    {
        $relationshipQuery = $this->relationship($class, $value);

        return $relationshipQuery->get();
    }

    public function getRelation($model, $field)
    {
        if (! $model->exists) {
            return collect();
        }

        $relationshipQuery = $this->relationship($model, $field);

        return $relationshipQuery->get();
    }

    public function queryFor($query, $component)
    {

        $field = $component->field;
        $model = $component->model;

        if (optional($component)->parent) {
            $field = $component->parent->fieldBySlug($field['slug']);
            $model = $component->parent;
        }

        // if $field['relation'] is set, check if meta with key $field['relation'] exists, apply whereHas meta to the query

        // if optional($field)['relation'] is closure
        if (is_callable(optional($field)['relation'])) {
            return $field['relation']($query, $model);
        }

        if (isset($component->field['resource'])) {
            $relationship = $this->relationship($model, $field);

            return $relationship->getQuery();
        }

        if (optional($component->field)['relation']) {
            if ($model->id) {
                return $query->whereHas('meta', function ($query) use ($field, $model) {
                    $query->where('key', $field['relation'])
                        ->where('value', $model->id);
                });
            }
        }

        if ($model instanceof \Aura\Base\Resources\User) {
            return $query;
        }

        if ($model instanceof \Aura\Base\Resources\Team) {
            return $query;
        }

        if ($model instanceof \Aura\Flows\Resources\Flow) {
            return $query->where('flow_id', $model->id);
        }

        if ($model instanceof \Aura\Flows\Resources\Flow) {
            return $query->where('flow_id', $model->id);
        }

        if ($model instanceof Operation) {
            return $query->where('operation_id', $model->id);
        }

        if ($model instanceof \Aura\Flows\Resources\FlowLog) {
            return $query->where('flow_log_id', $model->id);
        }

        return $query->where('user_id', $model->id);
    }

    public function relationship($model, $field)
    {
        if (isset($field['column'])) {
            return $model->hasMany($field['resource'], $field['column']);
        }

        return $model
            ->morphedByMany($field['resource'], 'related', 'post_relations', 'resource_id', 'related_id')
            ->withTimestamps()
            ->withPivot('related_type')
            ->wherePivot('related_type', $field['resource']);
    }
}
```

## ./LivewireComponent.php
```
<?php

namespace Aura\Base\Fields;

class LivewireComponent extends Field
{
    public $edit = 'aura::fields.livewire-component';

    // public $view = 'components.fields.livewire-component';

    public string $type = 'livewire-component';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Component',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'component-tab',
                'style' => [],
            ],
            [
                'name' => 'Component',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'component',
            ],
        ]);
    }
}
```

## ./Text.php
```
<?php

namespace Aura\Base\Fields;

class Text extends Field
{
    public $edit = 'aura::fields.text';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Text',
                'name' => 'Text',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'text-tab',
                'style' => [],
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
            [
                'name' => 'Autocomplete',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'autocomplete',
            ],
            [
                'name' => 'Prefix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'prefix',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Suffix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'suffix',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Max Length',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'max_length',
                'style' => [
                    'width' => '100',
                ],

            ],
        ]);
    }
}
```

## ./Image.php
```
<?php

namespace Aura\Base\Fields;

use Aura\Base\Resources\Attachment;

class Image extends Field
{
    public $edit = 'aura::fields.image';

    public $optionGroup = 'Media Fields';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        if (! $value) {
            return;
        }

        try {
            $values = is_array($value) ? $value : [$value];

            $firstImageValue = array_shift($values);
            $attachment = Attachment::find($firstImageValue);

            if ($attachment) {
                $url = $attachment->thumbnail('xs');
                $imageHtml = "<img src='{$url}' class='object-cover w-32 h-32 rounded-lg shadow-lg'>";
            } else {
                return $value;
            }

            $additionalImagesCount = count($values);
            $circleHtml = '';
            if ($additionalImagesCount > 0) {
                $circleHtml = "<div class='flex justify-center items-center w-10 h-10 font-bold text-center text-gray-800 bg-gray-200 rounded-full'>+{$additionalImagesCount}</div>";
            }

            return "<div class='flex items-center space-x-2'>{$imageHtml}{$circleHtml}</div>";
        } catch (\Exception $e) {
            // Handle the exception or return a default value
            // Log the error message if logging is desired
            // error_log($e->getMessage());
        }
    }

    public function get($class, $value, $field = null)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }

        return $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Image',
                'name' => 'Image',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'image-tab',
                'style' => [],
            ],

            [
                'name' => 'Use Media Manager',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'slug' => 'use_media_manager',
            ],

            // min and max numbers for allowed number of files
            [
                'name' => 'Min Files',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'min_files',
                'instructions' => 'Minimum number of files allowed',
            ],
            [
                'name' => 'Max Files',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'max_files',
                'instructions' => 'Maximum number of files allowed',
            ],

            // allowed file types
            [
                'name' => 'Allowed File Types',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'allowed_file_types',
                'instructions' => 'Comma separated list of allowed file types. Example: jpg, png, gif',
            ],

        ]);
    }

    public function set($post, $field, $value)
    {
        return json_encode($value);
    }
}
```

## ./HasOne.php
```
<?php

namespace Aura\Base\Fields;

class HasOne extends AdvancedSelect
{
    public bool $api = true;

    public $edit = 'aura::fields.has-one';

    public bool $group = false;

    public bool $multiple = false;

    public $optionGroup = 'Relationship Fields';

    public bool $searchable = true;

    public string $type = 'relation';
}
```

