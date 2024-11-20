## ./Pipeline/RemoveValidationAttribute.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class RemoveValidationAttribute implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $fields->map(function ($field) {
            if (isset($field['validation'])) {
                unset($field['validation']);
            }

            return $field;
        });

        return $next($fields);
    }
}
```

## ./Pipeline/BuildTreeFromFields.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class BuildTreeFromFields implements Pipe
{
    public function buildTree(array &$fields, $parentId = 0)
    {
        $branch = [];

        foreach ($fields as &$field) {
            if ($field['_parent_id'] == $parentId) {
                $children = $this->buildTree($fields, $field['_id']);
                if ($children) {
                    $field['fields'] = array_values($children);
                }
                $branch[$field['_id']] = $field;
                unset($field);
            }
        }

        return $branch;
    }

    public function handle($fields, Closure $next)
    {
        $array = $fields->toArray();

        $tree = $this->buildTree($array);

        $tree = array_values($tree);

        return $next($tree);
    }
}
```

## ./Pipeline/FilterViewFields.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class FilterViewFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $fields->filter(function ($field) {
            // if there is a on_view = false, filter it out
            if (optional($field)['on_view'] === false) {
                return false;
            }

            return true;
        });

        return $next($fields);
    }
}
```

## ./Pipeline/ApplyParentConditionalLogic.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class ApplyParentConditionalLogic implements Pipe
{
    public function getParentIds($parentIdMap, $id): array
    {
        $parentIds = [];
        $currentId = $id;

        while (isset($parentIdMap[$currentId])) {
            $currentId = $parentIdMap[$currentId];
            $parentIds[] = $currentId;
        }

        return $parentIds;
    }

    public function handle($fields, Closure $next)
    {
        // Build a map of field IDs to parent IDs
        $parentIdMap = $fields->pluck('_parent_id', '_id');

        // Build a map of field IDs to conditional logics
        $conditionalLogicMap = $fields->pluck('conditional_logic', '_id');

        // Process fields
        $fields = $fields->map(function ($field) use ($parentIdMap, $conditionalLogicMap) {
            $parentIds = $this->getParentIds($parentIdMap, $field['_id']);

            // Merge Conditional Logic of parent IDs with current conditional Logic
            $parentConditionalLogics = collect($parentIds)
                ->map(fn ($id) => $conditionalLogicMap[$id] ?? [])
                ->filter()
                ->flatten(1);

            $field['conditional_logic'] = $parentConditionalLogics
                ->merge($field['conditional_logic'] ?? [])
                ->toArray();

            return $field;
        });

        return $next($fields);
    }
}
```

## ./Pipeline/ApplyWrappers.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;
use Illuminate\Support\Str;

class ApplyWrappers implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $newFields = [];
        $addedGlobalWrappers = [];
        $addedNonGlobalWrappers = [];

        foreach ($fields as $field) {
            // If no wrapper, add the field as is
            if (!$field['field']->wrapper) {
                $newFields[] = $field;
                continue;
            }

            $isGlobal = isset($field['global']) && $field['global'] === true;
            $relevantWrappers = $isGlobal ? $addedGlobalWrappers : $addedNonGlobalWrappers;

            // Add wrapper if:
            // 1. We haven't added this type of wrapper (global/non-global) before
            // 2. OR if wrap is explicitly set to true
            if (!in_array($field['field']->wrapper, $relevantWrappers) || optional($field)['wrap'] === true) {
                // Add the wrapper field
                $wrapperField = [
                    'label' => $field['field']->wrapper,
                    'name'  => $field['field']->wrapper,
                    'type'  => $field['field']->wrapper,
                    'slug'  => Str::slug($field['field']->wrapper),
                    'field' => app($field['field']->wrapper),
                ];

                // Set global flag on wrapper if field is global
                if ($isGlobal) {
                    $wrapperField['global'] = true;
                    $addedGlobalWrappers[] = $field['field']->wrapper;
                } else {
                    $addedNonGlobalWrappers[] = $field['field']->wrapper;
                }

                $newFields[] = $wrapperField;
            }

            // Add the field as is
            $newFields[] = $field;
        }

        // Return a collection if the input was a collection
        if ($fields instanceof \Illuminate\Support\Collection) {
            $newFields = collect($newFields);
        }

        // Pass the new fields to the next pipe
        return $next($newFields);
    }
}   
```

## ./Pipeline/MapFields.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class MapFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        return $next($fields->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        }));
    }
}
```

## ./Pipeline/FilterEditFields.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class FilterEditFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $fields->filter(function ($field) {
            if (optional($field)['on_forms'] === false) {
                return false;
            }

            if (optional($field)['on_edit'] === false) {
                return false;
            }

            return true;
        })->values();

        return $next($fields);
    }
}
```

## ./Pipeline/ApplyParentDisplayAttributes.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class ApplyParentDisplayAttributes implements Pipe
{
    public function getParentIds($fields, $id): array
    {
        $parentIds = [];

        // Find the field with the given id
        $field = $fields->firstWhere('_id', $id);

        // If the field has a parent id, add it to the array and recursively
        // get the parent ids of the parent field
        if ($field['_parent_id'] !== null) {
            $parentIds[] = $field['_parent_id'];
            $parentIds = array_merge($parentIds, $this->getParentIds($fields, $field['_parent_id']));
        }

        return $parentIds;
    }

    public function handle($fields, Closure $next)
    {
        // Foreach $fields as $field, get all parent IDs
        $fields = $fields->map(function ($field) use ($fields) {
            $parentIds = $this->getParentIds($fields, $field['_id']);

            $field['on_view'] = isset($field['on_view']) ? $field['on_view'] : $fields->whereIn('_id', $parentIds)->pluck('on_view')->flatten(1)->filter(fn ($i) => ! is_null($i))->first();

            $field['on_forms'] = isset($field['on_forms']) ? $field['on_forms'] : $fields->whereIn('_id', $parentIds)->pluck('on_forms')->flatten(1)->filter(fn ($i) => ! is_null($i))->first();

            $field['on_edit'] = isset($field['on_edit']) ? $field['on_edit'] : $fields->whereIn('_id', $parentIds)->pluck('on_edit')->flatten(1)->filter(fn ($i) => ! is_null($i))->first();

            $field['on_create'] = isset($field['on_create']) ? $field['on_create'] : $fields->whereIn('_id', $parentIds)->pluck('on_create')->flatten(1)->filter(fn ($i) => ! is_null($i))->first();

            $field['on_index'] = isset($field['on_index']) ? $field['on_index'] : $fields->whereIn('_id', $parentIds)->pluck('on_index')->flatten(1)->filter(fn ($i) => ! is_null($i))->first();

            return $field;
        });

        return $next($fields);
    }
}
```

## ./Pipeline/ApplyLayoutFields.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class ApplyLayoutFields implements Pipe
{
    public function createMainPanel(array $layouts): array
    {
        if (count($layouts) == 0) {
            $layouts[] = [
                'name' => 'Main Panel',
                'slug' => 'main-panel',
                'type' => 'Aura\Base\Fields\Panel',
                'field' => app('Aura\Base\Fields\Panel'),
                'field_type' => 'layout',
            ];
        }

        return $layouts;
    }

    public function createTabs(array $layouts, int|string|null $lastKey): array
    {
        $layouts[$lastKey]['fields'][] = [
            'name' => 'Tabs',
            'slug' => 'tabs',
            'type' => 'Aura\Base\Fields\Tabs',
            'field' => app('Aura\Base\Fields\Tabs'),
            'field_type' => 'tabs',
        ];

        return $layouts;
    }

    public function handle($fields, Closure $next)
    {
        $layouts = [];
        $isTab = null;
        $tabsKey = null;

        $fields = $fields->values();

        // dd('hier', $fields);

        foreach ($fields as $key => $field) {
            if ($field['field_type'] == 'layout') {
                $layouts[] = $field;
                $isTab = null;
                $tabsKey = null;

                continue;
            }

            // Create Main Panel, if there is no Main Panel yet
            $layouts = $this->createMainPanel($layouts);

            $lastKey = array_key_last($layouts);

            if (! is_null($isTab) && $field['field_type'] != 'tab') {
                $layouts[$lastKey]['fields'][$tabsKey]['fields'][$isTab]['fields'][] = $field;

                continue;
            }

            if ($field['field_type'] == 'tab' && is_null($isTab) && is_null($tabsKey)) {
                $layouts = $this->createTabs($layouts, $lastKey);
                $tabsKey = array_key_last($layouts[$lastKey]['fields']);
            }

            if ($field['field_type'] == 'tab') {
                $layouts[$lastKey]['fields'][$tabsKey]['fields'][] = $field;
                $isTab = array_key_last($layouts[$lastKey]['fields'][$tabsKey]['fields']);
            } else {
                $layouts[$lastKey]['fields'][] = $field;
            }
        }

        return $next($layouts);
    }
}
```

## ./Pipeline/ApplyGroupedInputs.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class ApplyGroupedInputs implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $group = 0;
        $groupLevel = '0';
        $groupedFields = false;

        $fields = $fields->map(function ($item, $key) use (&$group, &$groupLevel, &$groupedFields) {
            if ($item['field']->type == 'tab') {
                $group++;
                $groupLevel = strval($group);
            }

            if ($item['field']->type == 'panel') {
                // split string
                $groupLevel = explode('.', $groupLevel);
                // take only first two elements
                $groupLevel = array_slice($groupLevel, 0, 2);
                // add 1 to last element
                $groupLevel[count($groupLevel) - 1] = $groupLevel[count($groupLevel) - 1] + 1;
                // join string
                $groupLevel = implode('.', $groupLevel);
            }

            if (! $item['field']->group) {
                // split string
                $groupLevel = explode('.', $groupLevel);
                // add 1 to last element
                $groupLevel[count($groupLevel) - 1] = $groupLevel[count($groupLevel) - 1] + 1;
                // join string
                $groupLevel = implode('.', $groupLevel);
            }
            if ($item['field']->group && $item['field']->type != 'panel') {
                // split string
                $groupLevel = explode('.', $groupLevel);
                // add 1 to last element
                $groupLevel[count($groupLevel) - 1] = $groupLevel[count($groupLevel) - 1] + 1;
                // join string
                $groupLevel = implode('.', $groupLevel);
            }

            $item['group'] = $group;
            $item['groupLevel'] = $groupLevel;

            if ($item['field']->group) {
                $item['fields'] = [];
            }

            if ($item['field']->group && $item['field']->type != 'panel') {
                $groupLevel = $groupLevel.'.0';
            }

            if ($item['field']->type == 'panel') {
                $groupLevel = $groupLevel.'.0';
            }

            if ($item['field']->type == 'tab') {
                $groupLevel = $groupLevel.'.0';
            }

            return $item;
        });

        $nestedArray = $fields->reduce(function ($nestedArray, $item) {
            $levels = explode('.', $item['groupLevel']);

            $currentLevel = &$nestedArray;

            foreach ($levels as $level) {
                if (! isset($currentLevel[$level])) {
                    $currentLevel[$level] = $item;
                }

                $currentLevel = &$currentLevel[$level]['fields'];
            }

            return $nestedArray;
        }, []);

        $nestedArray = collect($nestedArray)->map(function ($item, $key) {
            $item['fields'] = collect($item['fields'])->values();

            return $item;
        })->values();

        return $nestedArray;

        return $next($fields);
    }
}
```

## ./Pipeline/Pipe.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

interface Pipe
{
    public function handle($content, Closure $next);
}
```

## ./Pipeline/FilterCreateFields.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class FilterCreateFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $fields->filter(function ($field) {
            if (optional($field)['on_forms'] === false) {
                return false;
            }

            if (optional($field)['on_create'] === false) {
                return false;
            }

            return true;
        })->values();

        return $next($fields);
    }
}
```

## ./Pipeline/TransformSlugs.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class TransformSlugs implements Pipe
{
    public function handle($fields, Closure $next)
    {
        // Create a map of field IDs to fields for quick lookup
        $fieldsById = $fields->keyBy('_id');

        // Map over fields to adjust slugs based on parent
        $fields = $fields->map(function ($item) use ($fieldsById) {
            // Get the parent field using the lookup map
            $parent = $fieldsById->get($item['_parent_id']);

            // If the parent is a group, prepend the parent slug to the item slug
            if (isset($parent->field) && $parent->field->type === 'group') {
                $item['slug'] = $parent->slug.'.'.$item['slug'];
            }

            return $item;
        });

        return $next($fields);
    }
}
```

## ./Pipeline/RemoveClosureAttributes.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class RemoveClosureAttributes implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $fields = $fields->map(function ($field) {
            if (isset($field['validation']) && $field['validation'] instanceof Closure) {
                unset($field['validation']);
            }

            if (isset($field['relation']) && $field['relation'] instanceof Closure) {
                unset($field['relation']);
            }

            if (isset($field['conditional_logic']) && $field['conditional_logic'] instanceof Closure) {
                unset($field['conditional_logic']);
            }

            return $field;
        });

        return $next($fields);
    }
}
```

## ./Pipeline/ApplyTabs.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class ApplyTabs implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $tabsAdded = 0;
        $added = false;
        $currentParent = null;
        $addedTabsToPanel = false;

        foreach ($fields as $key => $field) {
            if ($field['type'] === 'Aura\Base\Fields\Panel') {
                $currentParent = $field;
                $addedTabsToPanel = false;
            }

            // Add it to first Tabs
            if ($field['type'] === 'Aura\Base\Fields\Tab' && ! $added) {
                $fields->splice($key + $tabsAdded, 0, [
                    [
                        'label' => 'Tabs',
                        'name' => 'Tabs',
                        'global' => (bool) optional($field)['global'],
                        'type' => 'Aura\\Base\\Fields\\Tabs',
                        'slug' => 'tabs',
                        'style' => [],
                    ],
                ]);
                $added = true;
                $tabsAdded++;
                $addedTabsToPanel = true;
            }

            // Add it to first Tabs in Panels
            if ($currentParent && ! optional($field)['global']) {
                if ($field['type'] === 'Aura\Base\Fields\Tab' && ! $addedTabsToPanel) {
                    $fields->splice($key + $tabsAdded, 0, [
                        [
                            'label' => 'Tabs',
                            'name' => 'Tabs',
                            'global' => (bool) optional($field)['global'],
                            'type' => 'Aura\\Base\\Fields\\Tabs',
                            'slug' => 'tabs',
                            'style' => [],
                        ],
                    ]);
                    $addedTabsToPanel = true;
                    $tabsAdded++;
                }
            }
        }

        // to the next pipe
        return $next($fields);
    }
}
```

## ./Pipeline/DoNotDeferConditionalLogic.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;

class DoNotDeferConditionalLogic implements Pipe
{
    public function handle($fields, Closure $next)
    {
        // Get all conditional logic and pluck fields
        $conditionalLogicSlugs = $fields->pluck('conditional_logic')->flatten(1)->pluck('field')->toArray();

        // We need to set the defer property to false for all fields that are used in conditional logic
        $fields = $fields->map(function ($field) use ($conditionalLogicSlugs) {

            if (in_array(optional($field)['slug'], $conditionalLogicSlugs)) {
                $field['defer'] = false;
            }

            return $field;
        });

        return $next($fields);
    }
}
```

## ./Pipeline/AddIdsToFields.php
```
<?php

namespace Aura\Base\Pipeline;

use Closure;
use InvalidArgumentException;

class AddIdsToFields implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $currentParent = null;
        $globalTabs = null;
        $parentPanel = null;
        $parentTab = null;

        $fields = collect($fields)->values()->map(function ($item, $key) use (&$currentParent, &$globalTabs, &$parentPanel, &$parentTab) {
            $item['_id'] = $key + 1;

            $shouldNotBeNested = ! empty(optional($item)['exclude_from_nesting']) && $item['exclude_from_nesting'] === true;

            if ($shouldNotBeNested) {
                // Set the parent ID to the one before the current parent if it's set, or null
                $item['_parent_id'] = $currentParent ? $currentParent['_parent_id'] : null;

                if ($item['field']->group === true) {
                    $currentParent = $item;
                }

                // Try
                $parentPanel = false;
                $currentParent = $item;

                return $item;
            }

            if (optional($item)['global'] === true && ! $globalTabs) {
                if ($item['field']->type == 'tabs') {
                    $globalTabs = $item;
                }

                $item['_parent_id'] = null;
                $currentParent = $item;
                $parentPanel = null;

                return $item;
            }

            if ($item['field']->type !== 'panel' && $item['field']->group === true) {
                if (optional($item)['global']) {

                    // If type = group
                    if ($item['field']->type === 'group') {
                        $item['_parent_id'] = $currentParent['_parent_id'];
                        $currentParent = $item;
                        $parentPanel = null;
                    } else {
                        $item['_parent_id'] = optional($globalTabs)['_id'];
                        $parentPanel = null;
                    }

                }
                // Same Level Grouping
                elseif (optional($currentParent)['type'] == $item['type']) {
                    // Easier Option for now, should refactor
                    $item['_parent_id'] = $currentParent['_parent_id'];
                }
                // Parent Tab
                elseif ($item['field']->type == 'tab') {
                    if ($parentTab) {
                        $item['_parent_id'] = $parentTab['_parent_id'];
                    } else {
                        $item['_parent_id'] = optional($currentParent)['_id'];
                    }
                    $parentTab = $item;
                }
                // Nested False
                elseif (optional($item)['nested'] === false) {
                    // dd($item);
                    $item['_parent_id'] = optional($currentParent)['_id'];
                }

                // If Tab is set to Global, set it to GlobalTabs
                else {
                    $item['_parent_id'] = optional($currentParent)['_id'];
                }

                if (optional($item)['nested'] === true) {
                    $item['_parent_id'] = optional($currentParent)['_id'];
                }

                $currentParent = $item;
            } elseif ($item['field']->type !== 'panel' && $item['field']->group === false) {
                $item['_parent_id'] = optional($currentParent)['_id'];
            } elseif ($item['field']->type == 'panel') {
                if (optional($item)['global']) {
                    $item['_parent_id'] = null;
                    $parentPanel = null;
                    $currentParent = null;
                }

                if ($parentPanel) {
                    $item['_parent_id'] = $parentPanel['_parent_id'];
                } else {
                    $item['_parent_id'] = optional($currentParent)['_id'];
                }

                $currentParent = $item;
                $parentPanel = $item;
                $parentTab = null;
            } else {

                throw new InvalidArgumentException('Unexpected field configuration.');
            }

            return $item;
        });

        return $next($fields);
    }
}
```

## ./TransformColor.php
```
<?php

namespace Aura\Base;

class TransformColor
{
    public static function hexToRgb($hex, $alpha = false)
    {
        $hex = str_replace('#', '', $hex);
        $length = strlen($hex);
        $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
        $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
        $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
        if ($alpha) {
            $rgb['a'] = $alpha;
        }

        return $rgb['r'].' '.$rgb['g'].' '.$rgb['b'];
    }
}
```

## ./AuraFake.php
```
<?php

namespace Aura\Base;

class AuraFake extends Aura
{
    public $model;

    public function findResourceBySlug($slug)
    {
        if ($this->model) {
            return $this->model;
        }

        return $slug;
    }

    public function setModel($model)
    {
        $this->model = $model;

        $slug = $model->getSlug();

        Aura::registerRoutes($slug);

        Aura::clearRoutes();
    }
}
```

## ./Collection/MetaCollection.php
```
<?php

namespace Aura\Base\Collection;

use Illuminate\Database\Eloquent\Collection;

/**
 * Class MetaCollection
 */
class MetaCollection extends Collection
{
    /**
     * @param  string  $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get($key)
    {
        // dd('hier', in_array($key, static::$proxies));
        if (in_array($key, static::$proxies)) {
            return parent::__get($key);
        }

        if (isset($this->items) && count($this->items)) {
            $meta = $this->first(function ($meta) use ($key) {
                return $meta->key === $key;
            });

            return $meta ? $meta->value : null;
        }
    }

    /**
     * @param  string  $name
     * @return bool
     */
    public function __isset($name)
    {
        return ! is_null($this->__get($name));
    }
}
```

## ./Traits/ProfileFields.php
```
<?php

namespace Aura\Base\Traits;

use Illuminate\Validation\Rules\Password;

trait ProfileFields
{
    public function getProfileFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Details',
                'slug' => 'tab-user',
                'global' => true,
            ],
            [
                'name' => 'Personal Infos',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-details',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Email',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|email',
                'on_index' => true,
                'slug' => 'email',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Password',
                'slug' => 'tab-password',
                'global' => true,
            ],
            [
                'name' => 'Change Password',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-details',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Current Password',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => ['required_with:form.fields.password', 'current_password'],
                'slug' => 'current_password',
                'on_index' => false,
            ],
            [
                'name' => 'New Password',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
                'slug' => 'password',
                'on_index' => false,
            ],
            [
                'name' => 'Confirm Password',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => ['required_with:form.fields.password', 'same:form.fields.password'],
                'slug' => 'password_confirmation',
                'on_index' => false,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => '2FA',
                'slug' => '2fa',
                'global' => true,
            ],
            [
                'name' => 'Two Factor Authentication',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-2fa',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => '2FA',
                'type' => 'Aura\\Base\\Fields\\LivewireComponent',
                'component' => 'aura::user-two-factor-authentication-form',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => '2fa',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Delete',
                'slug' => 'delete-tab',
                'global' => true,
            ],
            [
                'name' => 'Delete Account',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-delete-panel',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => '2FA',
                'type' => 'Aura\\Base\\Fields\\View',
                'view' => 'aura::profile.delete-user-form',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => '2fa',
            ],

        ];
    }
}
```

## ./Traits/MediaFields.php
```
<?php

namespace Aura\Base\Traits;

use Illuminate\Support\Str;

trait MediaFields
{
    public function getField($slug)
    {
        return $this->form['fields'][$slug];
    }

    public function removeMediaFromField($slug, $id)
    {
        $field = $this->getField($slug);

        $field = collect($field)->filter(function ($value) use ($id) {
            return $value != $id;
        })->values()->toArray();

        $this->updateField([
            'slug' => $slug,
            'value' => $field,
        ]);

        // Emit Event selectedMediaUpdated
        $this->dispatch('selectedMediaUpdated', [
            'slug' => $slug,
            'value' => $field,
        ]);
    }

    public function reorderMedia($slug, $ids)
    {
        $ids = collect($ids)->map(function ($id) {
            return Str::after($id, '_file_');
        })->toArray();

        // emit update Field
        $this->updateField([
            'slug' => $slug,
            'value' => $ids,
        ]);
    }

    public function updateField($data)
    {
        $this->form['fields'][$data['slug']] = $data['value'];

        $this->dispatch('fieldUpdated', [
            'slug' => $data['slug'],
            'value' => $data['value'],
        ]);

        $this->dispatch('selectedMediaUpdated', [
            'slug' => $data['slug'],
            'value' => $data['value'],
        ]);
    }
}
```

## ./Traits/InteractsWithFields.php
```
<?php

namespace Aura\Base\Traits;

trait InteractsWithFields
{
    public function getCreateFieldsProperty()
    {
        return $this->model->createFields();
    }

    public function getEditFieldsProperty()
    {
        return $this->model->editFields();
    }

    public function getFieldsProperty()
    {
        $fields = $this->model->mappedFields();

        return $this->model->fieldsForView($fields);
    }

    public function getViewFieldsProperty()
    {
        return $this->model->viewFields();
    }

    public function validationAttributes()
    {
        $attributes = [];

        foreach ($this->model->inputFields() as $field) {
            $attributes['form.fields.'.$field['slug']] = $field['slug'];
        }

        return $attributes;
    }
}
```

## ./Traits/SaveFields.php
```
<?php

namespace Aura\Base\Traits;

use Aura\Base\Events\SaveFields as SaveFieldsEvent;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;

trait SaveFields
{
    public function saveFields($fields)
    {
        $fieldsWithIds = $fields;

        // Unset Mapping of Fields
        foreach ($fields as &$field) {
            unset($field['field']);
            unset($field['field_type']);
            unset($field['_id']);
            unset($field['_parent_id']);
        }

        $a = new \ReflectionClass($this->model::class);

        $filePath = $a->getFileName();

        if (file_exists($filePath)) {
            $file = file_get_contents($filePath);

            $replacement = Aura::varexport($this->setKeysToFields($fields), true);

            preg_match('/function\s+getFields\s*\((?:[^()]*?)\s*\)\s*(?<functionBody>{(?:[^{}]+|(?-1))*+})/ms', $file, $matches, PREG_OFFSET_CAPTURE);

            if (isset($matches['functionBody'])) {
                $functionBody = $matches['functionBody'][0];
                $functionBodyOffset = $matches['functionBody'][1];

                preg_match('/return\s+(\[.*\]);/ms', $functionBody, $matches2);

                if (isset($matches2[1])) {

                    $newFunctionBody = Str::replace(
                        $matches2[1],
                        $replacement,
                        $functionBody
                    );

                    $newFile = substr_replace(
                        $file,
                        $newFunctionBody,
                        $functionBodyOffset,
                        strlen($functionBody)
                    );

                    file_put_contents($filePath, $newFile);

                    $this->runPint($filePath);
                } else {
                    // Handle the case where the return statement is not found
                    // You may want to add the return statement if it's missing
                    // For now, we'll notify that the return statement was not found
                    $this->notify('Return statement not found in getFields().');
                }
            } else {
                // Handle the case where getFields() function is not found
                $this->notify('Function getFields() not found.');
            }
        }

        // Trigger the event to change the database schema
        event(new SaveFieldsEvent($fieldsWithIds, $this->mappedFields, $this->model));

        // $this->dispatch('refreshComponent');

        $this->notify('Saved successfully.');
    }

    public function saveProps($props)
    {
        $a = new \ReflectionClass($this->model::class);

        $file = file_get_contents($a->getFileName());

        $replacement = $props;

        $patterns = [
            'type' => "/type = ['\"]([^'\"]*)['\"]/",
            'group' => "/group = ['\"]([^'\"]*)['\"]/",
            'dropdown' => "/dropdown = ['\"]([^'\"]*)['\"]/",
            'sort' => '/sort = (.*?);/',
            'slug' => "/slug = ['\"]([^'\"]*)['\"]/",
            'icon' => "/public function getIcon\(\)[\n\r\s+]*\{[\n\r\s+]*return ['\"](.*?)['\"];/",
        ];

        $replacements = [
            'type' => "type = '".htmlspecialchars($replacement['type'])."'",
            'group' => "group = '".htmlspecialchars($replacement['group'])."'",
            'dropdown' => "dropdown = '".htmlspecialchars($replacement['dropdown'])."'",
            'sort' => 'sort = '.htmlspecialchars($replacement['sort']).';',
            'slug' => "slug = '".htmlspecialchars($replacement['slug'])."'",
            'icon' => "public function getIcon()\n    {\n        return '".($replacement['icon'])."';",
        ];

        $replaced = $file;

        $matches = [];
        foreach ($patterns as $key => $pattern) {
            preg_match($pattern, $file, $matches[$key]);
        }

        foreach ($patterns as $key => $pattern) {

            if ($key == 'icon') {
                // dump($replacements[$key]);
                $replaced = preg_replace($pattern, strip_tags($replacements[$key], '<a><altGlyph><altGlyphDef><altGlyphItem><animate><animateColor><animateMotion><animateTransform><circle><clipPath><color-profile><cursor><defs><desc><ellipse><feBlend><feColorMatrix><feComponentTransfer><feComposite><feConvolveMatrix><feDiffuseLighting><feDisplacementMap><feDistantLight><feFlood><feFuncA><feFuncB><feFuncG><feFuncR><feGaussianBlur><feImage><feMerge><feMergeNode><feMorphology><feOffset><fePointLight><feSpecularLighting><feSpotLight><feTile><feTurbulence><filter><font><font-face><font-face-format><font-face-name><font-face-src><font-face-uri><foreignObject><g><glyph><glyphRef><hkern><image><line><linearGradient><marker><mask><metadata><missing-glyph><mpath><path><pattern><polygon><polyline><radialGradient><rect><set><stop><style nonce="{{ csp_nonce() }}"><svg><switch><symbol><text><textPath><title><tref><tspan><use><view><vkern>'), $replaced);

                continue;
            }

            if (in_array($key, ['group', 'dropdown', 'sort'])) {

                if (isset($replacement[$key])) {
                    if (isset($matches[$key][1]) || (isset($matches[$key][0]) && $matches[$key][0] == "''")) {
                        // Replace existing line
                        $replaced = Str::replace(
                            $matches[$key][1],
                            htmlspecialchars($replacement[$key]),
                            $replaced
                        );
                    } else {

                        // Don't add empty lines
                        if (empty(htmlspecialchars($replacement[$key]))) {
                            continue;
                        }

                        // Add missing line
                        // if sort then add ?int instead of ?string
                        if ($key == 'sort') {
                            $lineToAdd = "protected static ?int \${$key} = ".htmlspecialchars($replacement[$key]).";\n";
                        } else {
                            $lineToAdd = "protected static ?string \${$key} = '".htmlspecialchars($replacement[$key])."';\n";
                        }
                        $replaced = preg_replace('/(public\s+static\s+\?string\s+\$slug\s+=\s+[^;\n]+;)/', "$1\n{$lineToAdd}", $replaced);
                    }
                }

                continue;
            }

            if (preg_match($pattern, $file) && isset($replacements[$key])) {
                $replaced = preg_replace($pattern, $replacements[$key], $replaced);
            }
        }

        file_put_contents($a->getFileName(), $replaced);

        // Run "pint" on the migration file
        // exec('./vendor/bin/pint '.$a->getFileName());

        // $this->notify('Saved Props successfully.');
    }

    public function setKeysToFields($fields)
    {
        $group = null;

        return $fields;

        return collect($fields)->mapWithKeys(function ($item, $key) use (&$group) {
            if (app($item['type'])->group) {
                $group = $item['slug'];

                return [$item['slug'] => $item];
            }

            return [$group.'.'.$item['slug'] => $item];
        })->toArray();
    }

    protected function runPint($migrationFile)
    {
        return;

        $command = [
            (new ExecutableFinder)->find('php', 'php', [
                '/usr/local/bin',
                '/opt/homebrew/bin',
            ]),

            'vendor/bin/pint', $migrationFile,
        ];

        $result = Process::path(base_path())->run($command);
    }
}
```

## ./Traits/InputFieldsHelpers.php
```
<?php

namespace Aura\Base\Traits;

use Illuminate\Pipeline\Pipeline;

trait InputFieldsHelpers
{
    protected static $fieldClassesBySlug = [];

    protected static $fieldsBySlug = [];

    protected static $fieldsCollectionCache = [];

    protected static $inputFieldSlugs = [];

    protected static $mappedFields = [];

    public function fieldBySlug($slug)
    {

        // Construct a unique key using the class name and the slug
        $key = get_class($this).'-'.$slug;

        // If this key exists in the static array, return the cached result
        if (isset(self::$fieldsBySlug[$key])) {
            return self::$fieldsBySlug[$key];
        }

        $result = $this->fieldsCollection()->firstWhere('slug', $slug);

        self::$fieldsBySlug[$key] = $result;

        return $result;
    }

    public function fieldClassBySlug($slug)
    {
        // Construct a unique key using the class name and the slug
        $key = get_class($this).'-'.$slug;

        // If this key exists in the static array, return the cached result
        if (isset(self::$fieldClassesBySlug[$key])) {
            return self::$fieldClassesBySlug[$key];
        }

        // Otherwise, perform the original operation
        $field = $this->fieldBySlug($slug);
        $result = false;

        if (optional($field)['type']) {
            $result = app($field['type']);
        }

        // Store the result in the static array
        self::$fieldClassesBySlug[$key] = $result;

        // Return the result
        return $result;
    }

    public function fieldsCollection()
    {
        // return collect($this->getFields());
        $class = get_class($this);

        if (isset(self::$fieldsCollectionCache[$class])) {
            return self::$fieldsCollectionCache[$class];
        }

        self::$fieldsCollectionCache[$class] = collect($this->getFields());

        return self::$fieldsCollectionCache[$class];
    }

    public function findBySlug($array, $slug)
    {
        foreach ($array as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
            if (isset($item['fields'])) {
                $result = $this->findBySlug($item['fields'], $slug);
                if ($result) {
                    return $result;
                }
            }
        }
    }

    public function getFieldSlugs()
    {
        return $this->fieldsCollection()->pluck('slug');
    }

    public function getFieldValue($key)
    {
        // dd('test', $key, $this->fieldBySlug($key), $this->meta->$key);
        return $this->fieldClassBySlug($key)->get($this->fieldBySlug($key), $this->meta->$key);
    }

    public function groupedFieldBySlug($slug)
    {
        $fields = $this->getGroupedFields();

        return $this->findBySlug($fields, $slug);
    }

    public function indexHeaderFields()
    {
        return $this->getFieldsBeforeTree()->filter(fn ($item) => in_array($item['field_type'], ['input', 'index']));
    }

    public function inputFields()
    {
        // dump($this->getFieldsBeforeTree()->filter(fn ($item) => in_array($item['field_type'], ['input'])));
        return $this->getFieldsBeforeTree()->filter(fn ($item) => in_array($item['field_type'], ['input']));
    }

    public function inputFieldsSlugs()
    {
        $class = get_class($this);

        if (isset(self::$inputFieldSlugs[$class])) {
            return self::$inputFieldSlugs[$class];
        }

        self::$inputFieldSlugs[$class] = $this->inputFields()->pluck('slug')->toArray();

        return self::$inputFieldSlugs[$class];
    }

    public function mappedFieldBySlug($slug)
    {
        // dd($this->mappedFields(), $this->newFields);
        return $this->mappedFields()->firstWhere('slug', $slug);
    }

    public function mappedFields()
    {
        // mappedFields
        $class = get_class($this);

        if (isset(self::$mappedFields[$class])) {
            return self::$mappedFields[$class];
        }

        self::$mappedFields[$class] = $this->fieldsCollection()->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });

        return self::$mappedFields[$class];
    }

    public function sendThroughPipeline($fields, $pipes)
    {
        // dump('sendThroughPipeline');
        return app(Pipeline::class)
            ->send(clone $fields)
            ->through($pipes)
            ->thenReturn();
    }
}
```

## ./Traits/WithCustomPagination.php
```
<?php

namespace Aura\Base\Traits;

use Illuminate\Pagination\Paginator;
use Livewire\WithPagination;

trait WithCustomPagination
{
    use WithPagination;

    public function getPublicPropertiesDefinedBySubClass()
    {
        return tap(parent::getPublicPropertiesDefinedBySubClass(), function (&$props) {
            $props[$this->pageName()] = $this->{$this->pageName()};
        });
    }

    public function getQueryString()
    {
        return array_merge([$this->pageName() => ['except' => 1]], $this->queryString);
    }

    public function initializeWithPagination()
    {
        $this->{$this->pageName()} = $this->resolvePage();

        Paginator::currentPageResolver(function () {
            return $this->{$this->pageName()};
        });

        Paginator::defaultView($this->paginationView());
    }

    public function nextPage()
    {
        $this->setPage($this->{$this->pageName()} + 1);
    }

    public function pageName(): string
    {
        if (property_exists($this, 'pageName')) {
            if (! isset($this->{$this->pageName})) {
                $this->{$this->pageName} = 1;
            }

            return $this->pageName;
        } else {
            return 'page';
        }
    }

    public function previousPage()
    {
        $this->setPage($this->{$this->pageName()} - 1);
    }

    public function resolvePage()
    {
        return request()->query($this->pageName(), $this->{$this->pageName()});
    }

    public function setPage($page)
    {
        $this->{$this->pageName()} = $page;
    }
}
```

## ./Traits/DefaultFields.php
```
<?php

namespace Aura\Base\Traits;

trait DefaultFields
{
    public static function fields($key)
    {
        $fields = collect([
            [
                'name' => 'Created at',
                'slug' => 'created_at',
                'type' => 'Aura\\Base\\Fields\\Date',
                'validation' => '',
                'enable_time' => true,
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Updated at',
                'slug' => 'updated_at',
                'type' => 'Aura\\Base\\Fields\\Date',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'enable_time' => true,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'User',
                'slug' => 'user_id',
                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ]);

        return $fields->where('slug', $key)->first();
    }
}
```

## ./Traits/InitialPostFields.php
```
<?php

namespace Aura\Base\Traits;

use Illuminate\Support\Str;

trait InitialPostFields
{
    protected static function bootInitialPostFields()
    {
        static::saving(function ($post) {
            if (! $post->title && $post::usesTitle()) {
                $post->title = '';
            }

            if ($post instanceof \Aura\Base\Resources\User) {
                return;
            }

            if (! $post->content && ! $post::usesCustomTable()) {
                $post->content = '';
            }

            if (! $post->user_id && auth()->user()) {
                $post->user_id = auth()->user()->id;
            }

            if (config('aura.teams') && ! isset($post->team_id) && auth()->user()) {
                $post->team_id = auth()->user()->current_team_id;
            }

            if (! $post->type && ! $post::usesCustomTable()) {
                $post->type = $post::$type;
            }

            if ($post->getTable() == 'posts' && ! $post->slug) {
                $post->slug = Str::slug($post->title);
            }
        });
    }
}
```

## ./Traits/ConfirmsPasswords.php
```
<?php

namespace Aura\Base\Traits;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmPassword;

trait ConfirmsPasswords
{
    /**
     * The ID of the operation being confirmed.
     *
     * @var string|null
     */
    public $confirmableId = null;

    /**
     * The user's password.
     *
     * @var string
     */
    public $confirmablePassword = '';

    /**
     * Indicates if the user's password is being confirmed.
     *
     * @var bool
     */
    public $confirmingPassword = false;

    /**
     * Confirm the user's password.
     *
     * @return void
     */
    public function confirmPassword()
    {
        if (! app(ConfirmPassword::class)(app(StatefulGuard::class), Auth::user(), $this->confirmablePassword)) {
            throw ValidationException::withMessages([
                'confirmable_password' => [__('This password does not match our records.')],
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->dispatch('password-confirmed',
            id: $this->confirmableId,
        );

        $this->stopConfirmingPassword();
    }

    /**
     * Start confirming the user's password.
     *
     * @return void
     */
    public function startConfirmingPassword(string $confirmableId)
    {
        $this->resetErrorBag();

        if ($this->passwordIsConfirmed()) {
            return $this->dispatch('password-confirmed',
                id: $confirmableId
            );
        }

        $this->confirmingPassword = true;
        $this->confirmableId = $confirmableId;
        $this->confirmablePassword = '';

        $this->dispatch('confirming-password');
    }

    /**
     * Stop confirming the user's password.
     *
     * @return void
     */
    public function stopConfirmingPassword()
    {
        $this->confirmingPassword = false;
        $this->confirmableId = null;
        $this->confirmablePassword = '';
    }

    /**
     * Ensure that the user's password has been recently confirmed.
     *
     * @param  int|null  $maximumSecondsSinceConfirmation
     * @return void
     */
    protected function ensurePasswordIsConfirmed($maximumSecondsSinceConfirmation = null)
    {
        $maximumSecondsSinceConfirmation = $maximumSecondsSinceConfirmation ?: config('auth.password_timeout', 900);

        $this->passwordIsConfirmed($maximumSecondsSinceConfirmation) ? null : abort(403);
    }

    /**
     * Determine if the user's password has been recently confirmed.
     *
     * @param  int|null  $maximumSecondsSinceConfirmation
     * @return bool
     */
    protected function passwordIsConfirmed($maximumSecondsSinceConfirmation = null)
    {
        $maximumSecondsSinceConfirmation = $maximumSecondsSinceConfirmation ?: config('auth.password_timeout', 900);

        return (time() - session('auth.password_confirmed_at', 0)) < $maximumSecondsSinceConfirmation;
    }
}
```

## ./Traits/HasFields.php
```
<?php

namespace Aura\Base\Traits;

use Aura\Base\Pipeline\AddIdsToFields;
use Aura\Base\Pipeline\BuildTreeFromFields;
use Illuminate\Pipeline\Pipeline;

trait HasFields
{
    public function fieldsCollection()
    {
        return collect($this->getFields());
    }

    public function getFields()
    {
        return [];
    }

    public function getGroupedFields()
    {
        $fields = $this->mappedFields();

        return $this->sendThroughPipeline($fields, [
            // ApplyGroupedInputs::class, // Enes
            AddIdsToFields::class, // Bajram
            BuildTreeFromFields::class, // Bajram
        ]);
    }

    public function mappedFields()
    {
        return $this->fieldsCollection()->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });
    }

    public function sendThroughPipeline($fields, $pipes)
    {
        return app(Pipeline::class)
            ->send($fields)
            ->through($pipes)
            ->thenReturn();
    }

    public function validationAttributes()
    {
        $attributes = [];

        foreach ($this->model->inputFields() as $field) {
            $attributes['form.fields.'.$field['slug']] = $field['slug'];
        }

        return $attributes;
    }
}
```

## ./Traits/InputFieldsValidation.php
```
<?php

namespace Aura\Base\Traits;

trait InputFieldsValidation
{
    public function mapIntoValidationFields($item)
    {
        $map = [
            'validation' => $item['validation'] ?? '',
            'slug' => $item['slug'] ?? '',
        ];

        if (isset($item['fields'])) {
            $map['*'] = collect($item['fields'])->map(function ($item) {
                return $this->mapIntoValidationFields($item);
            })->toArray();
        }

        return $map;
    }

    public function resourceFieldValidationRules()
    {
        return collect($this->validationRules())->mapWithKeys(function ($value, $key) {
            return ['form.fields.'.$key => $value];
        })->toArray();
    }

    public function validationRules()
    {
        $subFields = [];

        $fields = $this->getFieldsBeforeTree()
            ->filter(fn ($item) => in_array($item['field_type'], ['input', 'repeater', 'group']))
            ->map(function ($item) use (&$subFields) {
                if (in_array($item['field_type'], ['repeater', 'group'])) {
                    $subFields[] = $item['slug'];

                    return $this->groupedFieldBySlug($item['slug']);
                }

                return $item;
            })
            ->map(function ($item) {
                return $this->mapIntoValidationFields($item);
            })
            ->mapWithKeys(function ($item, $key) use (&$subFields) {
                foreach ($subFields as $exclude) {
                    if (str($key)->startsWith($exclude.'.')) {
                        return [$exclude.'.*.'.$item['slug'] => $item['validation']];
                    }
                }

                return [$item['slug'] => $item['validation']];
            })
            ->toArray();

        return $fields;
    }
}
```

## ./Traits/FieldsOnComponent.php
```
<?php

namespace Aura\Base\Traits;

use Illuminate\Support\Arr;

trait FieldsOnComponent
{
    use InputFields;

    public function getFieldsProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
        ]);
    }
}
```

## ./Traits/RepeaterFields.php
```
<?php

namespace Aura\Base\Traits;

use Illuminate\Support\Arr;

trait RepeaterFields
{
    public function addRepeater($slug)
    {
        if (! optional($this->form['fields'])[$slug]) {
            return $this->form['fields'][$slug][] = [];
        }

        $last = Arr::last($this->form['fields'][$slug]);

        $keys = array_keys($last);

        $new = [];

        foreach ($keys as $key) {
            $new[$key] = '';
        }

        $this->form['fields'][$slug][] = $new;
    }

    public function moveRepeaterDown($slug, $key)
    {
        $array = $this->form['fields'][$slug];

        if ($key == count($array) - 1) {
            return;
        }

        $item = $array[$key];
        $array[$key] = $array[$key + 1];
        $array[$key + 1] = $item;

        $this->form['fields'][$slug] = $array;
    }

    public function moveRepeaterUp($slug, $key)
    {
        if ($key == 0) {
            return;
        }

        $array = $this->form['fields'][$slug];

        $item = $array[$key];
        $array[$key] = $array[$key - 1];
        $array[$key - 1] = $item;

        $this->form['fields'][$slug] = $array;
    }

    public function removeRepeater($slug, $key)
    {
        unset($this->form['fields'][$slug][$key]);

        // Reset values
        $this->form['fields'][$slug] = array_values($this->form['fields'][$slug]);
    }
}
```

## ./Traits/SaveMetaFields.php
```
<?php

namespace Aura\Base\Traits;

use Aura\Base\Models\Meta;
use Illuminate\Support\Str;

trait SaveMetaFields
{
    protected static function bootSaveMetaFields()
    {

        static::saving(function ($post) {

            //  ray('SaveMetaFields', $post->attributes)->red();

            if (isset($post->attributes['fields'])) {

                // Dont save Meta Fields if it is uses customTable
                if ($post->usesCustomTable() && ! $post->usesMeta()) {
                    unset($post->attributes['fields']);

                    return;
                }

                foreach ($post->attributes['fields'] as $key => $value) {
                    $key = (string) $key;

                    $class = $post->fieldClassBySlug($key);

                    // Do not continue if the Field is not found
                    if (! $class) {
                        continue;
                    }

                    // if there is a function set{Slug}Field on the model, use it
                    $method = 'set'.Str::studly($key).'Field';

                    if (method_exists($post, $method)) {
                        $post->saveMetaField([$key => $value]);

                        //$post = $post->{$method}($value);

                        continue;
                    }

                    // If the $class is a Password Field and the value is null, continue
                    if ($class instanceof \Aura\Base\Fields\Password && ($value === null || $value === '')) {

                        // If the password is available in the $post->attributes, unset it
                        if (isset($post->attributes[$key])) {
                            unset($post->attributes[$key]);
                        }

                        continue;
                    }

                    $field = $post->fieldBySlug($key);

                    if (isset($field['set']) && $field['set'] instanceof \Closure) {
                        $value = call_user_func($field['set'], $post, $field, $value);
                    }

                    if (method_exists($class, 'set')) {
                        $value = $class->set($post, $field, $value);
                    }

                    if ($class instanceof \Aura\Base\Fields\ID) {
                        // $post->attributes[$key] = $value;

                        // unset($post->attributes['fields'][$key]);

                        continue;
                    }

                    // If the field exists in the $post->getBaseFillable(), it should be safed in the table instead of the meta table
                    if (in_array($key, $post->getBaseFillable())) {
                        $post->attributes[$key] = $value;

                        continue;
                    }

                    if (in_array($key, $post->getFillable())) {
                        // Save the meta field to the model, so it can be saved in the Meta table
                        $post->saveMetaField([$key => $value]);
                    }

                    // Save the meta field to the model, so it can be saved in the Meta table
                    // $post->saveMetaField([$key => $value]);
                }

                unset($post->attributes['fields']);

                $post->clearFieldsAttributeCache();
            }

        });

        static::saved(function ($post) {
            if (isset($post->metaFields)) {

                foreach ($post->metaFields as $key => $value) {

                    // ray($key, $value)->red();

                    // if there is a function set{Slug}Field on the model, use it
                    $method = 'set'.Str::studly($key).'Field';

                    if (method_exists($post, $method)) {
                        $post = $post->{$method}($value);

                        continue;
                    }

                    $field = $post->fieldBySlug($key);
                    $class = $post->fieldClassBySlug((string) $key);

                    // if (isset($field['set']) && $field['set'] instanceof \Closure) {
                    //     // dd('here');
                    //     $value = call_user_func($field['set'], $post, $field, $value);
                    // }

                    if (method_exists($class, 'saved')) {
                        $value = $class->saved($post, $field, $value);

                        continue;
                    }

                    if ($post->usesMeta()) {
                        $post->meta()->updateOrCreate(['key' => $key], ['value' => $value]);
                    }

                }

                $post->fireModelEvent('metaSaved');
            }
        });
    }
}
```

## ./Traits/SaveFieldAttributes.php
```
<?php

namespace Aura\Base\Traits;

trait SaveFieldAttributes
{
    /**
     * Set Fields Attributes
     *
     * Take Fields Attributes and Put all fields from getFieldSlugs() in the Fields Column
     *
     * @param  $post
     * @return void
     */
    protected static function bootSaveFieldAttributes()
    {
        static::saving(function ($post) {

            // ray('SaveFieldAttributes', $post->attributes)->blue();

            if ($post->name == 'Test Post 1') {
                // dd($post)->red();
            }

            if (! optional($post->attributes)['fields']) {
                $post->attributes['fields'] = [];
            }

            collect($post->inputFieldsSlugs())->each(function ($slug) use ($post) {

                // ray($slug, array_key_exists( $slug, $post->attributes))->blue();

                if (array_key_exists($slug, $post->attributes)) {

                    $class = $post->fieldClassBySlug($slug);

                    if ($slug == 'password') {
                        // ray('Password Field', $class)->red();
                    }

                    // Do not continue if the Field is not found
                    if (! $class) {
                        return;
                    }

                    // Do not set password fields manually, since they would overwrite the hashed password
                    if ($class instanceof \Aura\Base\Fields\Password) {

                        // If the password is available in the $post->attributes, unset it
                        if (empty($post->attributes[$slug])) {
                            unset($post->attributes[$slug]);
                        }

                        return;
                    }

                    if ($class instanceof \Aura\Base\Fields\ID) {
                        return;
                    }

                    if (! array_key_exists($slug, $post->attributes['fields'])) {
                        $post->attributes['fields'][$slug] = $post->attributes[$slug];
                    }

                    // Set the field value into nested fields array if it contains a dot
                    if (strpos($slug, '.') !== false) {
                        self::setNestedFieldValue($post->attributes['fields'], $slug, $post->attributes[$slug]);
                        // Unset the attribute from the main attributes array
                        // unset($post->attributes[$slug]);
                        unset($post->attributes['fields'][$slug]);
                    } else {
                        // If no dot, set the attribute directly in fields
                        // $post->attributes['fields'][$slug] = $post->attributes[$slug];
                    }
                }

                if ($slug == 'title') {
                    return;
                }

                // Dont unset Field if it is in baseFillable
                if (in_array($slug, $post->baseFillable)) {
                    return;
                }

                // ray($post->usesCustomTable(), $post->usesCustomMeta());
                // Dont unset Field if it is uses customTable
                if ($post->usesCustomTable() && ! $post->usesMeta()) {
                    return;
                }
                // if ($post->usesCustomTable() && $post->usesCustomMeta()) {
                //     return;
                // }

                // Unset fields from the attributes
                unset($post->attributes[$slug]);
            });

            // ray('saving', $post)->green();

        });
    }

    /**
     * Set a nested field value based on the slug with dots.
     *
     * @param  string  $slug
     * @param  mixed  $value
     * @return void
     */
    protected static function setNestedFieldValue(array &$fields, $slug, $value)
    {
        $keys = explode('.', $slug);
        $temp = &$fields;

        foreach ($keys as $key) {
            if (! isset($temp[$key])) {
                $temp[$key] = [];
            }
            $temp = &$temp[$key];
        }

        $temp = $value;
    }
}
```

## ./Traits/InputFields.php
```
<?php

namespace Aura\Base\Traits;

use Aura\Base\ConditionalLogic;
use Aura\Base\Pipeline\ApplyTabs;
use Aura\Base\Pipeline\MapFields;
use Aura\Base\Pipeline\ApplyWrappers;
use Aura\Base\Pipeline\AddIdsToFields;
use Aura\Base\Pipeline\TransformSlugs;
use Aura\Base\Pipeline\FilterEditFields;
use Aura\Base\Pipeline\FilterViewFields;
use Aura\Base\Pipeline\FilterCreateFields;
use Aura\Base\Pipeline\BuildTreeFromFields;
use Aura\Base\Pipeline\RemoveClosureAttributes;
use Aura\Base\Pipeline\RemoveValidationAttribute;
use Aura\Base\Pipeline\DoNotDeferConditionalLogic;
use Aura\Base\Pipeline\ApplyParentConditionalLogic;
use Aura\Base\Pipeline\ApplyParentDisplayAttributes;

trait InputFields
{
    use InputFieldsHelpers;
    use InputFieldsTable;
    use InputFieldsValidation;

    private $accessibleFieldKeysCache = null;

    public function createFields()
    {
        // Apply Conditional Logic of Parent Fields
        return $this->sendThroughPipeline($this->fieldsCollection(), [
            // ApplyTabs::class,
            MapFields::class,
            ApplyWrappers::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
            DoNotDeferConditionalLogic::class,
            ApplyParentDisplayAttributes::class,
            FilterCreateFields::class,
            BuildTreeFromFields::class,
        ]);
    }

    public function displayFieldValue($key, $value = null)
    {
        // return $value;

        // Check Conditional Logic if the field should be displayed
        if (! $this->shouldDisplayField($this->fieldBySlug($key))) {
            return;
        }

        $studlyKey = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));

        // If there is a get{key}Field() method, use that
        if ($value && method_exists($this, 'get'.ucfirst($studlyKey).'Field')) {
            return $this->{'get'.ucfirst($key).'Field'}($value);
        }

        // Maybe delete this one?
        if (optional($this->fieldBySlug($key))['display'] && $value) {
            return $this->fieldBySlug($key)['display']($value, $this);
        }

        // Only if uses Meta
        if (! $this->usesCustomTable() && $value === null && optional(optional($this)->meta)->$key) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), optional($this->meta)->$key, $this);
        }

        if ($this->fieldClassBySlug($key)) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), $value, $this);
        }

        return $value;
    }

    public function editFields()
    {
        // Apply Conditional Logic of Parent Fields
        return $this->sendThroughPipeline($this->fieldsCollection(), [
            // ApplyTabs::class,
            MapFields::class,
            ApplyWrappers::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
            DoNotDeferConditionalLogic::class,
            ApplyParentDisplayAttributes::class,
            FilterEditFields::class,
            RemoveClosureAttributes::class,
            BuildTreeFromFields::class,
        ]);
    }

    public function fieldBySlugWithDefaultValues($slug)
    {
        $field = $this->fieldBySlug($slug);

        if (! isset($field)) {
            return;
        }

        $fieldFields = optional($this->mappedFieldBySlug($slug))['field']->getGroupedFields();

        foreach ($fieldFields as $key => $f) {
            // if no key value pair is set, get the default value from the field
            if (! isset($field[$f['slug']]) && isset($f['default'])) {
                $field[$f['slug']] = $f['default'];
            }
        }

        return $field;
    }

    // public function getAccessibleFieldKeys()
    // {
    //     if ($this->accessibleFieldKeysCache === null) {
    //         // Apply Conditional Logic of Parent Fields
    //         $fields = $this->sendThroughPipeline($this->fieldsCollection(), [
    //             ApplyTabs::class,
    //             MapFields::class,
    //             AddIdsToFields::class,
    //             ApplyParentConditionalLogic::class,
    //             DoNotDeferConditionalLogic::class,
    //         ]);

    //         // Get all input fields
    //         $this->accessibleFieldKeysCache = $fields
    //             ->filter(function ($field) {
    //                 return $field['field']->isInputField();
    //             })
    //             ->pluck('slug')
    //             ->filter(function ($field) {
    //                 // return true;
    //                 return $this->shouldDisplayField($this->fieldBySlug($field));
    //             })
    //             ->toArray();
    //     }

    //     return $this->accessibleFieldKeysCache;
    // }

    public function fieldsForView($fields = null, $pipes = null)
    {
        if (! $fields) {
            $fields = $this->mappedFields();
        }

        if (! $pipes) {
            $pipes = [
                // ApplyTabs::class,
                MapFields::class,
                ApplyWrappers::class,
                AddIdsToFields::class,
                ApplyParentConditionalLogic::class,
                DoNotDeferConditionalLogic::class,
                ApplyParentDisplayAttributes::class,
                FilterViewFields::class,
                RemoveValidationAttribute::class,
                BuildTreeFromFields::class,
            ];
        }

        return $this->sendThroughPipeline($fields, $pipes);
    }

    public function fieldsHaveClosures($fields)
    {
        foreach ($fields as $field) {
            foreach ($field as $value) {
                if (is_array($value)) {
                    if ($this->fieldsHaveClosures([$value])) {
                        return true;
                    }
                } elseif ($value instanceof \Closure) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getFieldsBeforeTree($fields = null)
    {
        $cacheKey = get_class($this).'-getFieldsBeforeTree';

        if (! app()->bound($cacheKey)) {
            // If fields is set and is an array, create a collection
            if ($fields && is_array($fields)) {
                $fields = collect($fields);
            }

            if (! $fields) {
                $fields = $this->fieldsCollection();
            }

            $fieldsBeforeTree = $this->sendThroughPipeline($fields, [
                MapFields::class,
                AddIdsToFields::class,
                TransformSlugs::class,
                ApplyParentConditionalLogic::class,
                DoNotDeferConditionalLogic::class,
            ]);

            app()->singleton($cacheKey, function () use ($fieldsBeforeTree) {
                return $fieldsBeforeTree;
            });

        }

        return app($cacheKey);

    }

    // Used in Resource
    public function getFieldsForEdit($fields = null)
    {
        if (! $fields) {
            $fields = $this->mappedFields();
        }

        $pipes = [
            // ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            BuildTreeFromFields::class,
        ];

        return $this->sendThroughPipeline($fields, $pipes);
    }

    // Used in Resource
    public function getFieldsWithIds($fields = null)
    {
        if (! $fields) {
            $fields = $this->mappedFields();
        }

        $pipes = [
            AddIdsToFields::class,
        ];

        return $this->sendThroughPipeline($fields, $pipes);
    }

    /**
     * This code is used to render the form fields in the correct order.
     * It applies tabs to the fields, maps the fields, adds ids to the fields,
     * applies the parent conditional logic to the fields, and builds a tree from the fields.
     */
    public function getGroupedFields($fields = null, $pipes = null): array
    {
        // If fields is set and is an array, create a collection
        if ($fields && is_array($fields)) {
            $fields = collect($fields);
        }

        if (! $fields) {
            $fields = $this->fieldsCollection();
        }

        if (! $pipes) {
            $pipes = [
                // ApplyTabs::class,
                MapFields::class,
                ApplyWrappers::class,
                AddIdsToFields::class,
                ApplyParentConditionalLogic::class,
                DoNotDeferConditionalLogic::class,
                ApplyParentDisplayAttributes::class,
                FilterViewFields::class,
                BuildTreeFromFields::class,
            ];
        }

        return $this->sendThroughPipeline($fields, $pipes);
    }

    public function indexFields()
    {
        return $this->inputFields()->filter(function ($field) {
            if (optional($field)['on_index'] === false) {
                return false;
            }

            return true;
        });
    }

    /**
     * Map to Grouped Fields for the Resource Builder / Edit Resource.
     *
     * @param  array  $fields
     * @return array
     */
    public function mapToGroupedFields($fields)
    {
        $fields = collect($fields)->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });

        return $this->sendThroughPipeline($fields, [
            AddIdsToFields::class,
            BuildTreeFromFields::class,
        ]);
    }

    public function shouldDisplayField($field)
    {
        return ConditionalLogic::shouldDisplayField($this, $field, $this->getMeta());
    }

    public function taxonomyFields()
    {
        return $this->mappedFields()->filter(function ($field) {
            if (optional(optional($field)['field'])->isTaxonomyField()) {
                return true;
            }

            return false;
        });
    }

    public function viewFields()
    {
        return $this->sendThroughPipeline($this->mappedFields(), [
            // ApplyTabs::class,
            MapFields::class,
            ApplyWrappers::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
            DoNotDeferConditionalLogic::class,
            ApplyParentDisplayAttributes::class,
            FilterViewFields::class,
            BuildTreeFromFields::class,
        ]);
    }
}
```

## ./Traits/AuraModelConfig.php
```
<?php

namespace Aura\Base\Traits;

use Aura\Base\ConditionalLogic;
use Aura\Base\Models\Meta;
use Aura\Base\Resources\Team;
use Illuminate\Support\Str;

trait AuraModelConfig
{
    public array $actions = [];

    public array $bulkActions = [];

    public static $contextMenu = true;

    public static $createEnabled = true;

    public static $customTable = false;

    public static $editEnabled = true;

    public static $globalSearch = true;

    public static bool $indexViewEnabled = true;

    public array $metaFields = [];

    public static $pluralName = null;

    public static $showActionsAsButtons = false;

    public static $singularName = null;

    public static $taxonomy = false;

    public array $taxonomyFields = [];

    public static bool $usesMeta = true;

    public static $viewEnabled = true;

    public array $widgetSettings = [
        'default' => '30d',
        'options' => [
            '1d' => '1 Day',
            '7d' => '7 Days',
            '30d' => '30 Days',
            '60d' => '60 Days',
            '90d' => '90 Days',
            '180d' => '180 Days',
            '365d' => '365 Days',
            'all' => 'All',
            'ytd' => 'Year to Date',
            'qtd' => 'Quarter to Date',
            'mtd' => 'Month to Date',
            'wtd' => 'Week to Date',
            'last-year' => 'Last Year',
            'last-month' => 'Last Month',
            'last-week' => 'Last Week',
            'custom' => 'Custom',
        ],
    ];

    protected $baseFillable = [];

    protected static $dropdown = false;

    protected static ?string $group = 'Resources';

    protected static ?string $icon = null;

    protected static ?string $name = null;

    protected static array $searchable = [];

    protected static bool $showInNavigation = true;

    protected static ?string $slug = null;

    protected static ?int $sort = 100;

    protected static bool $title = false;

    protected static string $type = 'Resource';

    public function allowedToPerformActions()
    {
        return false;
    }

    public function createUrl()
    {
        return route('aura.'.$this->getSlug().'.create');
    }

    public function createView()
    {
        return 'aura::livewire.resource.create';
    }

    public function display($key)
    {
        if (array_key_exists($key, $this->fields->toArray())) {
            $value = $this->displayFieldValue($key, $this->fields[$key]);

            // if $value is an array, implode it
            if (is_array($value)) {
                $formattedValues = array_map(function ($subArray) {
                    if (is_array($subArray)) {
                        return '['.implode(', ', $subArray).']';
                    }

                    return $subArray;
                }, $value);

                return implode(', ', $formattedValues);
            }

            return $value;
        }

        if (isset($this->{$key})) {
            $value = $this->{$key};

            // if $value is an array, implode it
            if (is_array($value)) {
                return implode(', ', $value);
            }

            return $value;
        }
    }

    public function editHeaderView()
    {
        return 'aura::livewire.resource.edit-header';
    }

    public function editUrl()
    {
        if ($this->getType() && $this->id) {
            return route('aura.'.$this->getSlug().'.edit', ['id' => $this->id]);
        }
    }

    public function editView()
    {
        return 'aura::livewire.resource.edit';
    }

    public function getActions()
    {
        if (method_exists($this, 'actions')) {
            return $this->actions();
        }

        if (property_exists($this, 'actions')) {
            return $this->actions;
        }
    }

    public function getBadge() {}

    public function getBadgeColor() {}

    public function getBaseFillable()
    {
        return $this->baseFillable;
    }

    public function getBulkActions()
    {
        if (method_exists($this, 'bulkActions')) {
            return $this->bulkActions();
        }

        if (property_exists($this, 'bulkActions')) {
            return $this->bulkActions;
        }
    }

    public static function getContextMenu()
    {
        return static::$contextMenu;
    }

    public static function getDropdown()
    {
        return static::$dropdown;
    }

    public static function getFields()
    {
        return [];
    }

    public static function getGlobalSearch()
    {
        return static::$globalSearch;
    }

    public static function getGroup(): ?string
    {
        return static::$group;
    }

    public function getHeaders()
    {
        $fields = $this->indexFields();

        // Filter $fields based on Conditional Logic for roles
        $fields = $fields->filter(function ($field) {
            return ConditionalLogic::fieldIsVisibleTo($field, auth()->user());
        });

        $fields = $fields->pluck('name', 'slug')
            ->when($this->usesTitle(), function ($collection, $value) {
                return $collection->prepend('title', 'title');
            })
            ->prepend('ID', 'id');

        return $fields;
    }

    public function getIcon()
    {
        return '<svg class="w-5 h-5" viewBox="0 0 18 18" fill="none" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 9a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public function getIndexRoute()
    {
        return route('aura.'.$this->getSlug().'.index');
    }

    public function getMetaForeignKey()
    {
        return $this->meta()->getForeignKeyName();
    }

    public function getMetaTable()
    {
        return $this->meta()->getRelated()->getTable();

        //return (new Meta())->getTable();
    }

    public static function getName(): ?string
    {
        return static::$name;
    }

    public static function getPluralName(): string
    {

        return static::$pluralName ?? str(static::$type)->plural();
    }

    public static function getShowInNavigation(): bool
    {
        return static::$showInNavigation;
    }

    public static function getSlug(): string
    {
        return static::$slug ?? Str::slug(static::$name);
    }

    public static function getSort(): ?int
    {
        return static::$sort;
    }

    public static function getType(): string
    {
        return static::$type;
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function icon()
    {
        return $this->getIcon();
    }

    public function indexTableSettings()
    {
        return [];
    }

    public function indexView()
    {
        return 'aura::livewire.resource.index';
    }

    public function isAppResource()
    {
        return Str::startsWith(get_class($this), 'App');
    }

    public function isMetaField($key)
    {
        if ($key === 'id') {
            return false;
        }

        // If the model does not use meta, return false
        if (! $this->usesMeta()) {
            return false;
        }

        // If the key is in Base fillable, it is not a meta field
        if (in_array($key, $this->getBaseFillable())) {
            return false;
        }

        // If the key is in the fields, it is a meta field
        if (in_array($key, $this->inputFieldsSlugs())) {
            return true;
        }
    }

    public function isNumberField($key)
    {
        if ($this->fieldBySlug($key)['type'] == 'Aura\\Base\\Fields\\Number') {
            return true;
        }

        return false;
    }

    // Is the Field in the table?
    public function isTableField($key)
    {
        if (in_array($key, $this->getBaseFillable())) {
            return true;
        }

        return false;
    }

    public function isTaxonomy()
    {
        return static::$taxonomy;
    }

    public function isTaxonomyField($key)
    {
        // Check if the Field is a taxonomy 'type' => 'Aura\\Base\\Fields\\Tags',
        if (in_array($key, $this->inputFieldsSlugs())) {
            $field = $this->fieldBySlug($key);

            // Atm only tags, refactor later
            if (isset($field['type']) && $field['type'] == 'Aura\\Base\\Fields\\Tags') {
                return true;
            }
        }

        return false;
    }

    public function isVendorResource()
    {
        return ! $this->isAppResource();
    }

    /**
     * Get the Meta Relation
     *
     * @return mixed
     */
    public function meta()
    {
        if (! $this->usesMeta()) {
            return;
        }

        return $this->morphMany(Meta::class, 'metable');
    }

    public function navigation()
    {
        return [
            'icon' => $this->icon(),
            'resource' => get_class($this),
            'type' => $this->getType(),
            'name' => $this->pluralName(),
            'slug' => $this->getSlug(),
            'sort' => $this->getSort(),
            'group' => $this->getGroup(),
            'route' => $this->getIndexRoute(),
            'dropdown' => $this->getDropdown(),
            'showInNavigation' => $this->getShowInNavigation(),
            'badge' => $this->getBadge(),
            'badgeColor' => $this->getBadgeColor(),
        ];
    }

    public function pluralName()
    {
        return __(static::$pluralName ?? Str::plural($this->singularName()));
    }

    public function tableComponentView()
    {
        return 'aura::livewire.table';
    }

    public function rowView()
    {
        return 'aura::components.table.row';
    }

    public function saveMetaField(array $metaFields): void
    {
        $this->saveMetaFields($metaFields);
    }

    public function saveMetaFields(array $metaFields): void
    {
        $this->metaFields = array_merge($this->metaFields, $metaFields);
    }

    public function saveTaxonomyFields(array $taxonomyFields): void
    {
        $this->taxonomyFields = array_merge($this->taxonomyFields, $taxonomyFields);
    }

    public function scopeWhereInMeta($query, $field, $values)
    {
        // dd($query, $field, $values);
        if ($values instanceof \Illuminate\Support\Collection) {
            $values = $values->toArray();
        }
        if (! is_array($values)) {
            $values = [$values];
        }

        return $query->whereHas('meta', function ($query) use ($field, $values) {
            $query->where('key', $field)->whereIn('value', $values);
        });
    }

    public function scopeWhereMeta($query, ...$args)
    {
        if (count($args) === 3) {
            $key = $args[0];
            $operator = $args[1];
            $value = $args[2];

            return $query->whereHas('meta', function ($query) use ($key, $operator, $value) {
                $query->where('key', $key)->where('value', $operator, $value);
            });
        } elseif (count($args) === 2) {
            $key = $args[0];
            $value = $args[1];

            return $query->whereHas('meta', function ($query) use ($key, $value) {
                $query->where('key', $key)->where('value', $value);
            });
        } elseif (count($args) === 1 && is_array($args[0])) {
            $metaPairs = $args[0];

            return $query->where(function ($query) use ($metaPairs) {
                foreach ($metaPairs as $key => $value) {
                    $query->whereHas('meta', function ($query) use ($key, $value) {
                        $query->where('key', $key)->where('value', $value);
                    });
                }
            });
        }

        return $query;
    }

    /**
     * Scope a query to only include models where meta contains a specific value.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $key
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereMetaContains($query, $key, $value)
    {
        // ray($query, $key, $value)->red();

        return $query->whereHas('meta', function ($query) use ($key, $value) {
            $value = is_numeric($value) ? (int) $value : $value;
            $query->where('key', $key)
                ->whereRaw('JSON_CONTAINS(value, ?)', [json_encode($value)]);
        });
    }

    public function scopeWhereNotInMeta($query, $field, $values)
    {
        if ($values instanceof \Illuminate\Support\Collection) {
            $values = $values->toArray();
        }
        if (! is_array($values)) {
            $values = [$values];
        }

        return $query->whereDoesntHave('meta', function ($query) use ($field, $values) {
            $query->where('key', $field)->whereIn('value', $values);
        });
    }

    public function singularName()
    {
        return static::$singularName ?? Str::title(static::$slug);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function title()
    {
        if (optional($this)->id) {
            return __($this->getType())." (#{$this->id})";
        }
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    // public function __get($key)
    // {
    //     // // Title is a special case, for now
    //     if ($key == 'title') {
    //         return $this->getAttributeValue($key);
    //     }

    //     // Does not work atm
    //     // if ($key == 'roles') {
    //     //     return;
    //     //     return $this->getRolesField();
    //     // }

    //     $value = parent::__get($key);

    //     if ($value) {
    //         return $value;
    //     }

    //     return $this->displayFieldValue($key, $value);
    // }

    public static function usesCustomTable(): bool
    {
        return static::$customTable;
    }

    public static function usesMeta(): string
    {
        return static::$usesMeta;
    }

    public static function usesTitle(): bool
    {
        return static::$title;
    }

    public function viewHeaderView()
    {
        return 'aura::livewire.resource.view-header';
    }

    public function viewUrl()
    {
        if ($this->getType() && $this->id) {
            return route('aura.'.$this->getSlug().'.view', ['id' => $this->id]);
        }
    }

    public function viewView()
    {
        return 'aura::livewire.resource.view';
    }
}
```

## ./Traits/InputFieldsTable.php
```
<?php

namespace Aura\Base\Traits;

trait InputFieldsTable
{
    public function getColumns()
    {
        return $this->getTableHeaders()->toArray();
    }

    public function getDefaultColumns()
    {
        return $this->getTableHeaders()->map(fn () => true)->toArray();
    }

    public function getTableHeaders()
    {
        $fields = $this->indexHeaderFields()
            ->pluck('name', 'slug');

        // filter out fields that are not on the index or should not be displayed
        $fields = $fields->filter(function ($field, $slug) {
            return $this->isFieldOnIndex($slug) && $this->shouldDisplayField($this->fieldBySlug($slug));
        });

        return $fields;
    }

    public function isFieldOnIndex($slug)
    {
        return $this->mappedFieldBySlug($slug)['on_index'] ?? true;
    }
}
```

## ./Traits/InteractsWithTable.php
```
<?php

namespace Aura\Base\Traits;

trait InteractsWithTable
{
    public function defaultPerPage()
    {
        return 10;
    }

    public function defaultTableSort()
    {
        return 'id';
    }

    public function defaultTableSortDirection()
    {
        return 'desc';
    }

    public function defaultTableView()
    {
        return 'list';
    }

    public function kanbanQuery($query)
    {
        return false;
    }

    public function showTableSettings()
    {
        return true;
    }

    public function tableGridView()
    {
        return false;
    }

    public function tableKanbanView()
    {
        return false;
    }

    public function tableRowView()
    {
        return 'attachment.row';
    }

    public function tableView()
    {
        return 'aura::components.table.list-view';
    }
}
```

## ./Traits/HasActions.php
```
<?php

namespace Aura\Base\Traits;

trait HasActions
{
    /**
     * Confirm the user's action.
     *
     * @return void
     */
    public function confirmAction($id)
    {
        $this->dispatch('action-confirmed', id: $id);

    }

    public function getActionsProperty()
    {
        $actions = $this->model->getActions();

        return collect($actions)->filter(function ($item) {
            if (isset($item['conditional_logic'])) {
                return $item['conditional_logic']();
            }

            return true;
        })->all();
    }

    public function singleAction($action)
    {
        // Authorize
        if (! $this->model->allowedToPerformActions()) {
            $this->authorize('update', $this->model);
        }

        $response = $this->model->{$action}();

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            return $response; // Perform the redirect.
        }

        $this->notify(__('Successfully ran: :action', ['action' => __($action)]));
    }
}
```

## ./Mail/TeamInvitation.php
```
<?php

namespace Aura\Base\Mail;

use Aura\Base\Resources\TeamInvitation as TeamInvitationResource;
use Aura\Base\Resources\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TeamInvitation extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * The team invitation instance.
     *
     * @var \Laravel\Jetstream\TeamInvitation
     */
    public $invitation;

    /**
     * Create a new message instance.
     *
     * @param  \Laravel\Jetstream\TeamInvitation  $invitation
     * @return void
     */
    public function __construct(TeamInvitationResource $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('aura::emails.team-invitation', [
            'registerUrl' => URL::signedRoute('aura.invitation.register', [
                'team' => $this->invitation->team,
                'teamInvitation' => $this->invitation,
            ]),
            'userExists' => User::where('email', $this->invitation->email)->exists(),
            'acceptUrl' => URL::signedRoute('aura.team-invitations.accept', ['invitation' => $this->invitation]),
        ])
            ->subject(__('You have been invited to join the :team team!', ['team' => $this->invitation->team->name]));
    }
}
```

## ./Providers/AppServiceProvider.php
```
<?php

namespace Aura\Base\Providers;

use Aura\Base\Events\SaveFields;
use Aura\Base\Facades\DynamicFunctions;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Aura\Base\Listeners\SyncDatabase;
use Aura\Base\Navigation\Navigation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Navigation::add(array_filter([
            config('aura.features.create_resource') ? [
                'icon' => "<x-aura::icon icon='collection' />",
                'name' => 'Create Resource',
                'slug' => 'create_resource',
                'group' => 'settings',
                'sort' => 300,
                'onclick' => "Livewire.dispatch('openModal', { component : 'aura::create-resource' })",
                'route' => false,
                'conditional_logic' => DynamicFunctions::add(function () {
                    return auth()->user()->isSuperAdmin();
                }),
            ] : null,
            config('aura.features.settings') ? [
                'icon' => "<x-aura::icon icon='config' />",
                'name' => 'Settings',
                'slug' => 'settings',
                'group' => 'settings',
                'sort' => 300,
                'route' => 'aura.settings',
                'conditional_logic' => DynamicFunctions::add(function () {
                    return auth()->user()->isSuperAdmin();
                }),
            ] : null,
        ]));

        // Validator::extend('json', function ($attribute, $value, $parameters, $validator) {
        //     json_decode($value);
        //     dd('here');
        //     return json_last_error() === JSON_ERROR_NONE;
        // });

        // Register event and listener
        // Event::listen(SaveFields::class, SyncDatabase::class);

        $customTableMigrations = config('aura.features.custom_tables_for_resources');

        if ($customTableMigrations === 'multiple') {
            // Create New Migrations every time a new field is saved
            Event::listen(SaveFields::class, CreateDatabaseMigration::class);
        } elseif ($customTableMigrations === true || $customTableMigrations === 'single') {
            // Modify Existing Migration every time a new field is saved, syncs the database
            Event::listen(SaveFields::class, ModifyDatabaseMigration::class);
        }

    }

    /**
     * Register any application services.
     */
    public function register(): void {}
}
```

## ./Providers/AuthServiceProvider.php
```
<?php

namespace Aura\Base\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider as TwoFactorAuthenticationProviderContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::twoFactorChallengeView(function () {
            return view('aura::auth.two-factor-challenge');
        });

        Fortify::loginView(function () {
            return view('aura::auth.login');
        });

        Fortify::ignoreRoutes();

        // Password reset link in email template...
        ResetPassword::createUrlUsing(static function ($notifiable, $token) {
            return route('aura.password.reset', $token);
        });

        // Set Configuration of fortify.features to [registration, email-verification and two-factor-authentication]
        app('config')->set('fortify.features', [
            //Features::registration(),
            Features::emailVerification(),
            Features::twoFactorAuthentication([
                'confirm' => true,
                'confirmPassword' => true,
                // 'window' => 0,
            ]),
            // Features::confirmsTwoFactorAuthentication(),
        ]);

        // Set Configuration of fortify.redirects.login to /admin/dashboard
        app('config')->set('fortify.redirects.login', '/admin/dashboard');
        // app('config')->set('fortify.views', false);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TwoFactorAuthenticationProviderContract::class, function ($app) {
            return new TwoFactorAuthenticationProvider(
                $app->make(Google2FA::class),
                $app->make(Repository::class)
            );
        });
    }
}
```

## ./Providers/RouteServiceProvider.php
```
<?php

namespace Aura\Base\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/admin/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        // $this->routes(function () {
        //     Route::prefix('api')
        //         ->middleware('api')
        //         ->namespace($this->namespace)
        //         ->group(base_path('routes/api.php'));

        //     Route::middleware('web')
        //         ->namespace($this->namespace)
        //         ->group(base_path('routes/web.php'));
        // });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
```

## ./Providers/PolicyServiceProvider.php
```
<?php

namespace Aura\Base\Providers;

use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Policies\UserPolicy;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class PolicyServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Team::class => TeamPolicy::class,
        Resource::class => ResourcePolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        //$this->registerPolicies();

        // Gate::before(function ($user, $ability) {
        //     dd('before');

        //     return true;
        //     if ($user->isSuperAdmin()) {
        //         return true;
        //     }
        // });
    }

    public function register(): void {}
}
```

## ./Resources/Team.php
```
<?php

namespace Aura\Base\Resources;

use Aura\Base\Database\Factories\TeamFactory;
use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Team extends Resource
{
    use SoftDeletes;

    public array $actions = [
        'delete' => [
            'label' => 'Delete',
            'icon-view' => 'aura::components.actions.trash',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
        ],
    ];

    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'team';

    public static string $type = 'Team';

    public static bool $usesMeta = true;

    protected $fillable = [
        'name', 'user_id', 'fields',
    ];

    protected static ?string $group = 'Global';

    protected $table = 'teams';

    protected static bool $title = false;

    public function clearCachedOption($option)
    {
        $option = 'team.'.$this->id.'.'.$option;

        Cache::forget($option);
    }

    public function customPermissions()
    {
        return [
            'invite-users' => 'Invite users to team',
        ];
    }

    public function deleteOption($option)
    {
        $option = 'team.'.$this->id.'.'.$option;

        Option::whereName($option)->delete();

        Cache::forget($option);
    }

    public static function getFields()
    {
        return [

            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Team',
                'slug' => 'tab-team',
                'global' => true,
            ],
            [
                'name' => 'Team',
                'slug' => 'team-panel',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [],
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'searchable' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Description',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'on_index' => true,
                'slug' => 'description',
                'style' => [
                    'width' => '100',
                ],
            ],
            // [
            //     'type' => 'Aura\\Base\\Fields\\Tab',
            //     'name' => 'Users',
            //     'slug' => 'tab-users',
            //     'global' => true,
            //     'on_create' => false,
            // ],
            // [
            //     'name' => 'Users',
            //     'slug' => 'users',
            //     'type' => 'Aura\\Base\\Fields\\HasMany',
            //     'resource' => 'Aura\\Base\\Resources\\User',
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'on_index' => false,
            //     'on_forms' => true,
            //     'on_view' => true,
            //     'style' => [
            //         'width' => '100',
            //         'class' => '!p-4',
            //     ],
            // ],
            // [
            //     'name' => 'Invitations',
            //     'slug' => 'tab-Invitations',
            //     'type' => 'Aura\\Base\\Fields\\Tab',
            //     'global' => true,
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'on_create' => false,
            // ],
            // [
            //     'name' => 'Invitations',
            //     'slug' => 'Invitations',
            //     'type' => 'Aura\\Base\\Fields\\HasMany',
            //     'resource' => 'Aura\\Base\\Resources\\TeamInvitation',
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'on_index' => false,
            //     'on_forms' => true,
            //     'on_view' => true,
            //     'style' => [
            //         'width' => '100',
            //         'class' => '!p-4',
            //     ],
            // ],
        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /> </svg>';
    }

    public function getOption($option)
    {
        $option = 'team.'.$this->id.'.'.$option;

        // If there is a * at the end of the option name, it means that it is a wildcard
        // and we need to get all options that match the wildcard
        if (substr($option, -1) == '*') {

            $o = substr($option, 0, -1);

            // Cache
            $options = Option::where('name', 'like', $o.'%')->orderBy('id')->get();

            // Map the options, set the key to the option name (everything after last dot ".") and the value to the option value
            return $options->mapWithKeys(function ($item, $key) {
                return [str($item->name)->afterLast('.')->toString() => $item->value];
            });
        }

        $model = Option::whereName($option)->first();

        if ($model) {
            return $model->value;
        }
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function teamInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'team_id');
    }

    public function title()
    {
        return $this->name;
    }

    public function updateOption($option, $value)
    {
        $option = 'team.'.$this->id.'.'.$option;

        Option::updateOrCreate(['name' => $option], ['value' => $value]);

        Cache::forget($option);
    }

    // public function users()
    // {
    //     return $this->belongsToMany(User::class, 'post_relations', 'team_id', 'roleable_id')
    //         ->where('roleable_type', User::class);
    // }
    // public function users()
    // {
    //     return $this->hasManyThrough(Role::class, User::class);
    // }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    protected static function booted()
    {
        parent::booted();
        static::saving(function ($team) {
            // unset title attribute
            unset($team->title);
            unset($team->content);
            unset($team->type);
            unset($team->team_id);

            if (! $team->user_id && auth()->user()) {
                $team->user_id = auth()->user()->id;
            }
        });

        static::creating(function ($team) {
            // dd('creating', $team);
        });

        static::created(function ($team) {

            if ($user = auth()->user()) {
                // Change the current team id of the user
                // $user->switchTeam($team);
                $user->current_team_id = $team->id;
                $user->save();
            }

            // Create a Super Admin role for the team
            $role = Role::create([
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Super Admin has can perform everything.',
                'super_admin' => true,
                'permissions' => [],
                'team_id' => $team->id,
            ]);

            // Attach the current user to the team
            if ($user) {
                // $role->users()->sync([$user->id]);

                $team->users()->attach($user->id, ['role_id' => $role->id]);

                // $fields = $user->fields;
                // $fields['roles'] = [$role->id];
                // $user->update([
                //     'fields' => $fields->toArray(),
                // ]);

                // Clear cache of Cache('user.'.$user->id.'.teams')
                Cache::forget('user.'.$user->id.'.teams');
            }

            // Create all permissions for the team
            GenerateAllResourcePermissions::dispatch($team->id);
        });

        static::deleted(function ($team) {
            // Get all users who had the deleted team as their current team
            $users = User::where('current_team_id', $team->id)->get();

            // Loop through the users and update their current_team_id
            foreach ($users as $user) {
                $firstTeam = $user->teams()->first();
                $user->current_team_id = $firstTeam ? $firstTeam->id : null;
                $user->save();
            }

            // Delete all the team's roles
            // $team->roles()->delete();

            // Delete all the team's metas
            $team->meta()->delete();

            // Delete all the team's invitations
            $team->teamInvitations()->delete();

            // Delete all the team's options
            Option::where('name', 'like', 'team.'.$team->id.'.%')->delete();

            // Clear cache of Cache('user.'.$this->id.'.teams')
            Cache::forget('user.'.auth()->user()->id.'.teams');

            // Redirect to the dashboard
            return redirect()->route('aura.dashboard');
        });

        // static::updating(function ($team) {
        //     dd('uppdating');
        // });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return TeamFactory::new();
    }
}
```

## ./Resources/Category.php
```
<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;

class Category extends Resource
{
    public static $hierarchical = true;

    public static ?string $slug = 'category';

    public static string $type = 'Category';

    protected static ?string $group = 'Aura';

    public function getIcon()
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 7L11.8845 4.76892C11.5634 4.1268 11.4029 3.80573 11.1634 3.57116C10.9516 3.36373 10.6963 3.20597 10.4161 3.10931C10.0992 3 9.74021 3 9.02229 3H5.2C4.0799 3 3.51984 3 3.09202 3.21799C2.71569 3.40973 2.40973 3.71569 2.21799 4.09202C2 4.51984 2 5.0799 2 6.2V7M2 7H17.2C18.8802 7 19.7202 7 20.362 7.32698C20.9265 7.6146 21.3854 8.07354 21.673 8.63803C22 9.27976 22 10.1198 22 11.8V16.2C22 17.8802 22 18.7202 21.673 19.362C21.3854 19.9265 20.9265 20.3854 20.362 20.673C19.7202 21 18.8802 21 17.2 21H6.8C5.11984 21 4.27976 21 3.63803 20.673C3.07354 20.3854 2.6146 19.9265 2.32698 19.362C2 18.7202 2 17.8802 2 16.2V7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
}
```

## ./Resources/TeamInvitation.php
```
<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;

class TeamInvitation extends Resource
{
    public static $createEnabled = false;

    public static ?string $slug = 'teaminvitation';

    public static string $type = 'teaminvitation';

    protected static $dropdown = 'Users';

    protected static ?string $group = 'Aura';

    protected static bool $showInNavigation = true;

    public static function getFields()
    {
        return [
            [
                'name' => 'Email',
                'slug' => 'email',
                'type' => 'Aura\\Base\\Fields\\Email',
                'validation' => 'required',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Role',
                'slug' => 'role',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>';
    }

    public function singularName()
    {
        return 'Team Invitation';
    }

    /**
     * Get the team that the invitation belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
```

## ./Resources/Post.php
```
<?php

namespace Aura\Base\Resources;

use Aura\Base\Database\Factories\PostFactory;
use Aura\Base\Resource;
use Aura\Base\Widgets\AvgPostsNumber;
use Aura\Base\Widgets\PostChart;
use Aura\Base\Widgets\SumPostsNumber;
use Aura\Base\Widgets\TotalPosts;
use Aura\Export\Traits\Exportable;
use Aura\Flows\Resources\Flow;

class Post extends Resource
{
    use Exportable;

    public array $actions = [
        'delete' => [
            'label' => 'Delete',
            'icon-view' => 'aura::components.actions.trash',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
            'confirm' => true,
            'confirm-title' => 'Delete Post?',
            'confirm-content' => 'Are you sure you want to delete this post?',
            'confirm-button' => 'Delete',
            'confirm-button-class' => 'ml-3 bg-red-600 hover:bg-red-700',
        ],
        'testAction' => [
            'label' => 'Test Action',
            'class' => 'hover:text-primary-700 text-primary-500 font-bold',
            'confirm' => true,
            'confirm-title' => 'Test Action Post?',
            'confirm-content' => 'Are you sure you want to test Action?',
            'confirm-button' => 'Yup',
        ],
    ];

    public array $bulkActions = [
        'deleteSelected' => 'Delete',
        'multipleExportSelected' => [
            'label' => 'Export',
            'modal' => 'export::export-selected-modal',
        ],
    ];

    public static $fields = [];

    public static ?string $slug = 'post';

    public static ?int $sort = 50;

    public static string $type = 'Post';

    public array $widgetSettings = [
        'default' => '30d',
        'options' => [
            '1d' => '1 Day',
            '7d' => '7 Days',
            '30d' => '30 Days',
            '60d' => '60 Days',
            '90d' => '90 Days',
            '180d' => '180 Days',
            '365d' => '365 Days',
            'all' => 'All',
            'ytd' => 'Year to Date',
            'qtd' => 'Quarter to Date',
            'mtd' => 'Month to Date',
            'wtd' => 'Week to Date',
            'last-year' => 'Last Year',
            'last-month' => 'Last Month',
            'last-week' => 'Last Week',
            'custom' => 'Custom',
        ],
    ];

    protected static ?string $group = 'Aura';

    protected $hidden = ['password'];

    protected static array $searchable = [
        'title',
        'content',
    ];

    public function callFlow($flowId)
    {
        $flow = Flow::find($flowId);
        $operation = $flow->operation;

        // Create a Flow Log
        $flowLog = $flow->logs()->create([
            'post_id' => $this->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Run the Operation
        $operation->run($this, $flowLog->id);
    }

    public function delete()
    {
        // parent delete
        parent::delete();

        // redirect to index page
        return redirect()->route('aura.'.$this->getSlug().'.index');
    }

    public function deleteSelected()
    {
        parent::delete();
    }

    public function getBulkActions()
    {
        // get all flows with type "manual"

        // $flows = Flow::where('trigger', 'manual')
        //     ->where('options->resource', $this->getType())
        //     ->get();

        // foreach ($flows as $flow) {
        //     $this->bulkActions['callFlow.'.$flow->id] = $flow->name;
        // }

        return $this->bulkActions;
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'global' => true,
                'conditional_logic' => [
                ],
                'slug' => 'tab1',
            ],
            [
                'name' => 'Panel',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [
                ],
                'slug' => 'panel1',
                'style' => [
                    'width' => '70',
                ],
            ],
            [
                'name' => 'ID',
                'slug' => 'id',
                'type' => 'Aura\\Base\\Fields\\ID',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => false,
                'on_view' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Text',
                'slug' => 'text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Slug for Test',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'validation' => 'required|alpha_dash',
                'conditional_logic' => [
                ],
                'slug' => 'slug',
                'based_on' => 'title',
            ],
            [
                'name' => 'Bild',
                'type' => 'Aura\\Base\\Fields\\Image',
                'max' => 1,
                'upload' => true,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'image',
            ],
            [
                'name' => 'Password for Test',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => 'nullable|min:8',
                'conditional_logic' => [
                ],
                'slug' => 'password',
                'hydrate' => function ($set, $model, $state, $get) {},
                'on_index' => false,
                'on_forms' => true,
                'on_view' => false,
            ],
            [
                'name' => 'Number',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'number',
                'on_view' => true,
                'on_forms' => true,
                'on_index' => true,
            ],
            [
                'name' => 'Date',
                'type' => 'Aura\\Base\\Fields\\Date',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'date',
                'format' => 'y-m-d',
            ],
            [
                'name' => 'Description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'description',
                'style' => [
                    'width' => '100',
                ],
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            //  [
            //      'name' => 'Color',
            //      'type' => 'Aura\\Base\\Fields\\Color',
            //      'validation' => '',
            //      'conditional_logic' => [
            //      ],
            //      'slug' => 'color',
            //      'on_index' => true,
            //      'on_forms' => true,
            //      'on_view' => true,
            //      'format' => 'hex',
            //  ],
            [
                'name' => 'Sidebar',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [
                ],
                'slug' => 'sidebar',
                'style' => [
                    'width' => '30',
                ],
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Categories',
                'slug' => 'categories',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Category',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            //  [
            //      'name' => 'Team',
            //      'slug' => 'team_id',
            //      'type' => 'Aura\\Base\\Fields\\BelongsTo',
            //      'resource' => 'Aura\\Base\\Resources\\Team',
            //      'validation' => '',
            //      'conditional_logic' => [
            //          [
            //              'field' => 'role',
            //              'operator' => '==',
            //              'value' => 'super_admin',
            //          ],
            //      ],
            //      'wrapper' => '',
            //      'on_index' => true,
            //      'on_forms' => true,
            //      'on_view' => true,
            //  ],
            [
                'name' => 'User',
                'slug' => 'user_id',
                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                'resource' => 'Aura\\Base\\Resources\\User',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Attachments',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'global' => true,
                'conditional_logic' => [
                ],
                'slug' => 'tab2',
            ],
            [
                'name' => 'Attachments',
                'slug' => 'attachments',
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => 'Aura\\Base\\Resources\\Attachment',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            // [
            //     'name' => 'Created at',
            //     'slug' => 'created_at',
            //     'type' => 'Aura\\Base\\Fields\\Date',
            //     'validation' => '',
            //     'enable_time' => true,
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            // ],
            // [
            //     'name' => 'Updated at',
            //     'slug' => 'updated_at',
            //     'type' => 'Aura\\Base\\Fields\\Date',
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'enable_time' => true,
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            // ],
        ];
    }

    public function getIcon()
    {
        return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>';
    }

    public static function getWidgets(): array
    {
        return [
            [
                'name' => 'Total Posts Created',
                'slug' => 'total_posts_created',
                'type' => 'Aura\\Base\\Widgets\\ValueWidget',
                'method' => 'count',
                'cache' => 300,
                'style' => [
                    'width' => '33.33',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Average Number',
                'slug' => 'average_number',
                'type' => 'Aura\\Base\\Widgets\\ValueWidget',
                'method' => 'avg',
                'column' => 'number',
                'cache' => 300,
                'style' => [
                    'width' => '33.33',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Sum Number',
                'slug' => 'sum_number',
                'type' => 'Aura\\Base\\Widgets\\ValueWidget',
                'method' => 'sum',
                'column' => 'number',
                'goal' => 2000,
                'dailygoal' => false,
                'cache' => 300,
                'style' => [
                    'width' => '33.33',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Sparkline Bar Chart',
                'slug' => 'sparkline_bar_chart',
                'type' => 'Aura\\Base\\Widgets\\SparklineBar',
                'cache' => 300,
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Sparkline Area',
                'slug' => 'sparkline_area',
                'type' => 'Aura\\Base\\Widgets\\SparklineArea',
                'cache' => 300,
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Donut Chart',
                'slug' => 'donut',
                'type' => 'Aura\\Base\\Widgets\\Donut',
                'cache' => 300,
                // 'values' => function () {
                //     return [
                //         'value1' => 10,
                //         'value2' => 20,
                //         'value3' => 30,
                //     ];
                // },
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Pie Chart',
                'slug' => 'pie',
                'type' => 'Aura\\Base\\Widgets\\Pie',
                'cache' => 300,
                'column' => 'number',
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Bar Chart',
                'slug' => 'bar',
                'type' => 'Aura\\Base\\Widgets\\Bar',
                'cache' => 300,
                'column' => 'number',
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],
        ];
    }

    public function indexTableSettings()
    {
        return [
            //     'default_view' => 'grid',
            // 'views' => [
            //         'grid' => 'custom.table.grid',
            //     ]
        ];
    }

    public function testAction() {}

    // public static function getWidgets(): array
    // {
    //     return [
    //         // new TotalPosts(['width' => 'w-full md:w-1/3']),
    //         // new SumPostsNumber(['width' => 'w-full md:w-1/3']),
    //         // new AvgPostsNumber(['width' => 'w-full md:w-1/3']),
    //         new PostChart(['width' => 'w-full md:w-1/3']),
    //     ];
    // }

    public function title()
    {
        return optional($this)->title." (Post #{$this->id})";
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PostFactory::new();
    }
}
```

## ./Resources/Tag.php
```
<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Resource
{
    public static $hierarchical = false;

    public static ?string $slug = 'tag';

    public static string $type = 'Tag';

    protected static ?string $group = 'Aura';

    public function component()
    {
        return 'fields.tags';
    }

    public static function getFields()
    {
        return [
            'name' => [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'title',
            ],

            'description' => [
                'label' => 'Text',
                'name' => 'Beschreibung',
                'type' => 'Aura\\Base\\Fields\\Text',
                'conditional_logic' => [],
                'slug' => 'description',
            ],
            'slug' => [
                'name' => 'Slug',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'slug',
            ],
            'count' => [
                'name' => 'Count',
                'type' => 'Aura\\Base\\Fields\\Number',
                'conditional_logic' => [],
                'slug' => 'count',
                'on_forms' => false,
            ],

        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /> <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /> </svg>';
    }

    public function title()
    {
        return $this->title;
    }

    /**
     * Get all of the posts that are assigned this tag.
     */
    // public function posts(): MorphToMany
    // {
    //     return $this->morphedByMany(Post::class, 'taggable');
    // }
}
```

## ./Resources/Option.php
```
<?php

namespace Aura\Base\Resources;

use Aura\Base\Models\Post;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;

class Option extends Resource
{
    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'option';

    public static string $type = 'Option';

    protected $casts = [
        'value' => 'array',
    ];

    protected $fillable = ['name', 'value', 'team_id'];

    protected static ?string $group = 'Aura';

    protected $table = 'options';

    public static function byName($name)
    {
        return static::where('name', $name)->first();
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => 'required',
                'on_index' => false,
                'slug' => 'value',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /> </svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new TeamScope);

        static::saving(function ($option) {

            if (config('aura.teams') && ! isset($option->team_id) && auth()->user()) {
                $option->team_id = auth()->user()->current_team_id;
            }

            // unset post attributes
            unset($option->title);
            unset($option->content);
            unset($option->user_id);
            unset($option->type);
        });
    }
}
```

## ./Resources/User.php
```
<?php

namespace Aura\Base\Resources;

use Aura\Base\Database\Factories\UserFactory;
use Aura\Base\Resource;
use Aura\Base\Traits\ProfileFields;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Resource implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasApiTokens;
    use HasFactory;
    use Impersonate;
    use MustVerifyEmail;
    use Notifiable;
    use ProfileFields;
    use TwoFactorAuthenticatable;

    public static $customTable = true;

    public static ?string $slug = 'user';

    public static ?int $sort = 1;

    public static string $type = 'User';

    public static bool $usesMeta = true;

    protected $appends = ['fields'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // 'password' => 'hashed',
    ];

    protected static $dropdown = 'Users';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'password', 'fields', 'current_team_id', 'email_verified_at', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'remember_token',
    ];

    protected static ?string $group = 'Admin';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected static array $searchable = ['name', 'email'];

    protected $table = 'users';

    /**
     * Determine if the user belongs to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function belongsToTeam($team)
    {
        if (is_null($team)) {
            return false;
        }

        return $this->teams->contains(function ($t) use ($team) {
            return $t->id === $team->id;
        });
    }

    public function cachedRoles(): mixed
    {
        // ray('roles', $this->roles, DB::table('user_meta')->get(), DB::table('post_relations')->get())->blue();

        return $this->roles;

        return Cache::remember($this->getCacheKeyForRoles(), now()->addMinutes(60), function () {
            return $this->roles;
        });
    }

    public function canBeImpersonated()
    {
        return ! $this->resource->isSuperAdmin();
    }

    public function canImpersonate()
    {
        return $this->resource->isSuperAdmin();
    }

    public function clearCachedOption($option)
    {
        $option = 'user.'.$this->id.'.'.$option;

        Cache::forget($option);
    }

    // Reset to default create Method from Laravel
    public static function create($fields)
    {
        $model = new static;

        return tap($model->newModelInstance($fields), function ($instance) {
            $instance->save();
        });
    }

    /**
     * Get the current team of the user's context.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentTeam()
    {
        if (! config('aura.teams')) {
            return;
        }

        if (is_null($this->current_team_id) && $this->id) {
            $this->switchTeam($this->personalTeam());
        }

        return $this->belongsTo(config('aura.resources.team'), 'current_team_id');
    }

    public function deleteOption($option)
    {
        $option = 'user.'.$this->id.'.'.$option;

        Option::whereName($option)->delete();

        Cache::forget($option);

    }

    public function getAvatarUrlAttribute()
    {
        return 'https://ui-avatars.com/api/?name='.$this->getInitials().'';

        // Does not work atm
        if (! $this->fields['avatar']) {
            return 'https://ui-avatars.com/api/?name='.$this->getInitials().'';
        }

        // json decode the meta value
        $meta = is_string($this->fields['avatar']) ? json_decode($this->fields['avatar']) : $this->fields['avatar'];

        // get the attachment from the meta
        $attachments = Attachment::find($meta);

        // dd(count($attachments));

        if ($attachments && count($attachments) > 0) {
            $attachment = $attachments->first();

            return $attachment->path('thumbnail');
        }

        return 'https://ui-avatars.com/api/?name='.$this->getInitials().'';
    }

    // public function getEmailField($value)
    // {
    //     return "<a class='font-bold text-primary-500' href='mailto:".$value."'>".$value.'</a>';
    // }

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Details',
                'slug' => 'tab-user',
                'global' => true,
            ],
            [
                'name' => 'Personal Infos',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => 'required',
                'slug' => 'user-details',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Avatar',
                'type' => 'Aura\\Base\\Fields\\Image',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'avatar',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'searchable' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Email',
                'type' => 'Aura\\Base\\Fields\\Email',
                'validation' => 'required|email',
                'on_index' => true,
                'searchable' => true,
                'slug' => 'email',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Current Team',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'on_index' => false,
                'searchable' => false,
                'slug' => 'current_team_id',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Roles',
                'slug' => 'roles',
                'resource' => 'Aura\\Base\\Resources\\Role',
                'type' => 'Aura\\Base\\Fields\\Roles',
                'multiple' => true,
                'polymorphic_relation' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
            ],
            [
                'name' => 'Password',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => ['nullable', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
                'conditional_logic' => [],
                'slug' => 'password',
                'on_forms' => true,
                'on_edit' => true,
                'on_create' => true,
                'on_index' => false,
                'on_view' => false,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Teams',
                'slug' => 'tab-Teams',
                'global' => true,
                'conditional_logic' => function ($model, $post) {
                    return config('aura.teams');
                },
            ],
            [
                'name' => 'Teams',
                'slug' => 'teams',
                'type' => 'Aura\\Base\\Fields\\BelongsToMany',
                'resource' => 'Aura\\Base\\Resources\\Team',
                'validation' => '',
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,

                'conditional_logic' => function ($model, $post) {
                    return config('aura.teams');
                },
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => '2FA',
                'label' => 'Tab',
                'slug' => '2fa-tab',
                'global' => true,
                'on_view' => false,
            ],
            [
                'name' => '2FA',
                'type' => 'Aura\\Base\\Fields\\LivewireComponent',
                'component' => 'aura::user-two-factor-authentication-form',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => '2fa',
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>';
    }

    public function getInitials()
    {
        $name = $this->name;
        $words = explode(' ', $name);
        $initials = '';

        foreach ($words as $word) {
            if (strlen($initials) < 2) {
                $initials .= strtoupper(substr($word, 0, 1));
            } else {
                break;
            }
        }

        return $initials;
    }

    public function getMorphClass(): string
    {
        return "Aura\Base\Resources\User";
    }

    public function getOption($option)
    {
        $option = 'user.'.$this->id.'.'.$option;

        // If there is a * at the end of the option name, it means that it is a wildcard
        // and we need to get all options that match the wildcard
        if (substr($option, -1) == '*') {
            $o = substr($option, 0, -1);

            // Cache
            $options = Cache::remember($option, now()->addHour(), function () use ($o) {
                return Option::where('name', 'like', $o.'%')->get();
            });

            // Map the options, set the key to the option name (everything after last dot ".") and the value to the option value
            return $options->mapWithKeys(function ($item, $key) {
                return [str($item->name)->afterLast('.')->toString() => $item->value];
            });
        }

        // Cache
        $model = Cache::remember($option, now()->addHour(), function () use ($option) {
            return Option::whereName($option)->first();
        });

        if ($model) {
            return $model->value;
        }
    }

    public function getOptionBookmarks()
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.bookmarks', now()->addHour(), function () {
            return Option::whereName('user.'.$this->id.'.bookmarks')->first();
        });

        if ($option) {
            return $option->value;
        }

        return [];
    }

    public function getOptionColumns($slug)
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.columns.'.$slug, now()->addHour(), function () use ($slug) {
            return Option::whereName('user.'.$this->id.'.columns.'.$slug)->first();
        });

        if ($option) {
            return $option->value;
        }

        return [];
    }

    public function getOptionSidebar()
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.sidebar', now()->addHour(), function () {
            return Option::whereName('user.'.$this->id.'.sidebar')->first();
        });

        if ($option) {
            return $option->value;
        }

        return [];
    }

    public function getOptionSidebarToggled()
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.sidebarToggled', now()->addHour(), function () {
            return Option::whereName('user.'.$this->id.'.sidebarToggled')->first();
        });

        if ($option) {
            return $option->value;
        }

        return true;
    }

    // public function getRolesField()
    // {
    //     return $this->roles->pluck('id')->toArray();
    // }

    public function getSearchableFields()
    {
        // get input fields and remove the ones that are not searchable
        $fields = $this->inputFields()->filter(function ($field) {
            // if $field is array or undefined, then we don't want to use it
            if (! is_array($field) || ! isset($field['searchable'])) {
                return false;
            }

            return $field['searchable'];
        });

        return $fields;
    }

    public function getTeams()
    {
        if (! config('aura.teams')) {
            return;
        }

        // Return cached teams with meta
        return Cache::remember('user.'.$this->id.'.teams', now()->addHour(), function () {
            return $this->teams()->with('meta')->get();
        });
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function hasAnyRole(array $roles): bool
    {
        $cachedRoles = $this->cachedRoles()->pluck('slug');

        // ray($cachedRoles, $roles)->red();

        if (! $cachedRoles) {
            return false;
        }

        foreach ($cachedRoles as $role) {
            if (in_array($role, $roles)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission($permission)
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $role) {
            if ($role->super_admin) {
                return true;
            }

            $permissions = $role->fields['permissions'];

            if (empty($permissions)) {
                continue;
            }

            foreach ($permissions as $p => $value) {
                if ($p == $permission && $value == true) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasPermissionTo($ability, $post): bool
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $role) {

            if ($role->super_admin) {
                return true;
            }

            $permissions = $role->fields['permissions'];

            if (empty($permissions)) {
                continue;
            }

            // Temporary Fix. To Do: It should be an array
            if (is_string($permissions)) {
                $permissions = json_decode($permissions, true);
            }

            foreach ($permissions as $permission => $value) {
                if ($permission == $ability.'-'.$post::$slug && $value == true) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasRole(string $role): bool
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $r) {
            if ($r->slug == $role) {
                return true;
            }
        }

        return false;
    }

    public function indexQuery($query)
    {
        if (config('aura.teams')) {
            return $query->whereHas('roles', function ($query) {
                $query->where('roles.team_id', auth()->user()->current_team_id);
            });
        }

        return $query->whereHas('roles');
    }

    /**
     * Determine if the given team is the current team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function isCurrentTeam($team)
    {
        return $team->id === $this->currentTeam->id;
    }

    /**
     * Returns true if the user has at least one role that is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $role) {
            if ($role->super_admin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user owns the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function ownsTeam($team)
    {
        if (is_null($team)) {
            return false;
        }

        return $this->id == $team->{$this->getForeignKey()};
    }

    public function resource()
    {
        // Return \Aura\Base\Resources\User for this user
        if (config('aura.resources.user')) {
            return $this->hasOne(config('aura.resources.user'), 'id', 'id');
        } else {
            return $this->hasOne(\Aura\Base\Resources\User::class, 'id', 'id');
        }

        // Cache the resource so we don't have to query the database every time
        return Cache::remember('user.resource.'.$this->id, now()->addHour(), function () {
            return \Aura\Base\Resources\User::find($this->id);
        });
    }

    /**
     * Get the roles for the user.
     */
    public function roles(): BelongsToMany
    {
        if (config('aura.teams')) {
            return $this->belongsToMany(Role::class, 'user_role')
                ->withPivot('team_id')
                ->withTimestamps();
        }

        return $this->belongsToMany(Role::class, 'user_role')
            //->using(TeamUser::class)
            //->withPivot('team_id')
            ->withTimestamps();
    }

    // public function setRolesField($value)
    // {
    //     // Save the roles
    //     if (config('aura.teams')) {
    //         $this->roles()->syncWithPivotValues($value, ['key' => 'roles', 'team_id' => $this->current_team_id]);
    //     } else {
    //         $this->roles()->syncWithPivotValues($value, ['key' => 'roles']);
    //     }

    //     // Unset the roles field
    //     unset($this->attributes['fields']['roles']);

    //     // Clear Cache 'user.' . $this->id . '.roles'
    //     Cache::forget('user.'.$this->id.'.roles');

    //     return $this;
    // }

    /**
     * Switch the user's context to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function switchTeam($team)
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $this->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        $this->setRelation('currentTeam', $team);

        return true;
    }

    /**
     * Get all of the teams the user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'user_role')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function title()
    {
        return $this->name;
    }

    public function updateOption($option, $value)
    {
        $option = 'user.'.$this->id.'.'.$option;

        if (config('aura.teams')) {
            Option::updateOrCreate([
                'name' => $option,
                'team_id' => $this->current_team_id,
            ], ['value' => $value]);
        } else {
            Option::updateOrCreate([
                'name' => $option,
            ], ['value' => $value]);
        }

        // Clear the cache
        Cache::forget($option);
    }

    public function widgets()
    {
        return collect($this->getWidgets())->map(function ($item) {
            return $item;
        });
    }

    protected function getCacheKeyForRoles(): string
    {
        return $this->current_team_id.'.user.'.$this->id.'.roles';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
```

## ./Resources/Role.php
```
<?php

namespace Aura\Base\Resources;

use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Resource
{
    public array $actions = [
        'createMissingPermissions' => [
            'label' => 'Create Missing Permissions',
            'description' => 'Create missing permissions if you have added new resources.',
            'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 8L15 8M15 8C15 9.65686 16.3431 11 18 11C19.6569 11 21 9.65685 21 8C21 6.34315 19.6569 5 18 5C16.3431 5 15 6.34315 15 8ZM9 16L21 16M9 16C9 17.6569 7.65685 19 6 19C4.34315 19 3 17.6569 3 16C3 14.3431 4.34315 13 6 13C7.65685 13 9 14.3431 9 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ],
        'delete' => [
            'label' => 'Delete',
            'icon' => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
        ],
    ];

    public array $bulkActions = [
        'deleteSelected' => 'Delete',
    ];

    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'role';

    public static ?int $sort = 2;

    public static string $type = 'Role';

    public static bool $usesMeta = false;

    protected $casts = [
        'permissions' => 'array',
        'super_admin' => 'boolean',
    ];

    protected static $dropdown = 'Users';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'super_admin',
        'permissions',
        'team_id',
    ];

    protected static ?string $group = 'Aura';

    protected $table = 'roles';

    protected $with = [];

    public function createMissingPermissions()
    {
        GenerateAllResourcePermissions::dispatch();
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'searchable' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Slug',
                'slug' => 'slug',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'based_on' => 'name',
                'custom' => false,
                'disabled' => true,
                'validation' => 'required',
                'on_index' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'type' => 'Aura\Base\Fields\Boolean',
                'instructions' => 'Super Admins have access to all permission and can manage other users.',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'live' => true,
                'default' => false,
            ],
            [
                'name' => 'Permissions',
                'on_index' => false,
                'type' => 'Aura\\Base\\Fields\\Permissions',
                'validation' => '',
                'conditional_logic' => function ($model, $form) {

                    if (optional(optional($form)['fields'])['super_admin']) {
                        return false;
                    }

                    return true;
                },
                'slug' => 'permissions',
                'resource' => 'Aura\\Base\\Resources\\Permission',
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }

    /**
     * Get the Meta Relation
     *
     * @return mixed
     */
    public function meta()
    {
        return $this->hasMany(Meta::class, 'post_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'user_role')
            ->withPivot('user_id')
            ->withTimestamps();
    }

    public function title()
    {
        if (isset($this->title)) {
            return $this->title." (#{$this->id})";
        } elseif (isset($this->name)) {
            return $this->name." (#{$this->id})";
        } else {
            return "Role (#{$this->id})";
        }
    }

    public function users(): BelongsToMany
    {
        if (config('aura.teams')) {
            return $this->belongsToMany(User::class, 'user_role')
                ->withPivot('team_id')
                ->withTimestamps();
        }

        return $this->belongsToMany(User::class, 'user_role')
            ->withTimestamps();
    }
}
```

## ./Resources/Attachment.php
```
<?php

namespace Aura\Base\Resources;

use Aura\Base\Jobs\GenerateImageThumbnail;
use Aura\Base\Resource;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Attachment extends Resource
{
    use DispatchesJobs;

    public array $actions = [
        'deleteAttachment' => [
            'label' => 'Delete',
            'icon-view' => 'aura::components.actions.trash',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
            'confirm' => true,
            'confirm-title' => 'Delete Post?',
            'confirm-content' => 'Are you sure you want to delete this post?',
            'confirm-button' => 'Delete',
            'confirm-button-class' => 'ml-3 bg-red-600 hover:bg-red-700',
        ],
    ];

    public array $bulkActions = [
        // 'deleteSelected' => 'Delete',
        // 'deleteSelected' => [
        //     'label' => 'Delete',
        //     'method' => 'collection',
        // ],
    ];

    public static $contextMenu = false;

    public static ?string $name = 'Media';

    public static ?string $slug = 'attachment';

    public static ?int $sort = 2;

    public static string $type = 'Attachment';

    protected static ?string $group = 'Aura';

    public function defaultPerPage()
    {
        return 25;
    }

    public function defaultTableView()
    {
        return 'grid';
    }

    public function deleteAttachment()
    {
        parent::delete();

        return redirect()->route('aura.attachment.index');
    }

    public function deleteSelected($ids)
    {
        self::whereIn('id', $ids)->delete();

    }

    public function filePath($size = null)
    {
        // Base storage directory
        $basePath = storage_path('app/public');

        if ($size) {
            $relativePath = Str::after($this->url, 'media/');

            return $basePath.'/'.$size.'/'.$relativePath;
        }

        return $basePath.'/'.$this->url;
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Attachment',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel1',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Preview',
                'type' => 'Aura\\Base\\Fields\\Embed',
                'validation' => '',
                'on_index' => false,
                'slug' => 'embed',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Details',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel2',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'searchable' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Url',
                'type' => 'Aura\\Base\\Fields\\ViewValue',
                'searchable' => true,
                'validation' => 'required',
                'on_index' => false,
                'slug' => 'url',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'validation' => '',
                'slug' => 'tags',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Thumbnail',
                'type' => 'Aura\\Base\\Fields\\ViewValue',
                'validation' => '',
                'on_index' => false,
                'slug' => 'thumbnail_url',
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'name' => 'Mime Type',
                'type' => 'Aura\\Base\\Fields\\ViewValue',
                'validation' => 'required',
                'searchable' => true,
                'on_index' => true,
                'slug' => 'mime_type',
                'style' => [
                    'width' => '33',
                ],
                'display_view' => 'aura::attachment.mime_type',
            ],
            [
                'name' => 'Size',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'size',
                'style' => [
                    'width' => '33',
                ],
                'display_view' => 'aura::attachment.size',
            ],
            // [
            //     'name' => 'Created at',
            //     'slug' => 'created_at',
            //     'type' => 'Aura\\Base\\Fields\\Date',
            //     'validation' => '',
            //     'enable_time' => true,
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            //     'style' => [
            //         'width' => '50',
            //     ],
            // ],
            // [
            //     'name' => 'Updated at',
            //     'slug' => 'updated_at',
            //     'type' => 'Aura\\Base\\Fields\\Date',
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'enable_time' => true,
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            //     'style' => [
            //         'width' => '50',
            //     ],
            // ],
        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /> </svg>';
    }

    public function getReadableFilesizeAttribute()
    {
        $bytes = $this->size;

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        if ($i <= 1) {
            // For B and KB, don't round decimals
            return (int)$bytes . ' ' . $units[$i];
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getReadableMimeTypeAttribute()
    {
        $mimeTypeToReadable = [
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'application/pdf' => 'PDF',
            'application/docx' => 'DOCX',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PPTX',
            'application/vnd.ms-excel' => 'XLS',
            'application/vnd.ms-powerpoint' => 'PPT',
            'application/vnd.ms-word' => 'DOC',
            'video/quicktime' => 'MOV',
            'video/mp4' => 'MP4',
            'video/x-msvideo' => 'AVI',
            'video/x-ms-wmv' => 'WMV',
            'audio/mpeg' => 'MP3',
            'audio/mp3' => 'MP3',
            'audio/x-mpeg' => 'MP3',
            'audio/x-mp3' => 'MP3',
            'audio/mpeg3' => 'MP3',
            'audio/x-mpeg3' => 'MP3',
            'audio/mpg' => 'MP3',
            'audio/x-mpg' => 'MP3',
            'audio/x-mpegaudio' => 'MP3',
        ];

        return $mimeTypeToReadable[$this->mime_type] ?? $this->mime_type;
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public static function import($url, $folder = 'attachments')
    {
        // Download the image
        $imageContent = file_get_contents($url);

        // Generate a unique file name
        $fileName = uniqid().'.jpg';

        // Save the image to the desired storage
        $storagePath = "{$folder}/{$fileName}";
        Storage::disk('public')->put($storagePath, $imageContent);

        // Get the image size and mime type
        $imageSize = Storage::disk('public')->size($storagePath);
        $imageMimeType = Storage::disk('public')->mimeType($storagePath);

        // Create a new Attachment instance
        $attachment = self::create([
            'url' => $storagePath,
            'name' => $fileName,
            'title' => $fileName,
            'size' => $imageSize,
            'mime_type' => $imageMimeType,
        ]);

        return $attachment;
    }

    public function isImage()
    {
        return Str::startsWith($this->mime_type, 'image/');
    }

    public function path($size = null)
    {
        if ($size) {
            $url = Str::after($this->url, 'media/');

            $assetPath = 'storage/'.$size.'/'.$url;

            if (file_exists(public_path($assetPath))) {
                return asset($assetPath);
            }
        }

        return asset('storage/'.$this->url);
    }

    public function tableComponentView()
    {
        return 'aura::attachment.table';
    }

    public function tableGridView()
    {
        return 'aura::attachment.grid';
    }

    public function tableView()
    {
        return 'aura::attachment.list';
    }

    public function tableRowView()
    {
        return 'aura::attachment.row';
    }

    /**
     * Get the thumbnail URL for the attachment
     *
     * @param string|null $size The size identifier (xs, sm, md, lg)
     * @return string The URL to the thumbnail
     */
    public function thumbnail(?string $size = 'sm'): string
    {
        if (!$this->isImage()) {
            return $this->path(); // Return original path for non-images
        }

        // Get configured dimensions from config
        $configuredDimensions = config('aura.media.dimensions', []);

        // Find the requested size in the config
        $dimension = collect($configuredDimensions)
            ->firstWhere('name', $size);

        if (!$dimension) {
            // Fallback to original if size not found
            return $this->path();
        }

        // Generate the image URL using the named route
        return route('aura.image', [
            'path' => $this->url,
            'width' => $dimension['width'],
            'height' => $dimension['height'] ?? null,
        ]);
    }

    public function thumbnail_path()
    {
        return asset('storage/'.$this->thumbnail_url);
    }

    protected static function booted()
    {
        parent::booted();

        // when model saved and status is "eingang" and doctor_id is set, change status to "erstellt"
        // static::creating(function ($model) {
        //     if (! $model->team_id) {
        //         $model->team_id = 1;
        //     }
        // });

        static::saved(function (Attachment $attachment) {
            // Check if the attachment has a file

            // Dispatch the GenerateThumbnail job
            if ($attachment->isImage()) {
                GenerateImageThumbnail::dispatch($attachment);
            }
        });
    }
}
```

## ./Resources/Permission.php
```
<?php

namespace Aura\Base\Resources;

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;

class Permission extends Resource
{
    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'permission';

    public static bool $usesMeta = false;

    protected static $dropdown = 'Users';

    protected static ?string $group = 'Aura';

    protected static ?int $sort = 3;

    protected $table = 'permissions';

    protected static bool $title = false;

    protected static string $type = 'Permission';

    public static function getFields()
    {
        return [

            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Slug',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'slug',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Group',
                'slug' => 'group',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => [],
                'options' => [
                    'Invoice' => 'Invoice',
                    'Permission' => 'Permission',
                    'Post' => 'Post',
                    'Project' => 'Project',
                    'Role' => 'Role',
                    'User' => 'User',
                ],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }

    public static function getGroupOptions()
    {
        return collect(Aura::getResources())->mapWithKeys(fn ($item) => [$item => $item])->toArray();
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 13.5V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 9.75V10.5" /></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }
}
```

## ./HookManager.php
```
<?php

namespace Aura\Base;

class HookManager
{
    protected $hooks = [];

    public function addHook($hook, $callback)
    {
        $this->hooks[$hook][] = $callback;
    }

    public function applyHooks($hook, $data)
    {
        if (isset($this->hooks[$hook])) {
            foreach ($this->hooks[$hook] as $callback) {
                $data = call_user_func($callback, $data);
            }
        }

        return $data;
    }
}
```

## ./Navigation/Navigation.php
```
<?php

// app/Navigation/Navigation.php

namespace Aura\Base\Navigation;

class Navigation
{
    public static function add(array $items, ?callable $authCallback = null): void
    {
        if ($authCallback && ! $authCallback()) {
            // dd($authCallback());
            return;
        }

        app('hook_manager')->addHook('navigation', function ($navigation) use ($items) {
            foreach ($items as $item) {
                $navigation->push($item);
            }

            return $navigation;
        });
    }

    public static function clear(): void
    {
        app('hook_manager')->addHook('navigation', function ($navigation) {
            return collect([]);
        });
    }
}
```

## ./Models/TeamUser.php
```
<?php

namespace Aura\Base\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamUser extends Pivot
{
    protected $fillable = [
        'team_id',
        'user_id',
        'role_id',
    ];

    protected $table = 'user_role';
}
```

## ./Models/Meta.php
```
<?php

namespace Aura\Base\Models;

use Aura\Base\Collection\MetaCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'metable_type', 'metable_id'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'meta';

    /**
     * Get the owning metable model.
     */
    public function metable()
    {
        return $this->morphTo();
    }

    /**
     * @return MetaCollection
     */
    public function newCollection(array $models = [])
    {
        return new MetaCollection($models);
    }
}
```

## ./Models/Membership.php
```
<?php

namespace Aura\Base\Models;

class Membership
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
}
```

## ./Models/Scopes/TaxonomyScope.php
```
<?php

namespace Aura\Base\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TaxonomyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($model::getType()) {
            return $builder->where('taxonomy', $model::getType());
        }

        return $builder;
    }
}
```

## ./Models/Scopes/TeamScope.php
```
<?php

namespace Aura\Base\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TeamScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (config('aura.teams') === false) {
            return $builder;
        }

        return $builder;

        return $builder;

        // If the Model is a Team Resource, don't apply the scope
        if (auth()->user() && $model instanceof \Aura\Base\Resources\Team) {
            return $builder->whereId(auth()->user()->current_team_id);
        }

        if (auth()->user() && $model instanceof \Aura\Base\Resources\Role) {
            return $builder->where('posts.team_id', auth()->user()->current_team_id);
        }

        // if (auth()->user() && $model->getTable() == 'posts') {
        //     return $builder->where('posts.team_id', auth()->user()->current_team_id);
        // }

        // if(auth()->guest()) {
        //     return $builder;
        // }

        if (auth()->user() && $model->getTable() == 'posts') {
            return $builder->where($model->getTable().'.team_id', auth()->user()->current_team_id);
        }

        if (auth()->user()) {
            return $builder->where($model->getTable().'.team_id', auth()->user()->current_team_id);
        }

        // Check access?
        return $builder;
    }
}
```

## ./Models/Scopes/ScopedScope.php
```
<?php

namespace Aura\Base\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ScopedScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($model instanceof \Aura\Base\Resources\Role) {
            return $builder;
        }
        if ($model instanceof \Aura\Base\Resources\User) {
            return $builder;
        }

        // Superadmin
        if (auth()->user() && auth()->user() instanceof \Aura\Base\Resources\User && auth()->user()->isSuperAdmin()) {
            return $builder;
        }

        if (auth()->user() && auth()->user() instanceof \Aura\Base\Resources\User && auth()->user()->hasPermissionTo('scope', $model)) {
            $builder->where($model->getTable().'.user_id', auth()->user()->id);
        }

        // Check access?
        return $builder;
    }
}
```

## ./Models/Scopes/TypeScope.php
```
<?php

namespace Aura\Base\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TypeScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        return $builder->where('posts.type', $model::getType());
    }
}
```

## ./Exceptions/InvalidMetaTableException.php
```
<?php

namespace Aura\Base\Exceptions;

use Exception;

class InvalidMetaTableException extends Exception
{
    public function __construct($message = 'You need to define a custom meta table for this model.')
    {
        parent::__construct($message);
    }
}
```

## ./Policies/MetaPolicy.php
```
<?php

namespace Aura\Base\Policies;

use Aura\Base\Models\Meta;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MetaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Meta $meta)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Meta $meta)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Meta $meta)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Meta $meta)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Meta $meta)
    {
        //
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }
}
```

## ./Policies/UserPolicy.php
```
<?php

namespace Aura\Base\Policies;

class UserPolicy extends ResourcePolicy {}
```

## ./Policies/OptionPolicy.php
```
<?php

namespace Aura\Base\Policies;

class OptionPolicy extends ResourcePolicy {}
```

## ./Policies/TeamPolicy.php
```
<?php

namespace Aura\Base\Policies;

use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can add team members.
     *
     * @return mixed
     */
    public function addTeamMember(User $user, Team $team)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user, $team)
    {

        if ($team::$createEnabled === false) {
            return false;
        }

        // if ($user->isSuperAdmin()) {
        //     return true;
        // }

        // todo: maybe do this as a setting

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Team $team)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    public function inviteUsers(User $user, Team $team)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('invite-users', $team)) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can remove team members.
     *
     * @return mixed
     */
    public function removeTeamMember(User $user, Team $team)
    {

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Team $team)
    {
        if ($team::$editEnabled === false) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can update team member permissions.
     *
     * @return mixed
     */
    public function updateTeamMember(User $user, Team $team)
    {

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Team $team)
    {
        // if ($user->isSuperAdmin()) {
        //     return true;
        // }

        // Check if the resource view is enabled
        if ($team::$viewEnabled === false) {
            return false;
        }

        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user, Team $team)
    {
        if ($team::$indexViewEnabled === false) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }
}
```

## ./Policies/TaxonomyPolicy.php
```
<?php

namespace Aura\Base\Policies;

use Aura\Base\Models\Taxonomy;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxonomyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Taxonomy $taxonomy)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Taxonomy $taxonomy)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Taxonomy $taxonomy)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Taxonomy $taxonomy)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Taxonomy $taxonomy)
    {
        //
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }
}
```

## ./Policies/ResourcePolicy.php
```
<?php

namespace Aura\Base\Policies;

use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourcePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, $resource)
    {
        if ($resource::$createEnabled === false) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('create', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, $resource)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Scoped Posts
        if ($user->hasPermissionTo('scope', $resource) && $user->hasPermissionTo('delete', $resource)) {
            if ($resource->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->hasPermissionTo('delete', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, $resource)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('forceDelete', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, $resource)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->hasPermissionTo('restore', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, $resource)
    {
        if ($resource::$editEnabled === false) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Scoped Posts
        if ($user->hasPermissionTo('scope', $resource) && $user->hasPermissionTo('update', $resource)) {
            if ($resource->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->hasPermissionTo('update', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, $resource)
    {
        // Check if the config resource view is enabled
        if (config('aura.resource-view-enabled') === false) {
            return false;
        }

        // Check if the resource view is enabled
        if ($resource::$viewEnabled === false) {
            return false;
        }

        // Check if the user is a superadmin
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Scoped Posts
        if ($user->hasPermissionTo('scope', $resource) && $user->hasPermissionTo('view', $resource)) {
            if ($resource->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->hasPermissionTo('view', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, $resource)
    {
        if ($resource::$indexViewEnabled === false) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('viewAny', $resource)) {
            return true;
        }

        return false;
    }
}
```

## ./Operations/Mail.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;

class Mail extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Subject',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Subject of the Mail',
                'validation' => 'required',
                'slug' => 'subject',
            ],
            [
                'name' => 'To',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Recipient of the Mail',
                'validation' => 'required|email',
                'slug' => 'to',
            ],
            [
                'name' => 'Body',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'instructions' => 'Body of the Mail',
                'validation' => 'required',
                'slug' => 'body',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // throw an exception if there is no message
        $message = $operation->options['message'] ?? throw new \Exception('No Message');

        $operationLog->status = 'success';
        $operationLog->save();

        // Send the Mail with Laravel Mailer
        // dd($operation);
    }
}
```

## ./Operations/GetResource.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;

class GetResource extends BaseOperation
{
    public function run(Operation $operation, $post, $operationLog)
    {
        // dd('hier', $operation->options['resource_ids'], $operation->options['resource']);

        // throw an exception if there is no message
        $ids = $operation->options['resource_ids'] ?? throw new \Exception('No ID');
        $resource = $operation->options['resource'] ?? throw new \Exception('No Resource');

        // Get the Resource
        $resources = app($resource)::find($ids);

        // Update the operation_log
        $operationLog->response = $resources;
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
```

## ./Operations/Delay.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;

class Delay extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Delay',
                'type' => 'Aura\\Base\\Fields\\Number',
                'instructions' => 'Delay in seconds',
                'validation' => 'required|numeric',
                'slug' => 'delay',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // Delay successfull
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
```

## ./Operations/BaseOperation.php
```
<?php

namespace Aura\Base\Operations;

class BaseOperation
{
    public function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Options',
                'slug' => 'options-tab',
                'global' => true,
            ],
            [
                'name' => 'Type',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'type',
                'options' => [
                    [
                        'value' => 'Send Email',
                        'key' => 'Aura\\Base\\Operations\\Mail',
                    ],
                    [
                        'value' => 'Send Notification',
                        'key' => 'Aura\\Base\\Operations\\Notification',
                    ],
                    [
                        'value' => 'Webhook',
                        'key' => 'Aura\\Base\\Operations\\Webhook',
                    ],
                    [
                        'value' => 'Read Post',
                        'key' => 'Aura\\Base\\Operations\\GetResource',
                    ],
                    [
                        'value' => 'Create Post',
                        'key' => 'Aura\\Base\\Operations\\CreateResource',
                    ],
                    [
                        'value' => 'Update Post',
                        'key' => 'Aura\\Base\\Operations\\UpdateResource',
                    ],
                    [
                        'value' => 'Delete Post',
                        'key' => 'Aura\\Base\\Operations\\DeleteResource',
                    ],
                    [
                        'value' => 'Trigger Flow',
                        'key' => 'Aura\\Base\\Operations\\TriggerFlow',
                    ],
                    [
                        'value' => 'Transform Payload',
                        'key' => 'transform-payload',
                    ],
                    [
                        'value' => 'Write to Log',
                        'key' => 'Aura\\Base\\Operations\\Log',
                    ],
                    [
                        'value' => 'Console Command',
                        'key' => 'console-command',
                    ],
                    [
                        'value' => 'Condition',
                        'key' => 'condition',
                    ],
                    [
                        'value' => 'Delay',
                        'key' => 'Aura\\Base\\Operations\\Delay',
                    ],
                ],
            ],
            [
                'name' => 'Options',
                'type' => 'Aura\\Base\\Fields\\Group',
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'slug' => 'options',
            ],
            [
                'name' => 'X',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'x',
                'disabled' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Y',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [],
                'disabled' => true,
                'slug' => 'y',
                'style' => [
                    'width' => '50',
                ],
            ],
        ];
    }

    public function validateString($string)
    {
        $message = $string;

        // if safeString contains "{!!" or "@" or "!!}", throw an exception
        if (strpos($message, '{!!') !== false || strpos($message, '@') !== false || strpos($message, '!!}') !== false) {
            throw new \Exception('Message contains a blade tag 2');
        }

        if (strpos($message, '{{') == false && strpos($message, '}}') == false) {
            return $string;
        }

        $pattern = '/{{(?:\s*)?\$post(?:\s*)?->(?:(?!delete\(|get\(|create\(|update\()[a-z_\-0-9])*(?:\s*)?}}/';

        if (! preg_match($pattern, $string)) {
            throw new \Exception('Message contains a blade tag');
        }

        $safeString = trim(strip_tags($message));

        // if safeString contains a php tag, throw an exception
        if (strpos($safeString, '<?php') !== false) {
            throw new \Exception('Message contains php tag');
        }
        // if safeString contains a blade tag, throw an exception
        if (strpos($safeString, '@') !== false) {
            throw new \Exception('Message contains blade tag');
        }

        // if safeString contains "dd(" or "dump(" or "var_dump(" or "die(" or "exit(" or "exit;", throw an exception)
        if (strpos($safeString, 'dd(') !== false || strpos($safeString, 'dump(') !== false || strpos($safeString, 'var_dump(') !== false || strpos($safeString, 'die(') !== false || strpos($safeString, 'exit(') !== false || strpos($safeString, 'exit;') !== false) {
            throw new \Exception('Message contains a dump or die function');
        }

        // if safeString contains "eval(" or "assert(" or "base64_decode(" or "base64_encode(" or "gzinflate(" or "gzuncompress(" or "gzdecode(" or "str_rot13(" or "strrev(" or "str_shuffle(" or "str_split(" or "str_word_count(" or "strtr(" or "strnatcmp(" or "strnatcasecmp(" or "strncasecmp(" or "strncmp(" or "strpbrk(" or "strpos(" or "strrchr(" or "strrev(" or "strripos(" or "strrpos(" or "strspn(" or "strstr(" or "strtok(" or "strtolower(" or "strtoupper(" or "strtr(" or "substr_compare(" or "substr_count(" or "substr_replace(" or "substr(" or "trim(" or "ucfirst(" or "ucwords(" or "wordwrap(" or "addcslashes(" or "addslashes(" or "bin2hex(" or "chop(" or "chr(" or "chunk_split(" or "convert_cyr_string(" or "convert_uudecode(" or "convert_uuencode(" or "count_chars(" or "crc32(" or "crypt(" or "echo(" or "explode(" or "fprintf(" or "get_html_translation_table(" or "hebrev(" or "hebrevc(" or "hex2bin(" or "html_entity_decode(" or "htmlentities(" or "htmlspecialchars_decode(" or "htmlspecialchars(" or "implode(" or "join(" or "lcfirst(" or "levenshtein(" or "localeconv(" or "ltrim(" or "md5(" or "metaphone(" or "money_format(" or "nl_langinfo(" or "nl2br(" or "number_format(" or "ord(" or "parse_str(" or "print(" or "printf(" or "quoted_printable_decode(" or "quoted_printable_encode(" or "quotemeta(" or "rtrim(" or "setlocale(" or "sha1(" or "similar_text(" or "soundex(" or "sprintf(" or "sscanf(" or "str_getcsv(" or "str_ireplace(" or "str_pad(" or "str_repeat(" or "str_replace(" or "str_rot13(" or "str_shuffle(" or "str_split(", throw an exception)
        if (strpos($safeString, 'eval(') !== false || strpos($safeString, 'assert(') !== false || strpos($safeString, 'base64_decode(') !== false || strpos($safeString, 'base64_encode(') !== false || strpos($safeString, 'gzinflate(') !== false || strpos($safeString, 'gzuncompress(') !== false || strpos($safeString, 'gzdecode(') !== false || strpos($safeString, 'str_rot13(') !== false || strpos($safeString, 'strrev(') !== false || strpos($safeString, 'str_shuffle(') !== false || strpos($safeString, 'str_split(') !== false || strpos($safeString, 'str_word_count(') !== false || strpos($safeString, 'strtr(') !== false || strpos($safeString, 'strnatcmp(') !== false || strpos($safeString, 'strnatcasecmp(') !== false || strpos($safeString, 'strncasecmp(') !== false || strpos($safeString, 'strncmp(') !== false || strpos($safeString, 'strpbrk(') !== false || strpos($safeString, 'strpos(') !== false || strpos($safeString, 'strrchr(') !== false || strpos($safeString, 'strrev(') !== false || strpos($safeString, 'strripos(') !== false || strpos($safeString, 'strrpos(') !== false || strpos($safeString, 'strspn(') !== false || strpos($safeString, 'strstr(') !== false || strpos($safeString, 'strtok(') !== false || strpos($safeString, 'strtolower(') !== false || strpos($safeString, 'strtoupper(') !== false || strpos($safeString, 'strtr(') !== false || strpos($safeString, 'substr_compare(') !== false || strpos($safeString, 'substr_count(') !== false || strpos($safeString, 'substr_replace(') !== false || strpos($safeString, 'substr(') !== false || strpos($safeString, 'trim(') !== false || strpos($safeString, 'ucfirst(') !== false || strpos($safeString, 'ucwords(') !== false || strpos($safeString, 'wordwrap(') !== false || strpos($safeString, 'addcslashes(') !== false) {
            throw new \Exception('Message contains a string function');
        }

        // if safeString contains "array(" or "array_change_key_case(" or "array_chunk(" or "array_column(" or "array_combine(" or "array_count_values(" or "array_diff_assoc(" or "array_diff_key(" or "array_diff_uassoc(" or "array_diff_ukey(" or "array_diff(" or "array_fill_keys(" or "array_fill(" or "array_filter(" or "array_flip(" or "array_intersect_assoc(" or "array_intersect_key(" or "array_intersect_uassoc(" or "array_intersect_ukey(" or "array_intersect(" or "array_key_exists(" or "array_keys(" or "array_map(" or "array_merge_recursive(" or "array_merge(" or "array_multisort(" or "array_pad(" or "array_pop(" or "array_product(" or "array_push(" or "array_rand(" or "array_reduce(" or "array_replace_recursive(" or "array_replace(" or "array_reverse(" or "array_search(" or "array_shift(" or "array_slice(" or "array_splice(" or "array_sum(" or "array_udiff_assoc(" or "array_udiff_uassoc(" or "array_udiff(" or "array_uintersect_assoc(" or "array_uintersect_uassoc(" or "array_uintersect(" or "array_unique(" or "array_unshift(" or "array_values(" or "array_walk_recursive(" or "array_walk(" or "array(" or "arsort(" or "asort(" or "compact(" or "count(" or "current(" or "each(" or "end(" or "extract(" or "in_array(" or "key_exists(" or "key(" or "krsort(" or "ksort(" or "list(" or "natcasesort(" or "natsort(" or "next(" or "pos(" or "prev(" or "range(" or "reset(" or "rsort(" or "shuffle(" or "sizeof(" or "sort(" or "uasort(" or "uksort(" or "usort(" or "array_change_key_case(" or "array_chunk(" or "array_column(" or "array_combine(" or "array_count_values(" or "array_diff_assoc(" or "array_diff_key(" or "array_diff_uassoc(" or "array_diff_ukey(" or "array_diff(" or "array_fill_keys(" or "array_fill(" or "array_filter(", throw an exception
        if (strpos($safeString, 'array(') !== false || strpos($safeString, 'array_change_key_case(') !== false || strpos($safeString, 'array_chunk(') !== false || strpos($safeString, 'array_column(') !== false || strpos($safeString, 'array_combine(') !== false || strpos($safeString, 'array_count_values(') !== false || strpos($safeString, 'array_diff_assoc(') !== false || strpos($safeString, 'array_diff_key(') !== false || strpos($safeString, 'array_diff_uassoc(') !== false || strpos($safeString, 'array_diff_ukey(') !== false || strpos($safeString, 'array_diff(') !== false || strpos($safeString, 'array_fill_keys(') !== false || strpos($safeString, 'array_fill(') !== false || strpos($safeString, 'array_filter(') !== false || strpos($safeString, 'array_flip(') !== false || strpos($safeString, 'array_intersect_assoc(') !== false || strpos($safeString, 'array_intersect_key(') !== false || strpos($safeString, 'array_intersect_uassoc(') !== false || strpos($safeString, 'array_intersect_ukey(') !== false || strpos($safeString, 'array_intersect(') !== false || strpos($safeString, 'array_key_exists(') !== false || strpos($safeString, 'array_keys(') !== false || strpos($safeString, 'array_map(') !== false || strpos($safeString, 'array_merge_recursive(') !== false || strpos($safeString, 'array_merge(') !== false || strpos($safeString, 'array_multisort(') !== false || strpos($safeString, 'array_pad(') !== false || strpos($safeString, 'array_pop(') !== false || strpos($safeString, 'array_product(') !== false || strpos($safeString, 'array_push(') !== false || strpos($safeString, 'array_rand(') !== false || strpos($safeString, 'array_reduce(') !== false || strpos($safeString, 'array_replace_recursive(') !== false || strpos($safeString, 'array_replace(') !== false || strpos($safeString, 'array_reverse(') !== false || strpos($safeString, 'array_search(') !== false || strpos($safeString, 'array_shift(') !== false) {
            throw new \Exception('Message contains an array function');
        }

        // if safeString contains SQL keywords, throw an exception
        if (strpos($safeString, 'SELECT') !== false || strpos($safeString, 'UPDATE') !== false || strpos($safeString, 'DELETE') !== false || strpos($safeString, 'INSERT') !== false || strpos($safeString, 'CREATE') !== false || strpos($safeString, 'DROP') !== false || strpos($safeString, 'ALTER') !== false || strpos($safeString, 'TRUNCATE') !== false || strpos($safeString, 'RENAME') !== false || strpos($safeString, 'GRANT') !== false || strpos($safeString, 'REVOKE') !== false || strpos($safeString, 'LOCK') !== false || strpos($safeString, 'UNLOCK') !== false || strpos($safeString, 'INDEX') !== false || strpos($safeString, 'VIEW') !== false || strpos($safeString, 'TABLE') !== false || strpos($safeString, 'DATABASE') !== false || strpos($safeString, 'TRIGGER') !== false || strpos($safeString, 'PROCEDURE') !== false || strpos($safeString, 'FUNCTION') !== false || strpos($safeString, 'EVENT') !== false || strpos($safeString, 'USER') !== false || strpos($safeString, 'PASSWORD') !== false || strpos($safeString, 'ROLE') !== false || strpos($safeString, 'SCHEMA') !== false || strpos($safeString, 'SESSION') !== false || strpos($safeString, 'TRANSACTION') !== false || strpos($safeString, 'COMMIT') !== false || strpos($safeString, 'ROLLBACK') !== false || strpos($safeString, 'SAVEPOINT') !== false || strpos($safeString, 'EXPLAIN') !== false || strpos($safeString, 'DESCRIBE') !== false || strpos($safeString, 'SHOW') !== false || strpos($safeString, 'DESC') !== false || strpos($safeString, 'EXPLAIN') !== false || strpos($safeString, 'DESCRIBE') !== false || strpos($safeString, 'SHOW') !== false || strpos($safeString, 'DESC') !== false || strpos($safeString, 'EXPLAIN') !== false || strpos($safeString, 'DESCRIBE') !== false || strpos($safeString, 'SHOW') !== false || strpos($safeString, 'DESC') !== false || strpos($safeString, 'EXPLAIN') !== false) {
            throw new \Exception('Message contains SQL keywords');
        }

        // if safeString contains delete or truncate or drop, or any other dangerous keywords, throw an exception
        if (strpos($safeString, 'delete') !== false || strpos($safeString, 'truncate') !== false || strpos($safeString, 'drop') !== false || strpos($safeString, 'alter') !== false || strpos($safeString, 'create') !== false || strpos($safeString, 'update') !== false || strpos($safeString, 'insert') !== false || strpos($safeString, 'select') !== false || strpos($safeString, 'replace') !== false || strpos($safeString, 'grant') !== false || strpos($safeString, 'revoke') !== false || strpos($safeString, 'lock') !== false || strpos($safeString, 'unlock') !== false || strpos($safeString, 'index') !== false || strpos($safeString, 'view') !== false || strpos($safeString, 'table') !== false || strpos($safeString, 'database') !== false || strpos($safeString, 'trigger') !== false || strpos($safeString, 'procedure') !== false || strpos($safeString, 'function') !== false || strpos($safeString, 'event') !== false || strpos($safeString, 'user') !== false || strpos($safeString, 'password') !== false || strpos($safeString, 'role') !== false || strpos($safeString, 'schema') !== false || strpos($safeString, 'session') !== false || strpos($safeString, 'transaction') !== false || strpos($safeString, 'commit') !== false || strpos($safeString, 'rollback') !== false || strpos($safeString, 'savepoint') !== false || strpos($safeString, 'explain') !== false || strpos($safeString, 'describe') !== false || strpos($safeString, 'show') !== false || strpos($safeString, 'desc') !== false || strpos($safeString, 'explain') !== false || strpos($safeString, 'describe') !== false || strpos($safeString, 'show') !== false || strpos($safeString, 'desc') !== false || strpos($safeString, 'explain') !== false || strpos($safeString, 'describe') !== false || strpos($safeString, 'show') !== false || strpos($safeString, 'desc') !== false || strpos($safeString, 'explain') !== false) {
            throw new \Exception('Message contains dangerous keywords');
        }

        return $safeString;
    }
}
```

## ./Operations/CreateResource.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;

class CreateResource extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Class of the Resource',
                'validation' => 'required',
                'slug' => 'resource',
            ],
            [
                'name' => 'Data Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Title of the Resource',
                'validation' => 'required',
                'slug' => 'data.title',
            ],
            [
                'name' => 'Data Status',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Status of the Resource',
                'validation' => 'required',
                'slug' => 'data.status',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // dd('send notification', $operation->toArray(), $post->toArray(), $operationLog->toArray());

        // if $post->type is the same as $operation->options['resource'] then throw expception
        // dd($post->type, $operation->options['resource']);

        if ($operation->flow->options['event'] == 'created' && get_class($post) == $operation->options['resource']) {
            throw new \Exception('Cannot create post of same type');
        }

        // throw an exception if there is no message
        if ($operation->options['data'] == null) {
            throw new \Exception('No Values');
        }
        $values = $operation->options['data'];

        if ($operation->options['resource'] == null) {
            throw new \Exception('No Resource');
        }
        $resource = $operation->options['resource'];

        // Create the Resource with the values
        $resource = app($resource)->create($values);

        // Update the operation_log
        $operationLog->response = $resource;
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
```

## ./Operations/Webhook.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;

class Webhook extends BaseOperation
{
    public function run(Operation $operation, $post, $operationLog)
    {
        // dd('send notification', $operation->toArray(), $post->toArray(), $operationLog->toArray());

        // throw an exception if there is no message
        $URL = $operation->options['URL'] ?? throw new \Exception('No URL');
        $method = $operation->options['method'] ?? 'POST';
        $headers = $operation->options['headers'] ?? [];
        $body = $operation->options['body'] ?? '';

        // call the webhook
        $client = new \GuzzleHttp\Client;
        $response = $client->request($method, $URL, [
            'headers' => $headers,
            'body' => $body,
        ]);

        $operationLog->status = 'success';
        $operationLog->save();
    }
}
```

## ./Operations/DeleteResource.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;

class DeleteResource extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Select Type',
                'type' => 'Aura\\Base\\Fields\\Select',
                'instructions' => 'Select the type of delete',
                'validation' => '',
                'live' => true,
                'slug' => 'type',
                'options' => [
                    'input' => 'Input',
                    'custom' => 'Custom',
                ],
            ],
            [
                'name' => 'User ID',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Which user to send the notification to',
                'validation' => 'required',
                'conditional_logic' => [
                    [
                        'field' => 'options.type',
                        'operator' => '==',
                        'value' => 'custom',
                    ],
                ],
                'slug' => 'resource',
            ],
            [
                'name' => 'Role',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Which role to send the notification to',
                'conditional_logic' => [
                    [
                        'field' => 'options.type',
                        'operator' => '==',
                        'value' => 'custom',
                    ],
                ],
                'validation' => 'required',
                'slug' => 'resource_ids',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {

        // dd($operation->flow->trigger == 'resource' && get_class($post) == $operation->flow->options['resource'], get_class($post));
        // get the resource type of the post and the resource type of the flow trigger
        // throw an exception if the resource type of the post is the same as the resource type of the flow trigger
        // if ($operation->flow->trigger == 'resource' && get_class($post) == $operation->flow->options['resource']) {
        //     throw new \Exception('Cannot delete post of same type');
        // }

        // dd('bish ier');

        if (isset($operation->flow->options['event']) && $operation->flow->options['event'] == 'deleted' && get_class($post) == $operation->options['resource']) {
            throw new \Exception('Cannot delete post of same type');
        }

        // throw an exception if there are no ids
        if ($operation->options['type'] == 'custom') {
            if ($operation->options['resource_ids'] == null) {
                throw new \Exception('No Resource Ids');
            }
            $ids = $operation->options['resource_ids'];

            if ($operation->options['resource'] == null) {
                throw new \Exception('No Resource');
            }
            $resource = $operation->options['resource'] ?? throw new \Exception('No Resource');
        } else {
            $ids = [$post->id];
            $resource = get_class($post);
        }

        // Get the Resource
        $resources = app($resource)->find($ids);

        // delete the Resources
        $resources->each(function ($r) {
            $r->delete();
        });

        // dd('hier', $resources);
        // Update the operation_log
        $operationLog->response = $resources;
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
```

## ./Operations/TriggerFlow.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Flow;
use Aura\Flows\Resources\Operation;

class TriggerFlow extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Flow ID',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Which flow to trigger',
                'validation' => '',
                'slug' => 'flow_id',
            ],

            [
                'name' => 'Pass Response',
                'type' => 'Aura\\Base\\Fields\\Select',
                'instructions' => 'Select the type of notification',
                'validation' => '',
                'live' => true,
                'slug' => 'response',
                'options' => [
                    'resource' => 'Post',
                    'flow' => 'Flow',
                ],
            ],

        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        //dd('trigger flow', $operation->toArray(), $post->toArray(), $operationLog->toArray());

        // throw an exception if there is no message
        $triggerFlowId = $operation->options['flow_id'] ?? throw new \Exception('No Flow to be triggered');
        $triggerFlow = Flow::where('trigger', 'flow')->find($triggerFlowId);

        // operation with operation_id of the flow
        $operation = Operation::find($triggerFlow->operation_id);

        // Create a Flow Log
        $flowLog = $triggerFlow->logs()->create([
            'post_id' => $post->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Run the Operation
        $operation->run($post, $flowLog->id);

        // Update the operation_log
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
```

## ./Operations/Log.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log as LaravelLog;

class Log extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Message',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Message that will be logged in the laravel log',
                'validation' => 'required',
                'slug' => 'message',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog, $data = null)
    {
        if ($data) {
            $post = $data;
        }
        // dd('hier', $operation->options['message']);
        // throw an exception if there is no message
        $message = $operation->options['message'] ?? throw new \Exception('No Message');

        $message = $this->validateString($message);

        // if message contains "{{" and "}}" then it is a blade template
        if (strpos($message, '{{') !== false && strpos($message, '}}') !== false) {
            $renderedMessage = Blade::render($message, [
                'resource' => $post,
            ]);
        } else {
            $renderedMessage = $message;
        }

        $operationLog->response = [
            'message' => $renderedMessage,
        ];
        $operationLog->save();

        LaravelLog::info($renderedMessage);

        return $post;
    }
}
```

## ./Operations/Condition.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;

class Condition extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Condition',
                'type' => 'Aura\\Base\\Fields\\Code',
                'instructions' => 'Condition to evaluate',
                'validation' => 'required',
                'slug' => 'delay',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // Delay successfull
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
```

## ./Operations/Notification.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Base\Notifications\FlowNotification;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Flows\Resources\Operation;
use Illuminate\Support\Facades\Blade;

class Notification extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Select Type',
                'type' => 'Aura\\Base\\Fields\\Select',
                'instructions' => 'Select the type of notification',
                'validation' => '',
                'live' => true,
                'slug' => 'type',
                'options' => [
                    'user' => 'User',
                    'role' => 'Role',
                ],
            ],
            [
                'name' => 'User ID',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Which user to send the notification to',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'options.type',
                        'operator' => '==',
                        'value' => 'user',
                    ],
                ],
                'slug' => 'user_id',
            ],
            [
                'name' => 'Role',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Which role to send the notification to',
                'conditional_logic' => [
                    [
                        'field' => 'options.type',
                        'operator' => '==',
                        'value' => 'role',
                    ],
                ],
                'validation' => '',
                'slug' => 'role_id',
            ],
            [
                'name' => 'Message',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'instructions' => 'Message of the notifictation',
                'validation' => 'required',
                'slug' => 'message',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // dd('send notification', $operation->toArray(), $post->toArray(), $operationLog->toArray());

        // throw an exception if there is no message
        $message = $operation->options['message'] ?? throw new \Exception('No Message');

        $message = $this->validateString($message);

        // if message contains "{{" and "}}" then it is a blade template
        if (strpos($message, '{{') !== false && strpos($message, '}}') !== false) {
            $renderedMessage = Blade::render($message, [
                'resource' => $post,
            ]);
        } else {
            $renderedMessage = $message;
        }

        $type = $operation->options['type'] ?? 'user';

        if ($type == 'role') {
            // dd('Role', $operation->options['role_id']);
            $role = Role::find($operation->options['role_id']);

            // Get all users with this role

            $users = $role->users;
            // foreach user send the notification
            foreach ($users as $user) {
                $user->notify(new FlowNotification($post, $renderedMessage));
            }
        } else {
            $user = User::find($operation->options['user_id']);
            // Send the notification
            $user->notify(new FlowNotification($post, $renderedMessage));
        }

        $operationLog->response = [
            'message' => $renderedMessage,
        ];

        // Update the operation_log
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
```

## ./Operations/UpdateResource.php
```
<?php

namespace Aura\Base\Operations;

use Aura\Base\Resources\Post;
use Aura\Flows\Resources\Operation;

class UpdateResource extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Select Type',
                'type' => 'Aura\\Base\\Fields\\Select',
                'instructions' => 'Select which type of resource to update',
                'validation' => '',
                'live' => true,
                'slug' => 'type',
                'options' => [
                    'input' => 'Input Data',
                    'custom' => 'Custom',
                ],
            ],
            [
                'name' => 'User ID',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Which user to send the notification to',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'options.type',
                        'operator' => '==',
                        'value' => 'user',
                    ],
                ],
                'slug' => 'user_id',
            ],
            [
                'name' => 'Role',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Which role to send the notification to',
                'conditional_logic' => [
                    [
                        'field' => 'options.type',
                        'operator' => '==',
                        'value' => 'role',
                    ],
                ],
                'validation' => '',
                'slug' => 'role_id',
            ],
            [
                'name' => 'Message',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'instructions' => 'Message of the notifictation',
                'validation' => 'required',
                'slug' => 'message',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // dd('send notification', $operation->toArray(), $post->toArray(), $operationLog->toArray());
        // dump('update resource', $operation->options);

        // if ($operation->flow->options['event'] == 'updated' && get_class($post) == $operation->options['resource']) {
        //     throw new \Exception('Cannot update post of same type');
        // }

        if ($operation->options['type'] != Post::class) {
            // throw an exception if there is no message
            if ($operation->options['resource_ids'] == null) {
                throw new \Exception('No Resource Ids');
            }
            $ids = $operation->options['resource_ids'];

            if ($operation->options['resource'] == null) {
                throw new \Exception('No Resource');
            }
            $resource = $operation->options['resource'];
        } else {
            $ids = [$post->id];
            $resource = get_class($post);
        }

        if ($operation->options['data'] == null) {
            throw new \Exception('No Values');
        }
        $values = $operation->options['data'];

        if (optional($operation->options)['resource_source'] != null) {
            $o = Operation::find($operation->options['resource_source'])->logs()->latest()->first()->response;
            $decoded = $o;
            // get the IDs from the response
            $ids = [];
            foreach ($decoded as $d) {
                $ids[] = $d['id'];
            }
        }

        // Get the Resource
        $resources = app($resource)->find($ids);

        // Update the Resource
        $resources->each(function ($resource) use ($values) {
            // update the resource silently

            $resource->updateQuietly($values);
        });

        // Update the operation_log
        $operationLog->response = $resources;
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
```

## ./AuraServiceProvider.php
```
<?php

namespace Aura\Base;

use Livewire\Livewire;
use Livewire\Component;
use Aura\Base\Widgets\Bar;
use Aura\Base\Widgets\Pie;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Aura\Base\Widgets\Donut;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Livewire\Modals;
use Aura\Base\Widgets\Widgets;
use Aura\Base\Commands\MakeUser;
use Aura\Base\Commands\MakeField;
use Aura\Base\Livewire\Dashboard;
use Aura\Base\Livewire\CreateFlow;
use Aura\Base\Livewire\InviteUser;
use Aura\Base\Livewire\Navigation;
use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Policies\UserPolicy;
use Aura\Base\Widgets\ValueWidget;
use Illuminate\Support\Facades\DB;
use Aura\Base\Livewire\PluginsPage;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Widgets\SparklineBar;
use Aura\Base\Commands\MakeResource;
use Aura\Base\Livewire\BookmarkPage;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Widgets\SparklineArea;
use Illuminate\Support\Facades\Gate;
use Aura\Base\Livewire\EditOperation;
use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\Notifications;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\View;
use Illuminate\Support\Facades\Blade;
use Aura\Base\Commands\PublishCommand;
use Aura\Base\Livewire\CreateResource;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\ResourceEditor;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Commands\ExtendUserModel;
use Aura\Base\Livewire\Resource\Create;
use Spatie\LaravelPackageTools\Package;
use Aura\Base\Commands\CreateAuraPlugin;
use Aura\Base\Commands\AuraLayoutCommand;
use Aura\Base\Livewire\EditResourceField;
use Illuminate\Database\Eloquent\Builder;
use Aura\Base\Commands\CustomizeComponent;
use Aura\Base\Livewire\Resource\EditModal;
use Aura\Base\Commands\DatabaseToResources;
use Aura\Base\Commands\InstallConfigCommand;
use Aura\Base\Livewire\Resource\CreateModal;
use Aura\Base\Commands\CreateResourceFactory;
use Aura\Base\Commands\MigratePostMetaToMeta;
use Aura\Base\Commands\CreateResourceMigration;
use Aura\Base\Commands\TransformTableToResource;
use Aura\Base\Commands\CreateResourcePermissions;
use Aura\Base\Commands\UpdateSchemaFromMigration;
use Aura\Base\Livewire\TwoFactorAuthenticationForm;
use Illuminate\Database\Eloquent\Relations\Relation;
use Aura\Base\Commands\MigrateFromPostsToCustomTable;
use Aura\Base\Commands\TransferFromPostsToCustomTable;
use Aura\Base\Navigation\Navigation as AuraNavigation;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Aura\Base\Livewire\Attachment\Index as AttachmentIndex;

class AuraServiceProvider extends PackageServiceProvider
{
    protected $commands = [
        // ... other commands ...
        Commands\AuraLayoutCommand::class,
    ];

    // boot
    public function boot()
    {
        parent::boot();
    }

    public function bootGate()
    {
        if (config('aura.teams')) {
            Gate::policy(Team::class, TeamPolicy::class);
        }

        Gate::policy(Resource::class, ResourcePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function ($user, $ability) {
            //  if ($ability == 'view' && config('aura.resource-view-enabled') === false) {
            //     return false;
            // }

            // if ($user->isSuperAdmin()) {
            //     return true;
            // }
        });

        return $this;
    }

    public function bootLivewireComponents()
    {
        Livewire::component('aura::resource-index', app(Index::class));
        Livewire::component('aura::resource-create', app(Create::class));
        Livewire::component('aura::resource-create-modal', app(CreateModal::class));
        Livewire::component('aura::resource-edit', app(Edit::class));
        Livewire::component('aura::resource-edit-modal', app(EditModal::class));
        Livewire::component('aura::resource-view', app(View::class));
        Livewire::component('aura::table', app(Table::class));
        Livewire::component('aura::navigation', Navigation::class);
        Livewire::component('aura::global-search', GlobalSearch::class);
        Livewire::component('aura::bookmark-page', BookmarkPage::class);
        Livewire::component('aura::dashboard', Dashboard::class);
        Livewire::component('aura::notifications', Notifications::class);
        Livewire::component('aura::edit-resource-field', EditResourceField::class);
        Livewire::component('aura::media-manager', MediaManager::class);
        Livewire::component('aura::media-uploader', app(MediaUploader::class));
        Livewire::component('aura::attachment-index', AttachmentIndex::class);
        Livewire::component('aura::user-two-factor-authentication-form', TwoFactorAuthenticationForm::class);
        Livewire::component('aura::create-resource', CreateResource::class);
        Livewire::component('aura::resource-editor', ResourceEditor::class);
        Livewire::component('aura::settings', app(config('aura.components.settings')));
        Livewire::component('aura::invite-user', InviteUser::class);

        Livewire::component('aura::profile', app(config('aura.components.profile')));
        Livewire::component('aura::modals', Modals::class);

        // Flows
        Livewire::component('aura::create-flow', CreateFlow::class);
        Livewire::component('aura::edit-operation', EditOperation::class);

        Livewire::component('aura::plugins-page', PluginsPage::class);

        // Widgets
        Livewire::component('aura::widgets', Widgets::class);
        Livewire::component('aura::widgets.value-widget', ValueWidget::class);
        Livewire::component('aura::widgets.sparkline-area', SparklineArea::class);
        Livewire::component('aura::widgets.sparkline-bar', SparklineBar::class);
        Livewire::component('aura::widgets.donut', Donut::class);
        Livewire::component('aura::widgets.pie', Pie::class);
        Livewire::component('aura::widgets.bar', Bar::class);

        return $this;
    }

    /*
    * This class is a Package Service Provider
    *
    * More info: https://github.com/spatie/laravel-package-tools
    */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('aura')
            ->hasConfigFile(['aura','aura-settings'])
            ->hasViews('aura')
            ->hasAssets()
            ->hasRoutes('web')
            ->hasMigrations(['create_aura_tables', 'create_flows_table'])
            ->runsMigrations()
            ->hasCommands([
                InstallConfigCommand::class,
                MakeResource::class,
                MakeUser::class,
                CustomizeComponent::class,
                CreateAuraPlugin::class,
                MakeField::class,
                PublishCommand::class,
                CreateResourceMigration::class,
                DatabaseToResources::class,
                TransformTableToResource::class,
                CreateResourcePermissions::class,
                ExtendUserModel::class,
                UpdateSchemaFromMigration::class,
                CreateResourceFactory::class,
                AuraLayoutCommand::class,
                MigratePostMetaToMeta::class,
                MigrateFromPostsToCustomTable::class,
                TransferFromPostsToCustomTable::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function (InstallCommand $command) {
                        $command->info('Hello, thank you for installing Aura!');
                    })
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('aura-cms/base')
                    ->endWith(function (InstallCommand $command) {
                        $command->call('aura:extend-user-model');

                        if ($command->confirm('Do you want to create a user?', true)) {
                            $command->call('aura:user');
                        }
                    });
            });

    }

    public function packageBooted()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->package->basePath('/../resources/dist') => public_path("vendor/{$this->package->shortName()}"),
                $this->package->basePath('/../resources/libs') => public_path("vendor/{$this->package->shortName()}/libs"),
                $this->package->basePath('/../resources/public') => public_path("vendor/{$this->package->shortName()}/public"),
            ], "{$this->package->shortName()}-assets");
        }

        Component::macro('notify', function ($message, $type = 'success') {
            $this->dispatch('notify', message: $message, type: $type);
        });

        // Search in multiple columns
        Builder::macro('searchIn', function ($columns, $search, $model) {
            return $this->where(function ($query) use ($columns, $search, $model) {
                foreach (Arr::wrap($columns) as $column) {
                    if ($model->isMetaField($column)) {
                        $metaTable = $model->getMetaTable();
                        $metaForeignKey = $model->getMetaForeignKey();

                        $query->orWhereExists(function ($subquery) use ($metaTable, $metaForeignKey, $column, $search, $model) {
                            $subquery->select(DB::raw(1))
                                ->from($metaTable)
                                ->whereColumn($model->getTable().'.id', $metaTable.'.'.$metaForeignKey)
                                ->where($metaTable.'.key', $column)
                                ->where($metaTable.'.value', 'like', '%'.$search.'%');
                        });
                    } else {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                }
            });
        });

        // CheckCondition Blade Directive
        Blade::if('checkCondition', function ($model, $field, $post = null) {
            return \Aura\Base\Aura::checkCondition($model, $field, $post);
        });

        Blade::if('superadmin', function () {
            return auth()->user()->isSuperAdmin();
        });

        Blade::if('local', function () {
            return app()->environment('local');
        });

        Blade::if('production', function () {
            return app()->environment('production');
        });

        Blade::directive('auraStyles', function (string $expression) {
            return "<?php echo app('aura')::styles(); ?>";
        });

        Blade::directive('auraScripts', function (string $expression) {
            return "<?php echo app('aura')::scripts(); ?>";
        });

        // Register the morph map for the resources
        // $resources = Aura::resources()->mapWithKeys(function ($resource) {
        //     return [$resource => 'Aura\Base\Resources\\'.str($resource)->title];
        // })->toArray();

        // Defer route loading until after all providers have booted
        // TEMP: DISABLED ROUTES
        // $this->app->booted(function () {
        //     $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        // });

        // Register the morph map to handle both user classes
        // Relation::morphMap([
        //     'Aura\Base\Resources\User' => 'App\Models\User',
        //     'Aura\Base\Resources\User' => 'Aura\Base\Resources\User',
        // ]);

        // Relation::morphMap([
        //     'Aura\Base\Resources\User' => 'App\Models\User',
        //     'Aura\Base\Resources\User' => 'Aura\Base\Resources\User',
        // ]);

        // ray('relation');

        $this
            ->bootGate()
            ->bootLivewireComponents();
    }

    public function packageRegistered()
    {
        parent::packageRegistered();

        $this->app->singleton('hook_manager', function ($app) {
            return new HookManager;
        });

        $this->app->singleton('dynamicFunctions', function ($app) {
            return new \Aura\Base\Facades\DynamicFunctions;
        });

        $this->app->singleton('dynamic_functions', function ($app) {
            return new DynamicFunctions;
        });

        $this->app->singleton('navigation', function ($app) {
            return new AuraNavigation;
        });

        $this->app->scoped('aura', function (): Aura {
            return app(Aura::class);
        });

        // dd(config('aura.resources.user'));

        app('aura')::registerResources([
            config('aura.resources.attachment'),
            config('aura.resources.option'),
            config('aura.resources.post'),
            config('aura.resources.permission'),
            config('aura.resources.role'),
            config('aura.resources.user'),
            config('aura.resources.tag'),
        ]);

        // dd(config('aura.resources.post'));

        if (config('aura.teams')) {
            app('aura')::registerResources([
                config('aura.resources.team'),
                config('aura.resources.team-invitation'),
            ]);
        }

        // Register Fields from src/Fields
        $fields = collect(app('files')->files(__DIR__.'/Fields'))
            ->map(function ($field) {
                $className = 'Aura\Base\Fields\\'.str($field->getFilename())->replace('.php', '');

                return $className !== 'Aura\Base\Fields\Field' ? $className : null;
            })
            ->filter()
            ->toArray();

        app('aura')::registerFields($fields);

        // Register App Resources
        app('aura')::registerResources(app('aura')::getAppResources());
        app('aura')::registerWidgets(app('aura')::getAppWidgets());
        app('aura')::registerFields(app('aura')::getAppFields());
    }

    public function registeringPackage()
    {
        //$package->hasRoute('web');
        //$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    protected function getResources(): array
    {
        return config('aura.resources');
    }
}
```

## ./ConditionalLogic.php
```
<?php

namespace Aura\Base;

use Illuminate\Support\Facades\Auth;

class ConditionalLogic
{
    private static $shouldDisplayFieldCache = [];

    public static function checkCondition($model, $field, $post = null)
    {
        $conditions = $field['conditional_logic'] ?? null;

        if (! $conditions) {

            return true;
        }

        // if (! Auth::check()) {

        //     return false;
        // }

        if ($conditions instanceof \Closure) {
            $result = self::executeClosure($conditions, $model, $post);

            return $result;
        }

        if (! is_array($conditions)) {

            return true;
        }

        foreach ($conditions as $index => $condition) {

            if ($condition instanceof \Closure) {
                $result = self::executeClosure($condition, $model, $post);

                if (! $result) {
                    return false;
                }
            } elseif (is_array($condition)) {
                $show = $condition['field'] === 'role'
                    ? self::handleRoleCondition($condition)
                    : self::handleDefaultCondition($model, $condition, $post);

                if (! $show) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function checkFieldCondition($condition, $fieldValue)
    {

        $result = match ($condition['operator']) {
            '==' => $fieldValue == $condition['value'],
            '!=' => $fieldValue != $condition['value'],
            '<=' => $fieldValue <= $condition['value'],
            '>' => $fieldValue > $condition['value'],
            '<' => $fieldValue < $condition['value'],
            '>=' => $fieldValue >= $condition['value'],
            default => false
        };

        return $result;
    }

    public static function clearConditionsCache()
    {
        self::$shouldDisplayFieldCache = [];
    }

    public static function fieldIsVisibleTo($field, $user)
    {
        $conditions = $field['conditional_logic'] ?? null;
        if (! $conditions || $user->isSuperAdmin()) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! $field || ($condition['field'] === 'role' && ! self::checkRoleCondition($condition))) {
                return false;
            }
        }

        return true;
    }

    public static function shouldDisplayField($model, $field, $post = null)
    {
        if (! $field || empty($field['conditional_logic'])) {
            return true;
        }

        $cacheKey = md5(get_class($model).json_encode($field).json_encode($post).Auth::id());

        if ($field['slug'] === 'prompt') {
            // ray($cacheKey, $post)->blue();
        }

        return self::$shouldDisplayFieldCache[$cacheKey]
            ??= self::checkCondition($model, $field, $post);
    }

    private static function checkRoleCondition($condition)
    {
        $user = Auth::user();

        return match ($condition['operator']) {
            '==' => $user->hasRole($condition['value']),
            '!=' => ! $user->hasRole($condition['value']),
            default => false
        };
    }

    private static function executeClosure(\Closure $closure, $model, $post = null)
    {
        try {
            // If $post is null, create a fields array from the model
            if ($post === null) {
                $fields = $model->getFieldsWithoutConditionalLogic();
                $post = ['fields' => $fields];
            }

            return $closure($model, $post) !== false;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            return false;
        }
    }

    private static function handleDefaultCondition($model, $condition, $post)
    {
        $fieldValue = null;

        if (is_object($model)) {
            if (property_exists($model, $condition['field'])) {
                $fieldValue = $model->{$condition['field']};
            } elseif (method_exists($model, 'getMeta')) {
                $fieldValue = $model->getMeta($condition['field']);
            }
        } elseif (is_array($model) && array_key_exists($condition['field'], $model)) {
            $fieldValue = $model[$condition['field']];
        }

        if ($fieldValue === null && $post !== null) {
            $fieldValue = data_get($post['fields'] ?? [], $condition['field']);
        }

        if ($fieldValue === null && str_contains($condition['field'], '.')) {
            $fieldValue = is_array($model)
                ? data_get($model, $condition['field'])
                : data_get($model instanceof \ArrayAccess ? $model->toArray() : (array) $model, $condition['field']);
        }

        $result = $fieldValue !== null ? self::checkFieldCondition($condition, $fieldValue) : false;

        return $result;
    }

    private static function handleRoleCondition($condition)
    {
        if (Auth::user()->isSuperAdmin()) {
            return true;
        }

        return self::checkRoleCondition($condition);
    }
}
```

## ./Livewire/EditOperation.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Traits\RepeaterFields;
use Aura\Flows\Resources\Operation;
use Illuminate\Support\Arr;
use Livewire\Component;

class EditOperation extends Component
{
    use RepeaterFields;

    public $model;

    public $open = false;

    public $resource;

    public function activate($params)
    {
        $this->model = Operation::find($params['model']);

        // dd($this->model->validationRules());

        $this->form['fields'] = $this->model->fields;

        // watch open property and trigger function on change
        // $this->watch('open', 'validateBeforeClosing');

        // dd($this->form['fields']);

        // Merge fields from type with fields from model
        // $this->form['fields'] = array_merge($this->form['fields'], $this->model->type->fields);

        $this->open = true;
    }

    public function deleteOperation($id)
    {
        $this->model = Operation::find($id);

        $this->model->delete();

        $this->open = false;

        $this->dispatch('refreshComponent');
    }

    public function getGroupedFieldsProperty()
    {
        // Fields from the model
        $modelFields = $this->model->getFields();

        // Specific fields for the operation type
        $operationFields = app($this->model->type)->getFields();

        // Merge the two
        $fields = array_merge($modelFields, $operationFields);

        return $this->model->getGroupedFields($fields);
    }

    public function render()
    {
        return view('aura::livewire.edit-operation');
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->model->validationRules(),
        ]);
    }

    public function save()
    {
        // dd($this->rules(), $this->resource);
        // Validate

        $this->validate();

        // dd($this->resource, $this->model);
        $this->model->update($this->form['fields']);

        // emit event to parent with slug and value
        // $this->dispatch('saveField', ['slug' => $this->resource['key'], 'value' => $this->form['fields']]);

        // emit to parent, that operation has been updated

        $this->open = false;

        $this->dispatch('updatedOperation');

        $this->notify('Operation saved');
    }

    public function updateType()
    {
        // when type is changed, update the fields
        $this->model->update(['type' => $this->form['fields']['type']]);

        $this->dispatch('updatedOperation');
    }

    // public function updatingOpen($value)
    // {
    //     dd('updatingOpen', $value);
    // }

    public function validateBeforeClosing()
    {
        // dd('validateBeforeClosing', $value);
        $this->validate();

        $this->open = false;
    }
}
```

## ./Livewire/CreateFlow.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Flows\Resources\Flow;
use Aura\Flows\Resources\Operation;
use Livewire\Component;

class CreateFlow extends Component
{
    public Flow $flow;

    public $model;

    public $name;

    public $operations;

    public $options;

    public $status;

    public $trigger;

    protected $listeners = ['refreshComponent' => '$refresh', 'updatedOperation' => 'refresh'];

    protected $rules = [
        'flow' => '',
        'flow.id' => '',
        'flow.name' => '',
        'flow.operation_id' => '',
        'flow.trigger' => '',
        'flow.status' => '',
        'flow.options' => '',
        'flow.data' => '',
        'operations.*.id' => '',
        'operations.*.name' => '',
        'operations.*.status' => '',
        'operations.*.user_id' => '',
        'operations.*.type' => '',
        'operations.*.flow_id' => '',
        'operations.*.created_at' => '',
        'operations.*.options' => '',
        'operations.*.updated_at' => '',
        'operations.*.deleted_at' => '',
        'operations.*.reject_id' => '',
        'operations.*.resolve_id' => '',
    ];

    public function addOperation($type, $operationId)
    {
        // dd('hi');
        $o = Operation::find($operationId);

        $x = 12;
        $y = 0;
        if ($type == 'reject') {
            $y = 14;
        }
        // code...
        $operation = $this->flow->operations()->create([
            'name' => 'New Operation',
            'status' => 'active',
            'user_id' => auth()->id(),
            'type' => 'Aura\\Base\\Operations\\Log',
            'options' => [
                'x' => $o['options']['x'] + $x,
                'y' => $o['options']['y'] + $y,
            ],
        ]);

        $this->connectOperation($type, $operationId, $operation->id);

        // Refresh the Component
        $this->dispatch('refreshComponent');
        $this->operations = $this->flow->fresh()->operations;
    }

    public function addOperationFlow()
    {
        // dd('hi');
        // Create a new operation
        $operation = $this->flow->operations()->create([
            'name' => 'New Operation',
            'status' => 'active',
            'user_id' => auth()->id(),
            'type' => 'Aura\\Base\\Operations\\Log',
            'options' => [
                'x' => 2 + 12,
                'y' => 2 + 0,
            ],
        ]);

        // Assign the new operation to the flow
        $this->flow->operation_id = $operation->id;
        $this->flow->save();

        // Refresh the flow's operations
        $this->operations = $this->flow->fresh()->operations;

        // Refresh the UI
        $this->dispatch('refreshComponent');
    }

    public function connectFlow($targetId)
    {
        // dd('hi');
        $this->flow->update([
            'operation_id' => $targetId,
        ]);

        $this->operations = $this->flow->fresh()->operations;
    }

    public function connectOperation($type, $operationId, $targetId)
    {
        // dd('hi');
        if (! $operationId) {
            return;
        }
        // if $targetId is null, then we are removing the connection
        if (! $targetId) {
            $operation = Operation::find($operationId);
            // dd($operation->resolve_id);
            //$operation['resolve_id'] = null;

            // unset resolve_id of the operation
            if ($type == 'resolve') {
                $operation->update(['resolve_id' => null]);
            } else {
                $operation->update(['reject_id' => null]);
            }

            $operation->name = 'Resolve should be deleted';
            $operation->save();
            // dd('removed', $operation->toArray());
            $this->operations = $this->flow->fresh()->operations;

            return;
        }

        // make sure, that the connection does not create a loop
        if ($this->hasConnectionLoop($operationId, $targetId, $type)) {
            // return an error or throw an exception
            return;
        }

        $operation = Operation::find($operationId);
        $operation->{$type.'_id'} = $targetId;
        $operation->save();

        // Refresh the Component
        $this->operations = $this->flow->fresh()->operations;
    }

    public function createOperation()
    {
        // dd('hi');
        $operation = $this->flow->operations()->create([
            'name' => 'New Operation',
            'status' => 'active',
            'user_id' => auth()->id(),
        ]);

        $this->operations->push($operation);
    }

    public function mount($model)
    {
        $this->flow = $model;
        $this->model = $model;

        $this->operations = $this->flow->operations;

        // catch event operationUpdated and refresh the component
        // $this->on('operationUpdated', function ($operation) {
        //     $this->operations = $this->flow->fresh()->operations;
        // });
    }

    public function refresh()
    {
        $this->operations = $this->flow->fresh()->operations;
        $this->dispatch('refreshComponent');
    }

    public function render()
    {
        return view('aura::livewire.create-flow');
    }

    public function saveOperation($o)
    {
        // dd('hi');
        // Save only the options of Operation
        $operation = Operation::find($o['id']);
        $operation->options = $o['options'];
        $operation->save();
    }

    public function selectOperation($operationId)
    {
        // dd('hi');
        $this->dispatch('openSlideOver', 'edit-operation', ['model' => $operationId]);
    }

    private function hasConnectionLoop($operationId, $targetId)
    {
        $currentOperation = Operation::find($operationId);
        $targetOperation = Operation::find($targetId);

        // if the target operation is the current operation, then we have a loop
        if ($currentOperation->id == $targetOperation->id) {
            return true;
        }

        // if the target operation has a connection to the current operation, then we have a loop
        if ($targetOperation->resolve_id == $currentOperation->id || $targetOperation->reject_id == $currentOperation->id) {
            return true;
        }

        // recursion: check the next operation in the chain
        if ($targetOperation->resolve_id) {
            return $this->hasConnectionLoop($operationId, $targetOperation->resolve_id);
        }

        if ($targetOperation->reject_id) {
            return $this->hasConnectionLoop($operationId, $targetOperation->reject_id);
        }

        // if we reach the end of the chain, there is no loop
        return false;
    }
}
```

## ./Livewire/Dashboard.php
```
<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('aura::livewire.dashboard')->layout('aura::components.layout.app');
    }
}
```

## ./Livewire/Modals.php
```
<?php

namespace Aura\Base\Livewire;

use Livewire\Component;
use Livewire\Mechanisms\ComponentRegistry;
use Ray\Ray;

class Modals extends Component
{
    public $activeModals = [];

    public $modals = [];

    protected $listeners = ['openModal', 'closeModal'];

    public function closeModal($id = null): void
    {
        if ($id) {
            unset($this->modals[$id]);
            $this->activeModals = array_values(array_filter($this->activeModals, function ($modalId) use ($id) {
                return $modalId !== $id;
            }));
        } else {
            $this->modals = [];
            $this->activeModals = [];
        }
    }

    public function mount()
    {
        // Initialization logic if needed
    }

    public function openModal($component, $arguments = [], $modalAttributes = []): void
    {
        $id = md5($component.serialize($arguments));

        $componentClass = app(ComponentRegistry::class)->getClass($component);

        $this->modals[$id] = [
            'name' => $component,
            'arguments' => $arguments,
            'modalAttributes' => array_merge([
                'persistent' => false,
                'modalClasses' => method_exists($componentClass, 'modalClasses') ? $componentClass::modalClasses() : 'max-w-4xl',
                'slideOver' => false,
            ], $modalAttributes),
        ];
        $this->activeModals[$id] = true;
    }

    public function render()
    {
        ray($this->modals)->blue(); // This will show the contents of $modals in Ray
        return view('aura::livewire.modals');
    }
}
```

## ./Livewire/Forms/ResourceForm.php
```
<?php

namespace Aura\Base\Livewire\Forms;

use Livewire\Form;

class ResourceForm extends Form
{
    public $fields = [];

    public function setFields($fields)
    {
        $this->fields = $fields;
    }
}
```

## ./Livewire/Settings.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Option;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\MediaFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Settings extends Component
{
    use InputFields;
    use MediaFields;

    public $form = [
        'fields' => [],
    ];

    public $model;

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'General',
                'slug' => 'tab-general',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Appearance',
                'slug' => 'panel-DZzV',
            ],
            [
                'name' => 'Logo',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'logo',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Logo Darkmode',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'logo-darkmode',
                'style' => [
                    'width' => '50',
                ],
            ],
            // [
            //     'name' => 'Timezone',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'timezone',
            // ],

            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Sidebar',
                'slug' => 'panel-theme-sidebar',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Size',
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'standard',
                        'value' => 'Standard',
                    ],
                    [
                        'key' => 'compact',
                        'value' => 'Compact',
                    ],
                ],
                'slug' => 'sidebar-size',
                'style' => [
                    'width' => '25',
                ],
            ],
            [
                'name' => 'Sidebar',
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'primary',
                        'value' => 'Primary',
                    ],
                    [
                        'key' => 'light',
                        'value' => 'Light',
                    ],
                    [
                        'key' => 'dark',
                        'value' => 'Dark',
                    ],
                ],
                'slug' => 'sidebar-type',
                'style' => [
                    'width' => '25',
                ],
            ],
            [
                'name' => 'Darkmode',
                'live' => true,
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'auto',
                        'value' => 'Auto',
                    ],
                    [
                        'key' => 'light',
                        'value' => 'Light',
                    ],
                    [
                        'key' => 'dark',
                        'value' => 'Dark',
                    ],
                ],
                'slug' => 'darkmode-type',
                'style' => [
                    'width' => '25',
                ],
            ],

            [
                'name' => 'Sidebar Darkmode',
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'primary',
                        'value' => 'Primary',
                    ],
                    [
                        'key' => 'light',
                        'value' => 'Light',
                    ],
                    [
                        'key' => 'dark',
                        'value' => 'Dark',
                    ],
                ],
                'slug' => 'sidebar-darkmode-type',
                'style' => [
                    'width' => '25',
                ],
                'conditional_logic' => function ($model, $form) {
                    if ($form && $form['fields'] && $form['fields']['darkmode-type']) {
                        return $form['fields']['darkmode-type'] == 'auto';
                    }
                },
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Theme',
                'slug' => 'panel-theme-primary',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'Primary Color Palette',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => [
                    'aura' => 'Aura',
                    'red' => 'Red',
                    'orange' => 'Orange',
                    'amber' => 'Amber',
                    'yellow' => 'Yellow',
                    'lime' => 'Lime',
                    'green' => 'Green',
                    'emerald' => 'Emerald',
                    'teal' => 'Teal',
                    'cyan' => 'Cyan',
                    'sky' => 'Sky',
                    'blue' => 'Blue',
                    'indigo' => 'Indigo',
                    'violet' => 'Violet',
                    'purple' => 'Purple',
                    'fuchsia' => 'Fuchsia',
                    'pink' => 'Pink',
                    'rose' => 'Rose',
                    'mountain-meadow' => 'Mountain Meadow',
                    'sandal' => 'Sandal',
                    'slate' => 'Slate',
                    'gray' => 'Gray',
                    'zinc' => 'Zinc',
                    'neutral' => 'Neutral',
                    'stone' => 'Stone',
                    'custom' => 'Custom',
                ],
                'slug' => 'color-palette',
                'live' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Group',
                'name' => 'Custom Colors',
                'slug' => 'tab-primary-colors-lightmode',
                'conditional_logic' => function ($model, $form) {
                    if ($form && $form['fields'] && $form['fields']['color-palette']) {
                        return $form['fields']['color-palette'] == 'custom';
                    }
                },
            ],
            [
                'name' => 'Primary 25',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-25',
            ],
            [
                'name' => 'Primary 50',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-50',
            ],
            [
                'name' => 'Primary 100',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-100',
            ],
            [
                'name' => 'Primary 200',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-200',
            ],
            [
                'name' => 'Primary 300',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-300',
            ],
            [
                'name' => 'Primary 400',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-400',
            ],
            [
                'name' => 'Primary 500',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-500',
            ],
            [
                'name' => 'Primary 600',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-600',
            ],
            [
                'name' => 'Primary 700',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-700',
            ],
            [
                'name' => 'Primary 800',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-800',
            ],
            [
                'name' => 'Primary 900',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-900',
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Theme',
                'slug' => 'panel-theme-gray',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'Gray Color Palette',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => [
                    'slate' => 'Slate',
                    'purple-slate' => 'Purple Slate',
                    'gray' => 'Gray',
                    'zinc' => 'Zinc',
                    'neutral' => 'Neutral',
                    'stone' => 'Stone',
                    'blue' => 'Blue',
                    'smaragd' => 'Smaragd',
                    'dark-slate' => 'Dark Slate',
                    'blackout' => 'Blackout',
                    'custom' => 'Custom',
                ],
                'slug' => 'gray-color-palette',
                'live' => true,
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Group',
                'name' => 'Custom Colors',
                'slug' => 'tab-gray-colors-custom-tab',
                'conditional_logic' => function ($model, $form) {
                    if ($form && $form['fields'] && $form['fields']['gray-color-palette']) {
                        return $form['fields']['gray-color-palette'] == 'custom';
                    }
                },
            ],

            [
                'name' => 'Gray 25',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-25',
            ],
            [
                'name' => 'Gray 50',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-50',
            ],
            [
                'name' => 'Gray 100',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-100',
            ],
            [
                'name' => 'Gray 200',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-200',
            ],
            [
                'name' => 'Gray 300',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-300',
            ],
            [
                'name' => 'Gray 400',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-400',
            ],
            [
                'name' => 'Gray 500',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-500',
            ],
            [
                'name' => 'Gray 600',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-600',
            ],
            [
                'name' => 'Gray 700',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-700',
            ],
            [
                'name' => 'Gray 800',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-800',
            ],
            [
                'name' => 'Gray 900',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-900',
            ],

        ];
    }

    public function getFieldsForViewProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function getFieldsProperty()
    {
        return $this->inputFields()->mapWithKeys(function ($field) {
            return [$field['slug'] => $this->form['fields'][$field['slug']] ?? null];
        });
    }

    public function mount()
    {
        abort_unless(config('aura.features.settings'), 404);

        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $valueString = [
            'darkmode-type' => config('aura.theme.darkmode-type'),
            'sidebar-type' => config('aura.theme.sidebar-type'),
            'color-palette' => config('aura.theme.color-palette'),
            'gray-color-palette' => config('aura.theme.gray-color-palette'),
            'sidebar-size' => config('aura.theme.sidebar-size'),
            'sidebar-darkmode-type' => config('aura.theme.sidebar-darkmode-type'),
        ];

        if (config('aura.teams')) {
            $this->model = Option::firstOrCreate([
                'name' => 'team.'.auth()->user()->current_team_id.'.settings',
            ], [
                'value' => $valueString,
            ]);
        } else {
            $this->model = Option::firstOrCreate([
                'name' => 'settings',
            ], [
                'value' => $valueString,
            ]);
        }

        if (is_string($this->model->value)) {
            $this->form['fields'] = json_decode($this->model->value, true);
            // set default values of fields if not set to null
            $this->form['fields'] = $this->inputFields()->mapWithKeys(function ($field) {
                return [$field['slug'] => $this->form['fields'][$field['slug']] ?? null];
            })->toArray();
        } else {
            $this->form['fields'] = $this->inputFields()->mapWithKeys(function ($field) {
                return [$field['slug'] => $this->model->value[$field['slug']] ?? ''];
            })->toArray();

        }
    }

    public function render()
    {
        return view('aura::livewire.settings')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        app('aura')::updateOption('settings', $this->form['fields']);

        Cache::clear();

        return $this->notify(__('Successfully updated'));
    }
}
```

## ./Livewire/PluginsPage.php
```
<?php

namespace Aura\Base\Livewire;

use Exception;
use Livewire\Component;
use Spatie\Packagist\PackagistClient;
use Spatie\Packagist\PackagistUrlGenerator;
use Symfony\Component\Process\Process;

class PluginsPage extends Component
{
    public $installedPackages = [];

    public $latestVersions = [];

    public $loading = [];

    public $output;

    // getInstalledPackages
    public function getInstalledPackages()
    {
        $composerJsonPath = base_path('composer.json');
        $composerJson = json_decode(file_get_contents($composerJsonPath), true);

        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

        $composerLockPackages = array_merge(
            $composerLock['packages'] ?? [],
            $composerLock['packages-dev'] ?? []
        );
        $lockPackages = [];
        foreach ($composerLockPackages as $package) {
            $packageName = $package['name'];
            $packageVersion = $package['version'];

            $lockPackages[$packageName] = [
                'version' => $packageVersion,
            ];

            // Use $packageName and $packageVersion as needed.
        }

        $dependencies = array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? []
        );

        $installedPackages = [];
        foreach ($dependencies as $package => $version) {
            $packageJsonPath = base_path().'/vendor/'.$package.'/composer.json';

            if (! file_exists($packageJsonPath)) {
                continue;
            }
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);

            if (str_starts_with($lockPackages[$package]['version'], 'v') || str_starts_with($lockPackages[$package]['version'], 'V')) {
                $version = substr($lockPackages[$package]['version'], 1);
            } else {
                $version = $lockPackages[$package]['version'];
            }

            $installedPackages[$package] = [
                'keywords' => $packageJson['keywords'] ?? [],
                'version' => $version ?? '',
            ];
        }

        return $installedPackages;
    }

    public function getPackageUpdates($name)
    {
        $this->loading[$name] = true;
        // refresh livewire component
        $this->dispatch('refresh');
        $client = new \GuzzleHttp\Client;
        $generator = new PackagistUrlGenerator;

        $packagist = new PackagistClient($client, $generator);

        $packageVersions = $packagist->getPackageMetadata($name)['packages'][$name];

        // get all keys from $packageVersions
        $versions = array_keys($packageVersions);
        // loop through all versions and if it starts with "dev-" remove it and if it starts with "v" remove the v
        foreach ($versions as $key => $version) {
            if (str_contains($version, 'dev') || str_contains($version, 'alpha') || str_contains($version, 'beta') || str_contains($version, 'BETA') || str_contains($version, 'rc') || str_contains($version, 'RC') || str_contains($version, 'x')) {
                unset($versions[$key]);
            } elseif (str_starts_with($version, 'v')) {
                $versions[$key] = substr($version, 1);
            } elseif (str_starts_with($version, 'V')) {
                $versions[$key] = substr($version, 1);
            }
        }

        // sort the versions and get the highest version 2.20 is higher than 2.9
        usort($versions, function ($a, $b) {
            return version_compare($a, $b);
        });

        $latestVersion = end($versions);

        $this->latestVersions[$name] = $latestVersion;
        $this->loading[$name] = false;
    }

    // mount
    public function mount()
    {
        $this->installedPackages = $this->getInstalledPackages();
    }

    public function render()
    {
        return view('aura::livewire.plugins-page')->layout('aura::components.layout.app');
    }

    public function runComposerUpdate()
    {
        // exec('composer update 2>&1', $output);
        exec('cd .. && /opt/homebrew/bin/php /usr/local/bin/composer update 2>&1', $output);

        // The output of the command will be stored in the $output array
        $this->output = implode("\n", $output);
    }

    public function updatePackage($name, $version)
    {
        $cmd = 'composer require '.$name.':'.$version.' --update-with-dependencies';

        $process = Process::fromShellCommandline($cmd);

        $processOutput = '';

        $captureOutput = function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        };

        $process->setTimeout(null)
            ->run($captureOutput);

        if ($process->getExitCode()) {
            $this->notify('Update failed.'.$cmd.' - '.$processOutput);

            $exception = new Exception($cmd.' - '.$processOutput);
            report($exception);

            throw $exception;
        }

        return $processOutput;
    }
}
```

## ./Livewire/Attachment/Index.php
```
<?php

namespace Aura\Base\Livewire\Attachment;

use Aura\Base\Livewire\Resource\Index as PostIndex;

class Index extends PostIndex
{
    public function mount($slug = 'Attachment')
    {
        // Call parent mount method
        parent::mount($slug);
    }

    public function render()
    {
        return view('aura::livewire.attachment.index')->layout('aura::components.layout.app');
    }
}
```

## ./Livewire/ImageUpload.php
```
<?php

namespace Aura\Base\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class ImageUpload extends Component
{
    use WithFileUploads;

    public $photos = [];

    public function remove($index)
    {
        array_splice($this->photos, $index, 1);
    }

    public function render()
    {
        return view('aura::livewire.image-upload');
    }

    public function save()
    {
        $this->validate([
            'photos.*' => 'image|max:10240',
        ]);

        foreach ($this->photos as $photo) {
            $photo->store('photos', 'public');
        }
        $this->photos = [];
        session()->flash('message', 'File Uploaded !');
    }

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'image|max:10240',
        ]);
    }
}
```

## ./Livewire/MediaManager.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Attachment;
use Livewire\Component;
use Livewire\Attributes\On;

class MediaManager extends Component
{
    public $field;

    public $fieldSlug;

    public $model;

    public $selected = [];

    public $modalAttributes;

    public $initialSelectionDone = false;

    public $rowIds = []; // Add this line

    // Listen for select Attachment
    protected $listeners = [
        'selectedRows' => 'selectAttachment',
        'tableMounted',
        'updateField' => 'updateField',
    ];


    public static function modalClasses(): string
    {
        return 'max-w-7xl';
    }

    public function mount($slug, $selected, $modalAttributes)
    {
        $this->selected = $selected;
        $this->fieldSlug = $slug;
        $this->modalAttributes = $modalAttributes;
        $this->field = app($this->model)->fieldBySlug($this->fieldSlug);
        $this->rowIds = Attachment::pluck('id')->toArray(); // Add this line to populate rowIds
    }

    public function render()
    {
        return view('aura::livewire.media-manager', [
            'rows' => Attachment::paginate(25), // Adjust the number as needed
        ]);
    }

    public function select()
    {
        // Emit update Field
        $this->dispatch('updateField', [
            'slug' => $this->fieldSlug,
            'value' => $this->selected,
        ]);

        $this->dispatch('media-manager-selected');

        // Close Modal
        $this->dispatch('closeModal');
    }

    #[On('selectedRows')]
    public function selectAttachment($ids)
    {
        if (!$this->initialSelectionDone) {
            ray('selectAttachment', $ids);
            $this->selected = $ids;
            $this->initialSelectionDone = true;
        }
    }

    #[On('tableMounted')]
    public function tableMounted()
    {
        if ($this->selected && !$this->initialSelectionDone) {
            $this->dispatch('selectedRows', $this->selected);
            $this->initialSelectionDone = true;
        }
    }

    public function updated($name, $value)
    {
        if ($name === 'selected') {
            $this->dispatch('selectedRows', $this->selected);
        }
    }

    #[On('updateField')]
    public function updateField($field)
    {
        if ($field['slug'] == $this->fieldSlug) {
        ray('updated', $this->selected);
            $this->selected = $field['value'];
            $this->dispatch('selectedRows', $this->selected);
        }
    }
}
```

## ./Livewire/TwoFactorAuthenticationForm.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\User;
use Aura\Base\Traits\ConfirmsPasswords;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Livewire\Component;

// This is heavily based on Laravel Jetstream's TwoFactorAuthenticationForm.php
// Thanks to Taylor Otwell and the Laravel Jetstream team!
// https://jetstream.laravel.com/2.x/introduction.html

class TwoFactorAuthenticationForm extends Component
{
    use ConfirmsPasswords;

    /**
     * The OTP code for confirming two factor authentication.
     *
     * @var string|null
     */
    public $code;

    /**
     * Indicates if the two factor authentication confirmation input and button are being displayed.
     *
     * @var bool
     */
    public $showingConfirmation = false;

    /**
     * Indicates if two factor authentication QR code is being displayed.
     *
     * @var bool
     */
    public $showingQrCode = false;

    /**
     * Indicates if two factor authentication recovery codes are being displayed.
     *
     * @var bool
     */
    public $showingRecoveryCodes = false;

    /**
     * Confirm two factor authentication for the user.
     *
     * @return void
     */
    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $confirm)
    {
        // if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
        //     $this->ensurePasswordIsConfirmed();
        // }
        $this->ensurePasswordIsConfirmed();

        $confirm($this->user, $this->code);

        $this->showingQrCode = false;
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = true;
    }

    /**
     * Disable two factor authentication for the user.
     *
     * @return void
     */
    public function disableTwoFactorAuthentication(DisableTwoFactorAuthentication $disable)
    {
        // if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
        //     $this->ensurePasswordIsConfirmed();
        // }
        $this->ensurePasswordIsConfirmed();

        $disable($this->user);

        $this->showingQrCode = false;
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = false;
    }

    /**
     * Enable two factor authentication for the user.
     *
     * @return void
     */
    public function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $enable)
    {
        // if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
        //     $this->ensurePasswordIsConfirmed();
        // }

        $this->ensurePasswordIsConfirmed();

        $enable($this->user);

        $this->showingQrCode = true;
        $this->showingConfirmation = true;

        // if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm')) {
        //     $this->showingConfirmation = true;
        // } else {
        //     $this->showingRecoveryCodes = true;
        // }
    }

    /**
     * Determine if two factor authentication is enabled.
     *
     * @return bool
     */
    public function getEnabledProperty()
    {
        return ! empty($this->user->two_factor_secret);
    }

    /**
     * Get the current user of the application.
     *
     * @return mixed
     */
    public function getUserProperty()
    {
        return auth()->user();
    }

    /**
     * Mount the component.
     *
     * @return void
     */
    public function mount()
    {
        if (is_null($this->user->two_factor_confirmed_at)) {
            app(DisableTwoFactorAuthentication::class)($this->user);
        }
    }

    /**
     * Generate new recovery codes for the user.
     *
     * @return void
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate)
    {
        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
            $this->ensurePasswordIsConfirmed();
        }

        $generate($this->user);

        $this->showingRecoveryCodes = true;
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('aura::profile.two-factor-authentication-form')->layout('aura::components.layout.app');
    }

    /**
     * Display the user's recovery codes.
     *
     * @return void
     */
    public function showRecoveryCodes()
    {
        // if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
        //     $this->ensurePasswordIsConfirmed();
        // }
        $this->ensurePasswordIsConfirmed();

        $this->showingRecoveryCodes = true;
    }
}
```

## ./Livewire/MediaUploader.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Attachment;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaUploader extends Component
{
    use WithFileUploads;

    public $button = false;

    public $disabled = false;

    public $field;

    public $for;

    public $media = [];

    public $model;

    public $namespace = Attachment::class;

    public $selected;

    public $table = true;

    public $upload = false;

    // listener selectedMediaUpdated
    protected $listeners = ['selectedMediaUpdated' => 'selectedMediaUpdated'];

    public function mount()
    {
        // ray('mount media uploader', $this->model, $this->field, $this->selected);
        $this->model = app($this->namespace);
    }

    public function render()
    {
        return view('aura::livewire.media-uploader');
    }

    public function selectedMediaUpdated($data)
    {
        if ($this->field && ($this->field['slug'] == $data['slug'])) {
            $this->selected = $data['value'];
        }
    }

    public function updatedMedia()
    {
        $this->validate([
            'media.*' => 'required|max:102400', // 100MB Max, for now
        ]);

        $attachments = [];

        foreach ($this->media as $key => $media) {
            $url = $media->store('media', 'public');

            $attachments[] = app(config('aura.resources.attachment'))::create([
                'url' => $url,
                'name' => $media->getClientOriginalName(),
                'title' => $media->getClientOriginalName(),
                'size' => $media->getSize(),
                'mime_type' => $media->getMimeType(),
            ]);

            // Unset the processed file
            unset($this->media[$key]);
        }

        if ($this->field) {
            // Emit update Field
            $this->dispatch('updateField', [
                'slug' => $this->field['slug'],
                // merge the new attachments with the old ones
                'value' => optional($this)->selected ? array_merge($this->selected, collect($attachments)->pluck('id')->toArray()) : collect($attachments)->pluck('id')->toArray(),
            ]);

            $this->selected = optional($this)->selected ? array_merge($this->selected, collect($attachments)->pluck('id')->toArray()) : collect($attachments)->pluck('id')->toArray();
        }

        $this->dispatch('refreshTable');
    }
}
```

## ./Livewire/InviteUser.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Mail\TeamInvitation;
use Aura\Base\Resources\Role;
use Aura\Base\Traits\InputFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class InviteUser extends Component
{
    use AuthorizesRequests;
    use InputFields;

    public $form = [
        'fields' => [
            'email' => '',
            'role' => '',
        ],
    ];

    public static function getFields()
    {
        return [
            [
                'name' => 'Email',
                'type' => 'Aura\\Base\\Fields\\Email',
                'placeholder' => 'email@example.com',
                'validation' => 'required|email',
                'slug' => 'email',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Role',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => 'required',
                'slug' => 'role',
                'options' => Role::get()->pluck('title', 'id')->toArray(),
                'style' => [
                    'width' => '50',
                ],
            ],
        ];
    }

    public function getFieldsProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function render()
    {
        return view('aura::livewire.user.invite-user');
    }

    public function rules()
    {
        $rules = Arr::dot([
            'form.fields' => $this->validationRules(),

        ]);

        $rules['form.fields.email'] = [
            'required', 'email',
            function ($attribute, $value, $fail) {
                $team = auth()->user()->currentTeam;

                if ($team->users()->where('email', $value)->exists()) {
                    $fail('User already exists.');
                }

                if ($team->teamInvitations()->whereMeta('email', $value)->exists()) {
                    $fail('User already invited.');
                }
            },
        ];

        return $rules;
    }

    public function save()
    {
        $this->validate();

        $team = auth()->user()->currentTeam;

        $this->authorize('invite-users', $team);

        $invitation = $team->teamInvitations()->create([
            'email' => $email = $this->form['fields']['email'],
            'role' => $this->form['fields']['role'],
        ]);

        Mail::to($email)->send(new TeamInvitation($invitation));

        $this->notify('Erfolgreich eingeladen.');

        $this->dispatch('closeModal');
        $this->dispatch('refreshTable');
    }
}
```

## ./Livewire/CreateResource.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Traits\FieldsOnComponent;
use Aura\Base\Traits\InputFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class CreateResource extends Component
{
    use FieldsOnComponent;
    use InputFields;

    public $form = [
        'fields' => [
            'name' => '',
        ],
    ];

    public function closemodal()
    {
        $this->dispatch('closeModal');
    }

    public static function modalClasses(): string
    {
        return 'max-w-xl';
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Name (Singular, e.g. Post)',
                'slug' => 'name',
                'instructions' => 'The name of the post type, shown in the admin panel.',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|alpha:ascii',
            ],
        ];
    }

    public function mount()
    {
        abort_if(app()->environment('production'), 403);

        abort_unless(auth()->user()->isSuperAdmin(), 403);
    }

    public function render()
    {
        return view('aura::livewire.create-resource');
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $name = $this->form['fields']['name'];
        $slug = str($name)->slug();

        $this->validate();

        Artisan::call('aura:resource', [
            'name' => $this->form['fields']['name'],
        ]);

        Artisan::call('cache:clear');

        $this->notify('Erfolgreich Erstellt.');

        $this->dispatch('closeModal');

        // redirect to route
        return redirect()->route('aura.resource.editor', ['slug' => $slug]);

        // Route::get('/resources/{slug}/editor', ResourceEditor::class)->name('resource.editor');
    }
}
```

## ./Livewire/Table/Traits/SwitchView.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

trait SwitchView
{
    public $currentView;

    public function mountSwitchView()
    {
        $userPreference = auth()->user()->getOption('table_view.'.$this->model()->getType());

        $this->currentView = $userPreference ?? $this->settings['default_view'];
    }

    public function switchView($view)
    {
        if (in_array($view, ['list', 'kanban', 'grid'])) {
            $this->currentView = $view;
            $this->saveViewPreference();
        }
    }

    protected function saveViewPreference()
    {
        auth()->user()->updateOption('table_view.'.$this->model()->getType(), $this->currentView);
    }
}
```

## ./Livewire/Table/Traits/BulkActions.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

/**
 * Trait for bulk actions in Livewire table component
 */
trait BulkActions
{
    public $bulkActionsView = 'aura::components.table.bulkActions';

    /**
     * Handle bulk action on the selected rows.
     */
    public function bulkAction(string $action)
    {
        $this->selectedRowsQuery->each(function ($item, $key) use ($action) {
            if (str_starts_with($action, 'callFlow.')) {
                $item->callFlow(explode('.', $action)[1]);
            } elseif (str_starts_with($action, 'multiple')) {
                $posts = $this->selectedRowsQuery->get();
                $response = $item->{$action}($posts);

                // dd($response);
            } elseif (method_exists($item, $action)) {
                $item->{$action}();
            }
        });

        // Clear the selected array
        $this->selected = [];

        $this->notify('Erfolgreich: '.$action);
    }

    public function bulkCollectionAction($action)
    {
        //$action = $this->model->getBulkActions()[$action];
        $ids = $this->selectedRowsQuery->pluck('id')->toArray();

        $response = $this->model->{$action}($ids);

        if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $response;
        }

        // reset selected rows
        $this->selected = [];

        $this->notify('Erfolgreich: '.$action);

        $this->dispatch('refreshTable');
    }

    /**
     * Get the available bulk actions.
     *
     * @return mixed
     */
    public function getBulkActionsProperty()
    {
        return $this->model->getBulkActions();
    }
}
```

## ./Livewire/Table/Traits/Filters.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;

/**
 * Trait for handling filters in Livewire Table component.
 */
trait Filters
{
    /**
     * An array of filters, with two keys: taxonomy and custom.
     *
     * @var array
     */
    // #[Reactive]
    public $filters = [
        'custom' => [],
    ];

    /**
     * The selected filter.
     *
     * @var mixed
     */
    public $selectedFilter;

    /**
     * A boolean value indicating whether the save filter modal is shown.
     *
     * @var bool
     */
    public $showSaveFilterModal = false;

    /**
     * Add a custom filter.
     *
     * @return void
     */
    public function addFilter()
    {
        $this->filters['custom'][] = [
            'name' => $this->fieldsForFilter->keys()->first(),
            'operator' => 'contains',
            'value' => null,
            'main_operator' => 'and',
        ];
    }

    public function clearFiltersCache()
    {
        auth()->user()->clearCachedOption($this->model->getType().'.filters.*');
        auth()->user()->currentTeam->clearCachedOption($this->model->getType().'.filters.*');
    }

    /**
     * Delete a filter.
     *
     * @param  mixed  $filter
     * @return void
     */
    public function deleteFilter($filterName)
    {
        // Retrieve the filter using the provided key
        $filter = $this->userFilters[$filterName] ?? null;

        if (! $filter) {
            throw new \InvalidArgumentException('Invalid filter name: '.$filterName);
        }

        switch ($filter['type']) {
            case 'user':
                auth()->user()->deleteOption($this->model->getType().'.filters.'.$filterName);

                break;
            case 'team':
                auth()->user()->currentTeam->deleteOption($this->model->getType().'.filters.'.$filterName);

                break;
            default:
                // Handle unexpected type value
                throw new \InvalidArgumentException('Invalid filter type: '.$filter['type']);
        }

        $this->notify('Success: Filter deleted!');
        $this->clearFiltersCache();
        $this->reset('filters');

        $filters = $this->userFilters;

        $this->reset('selectedFilter');

        // Refresh Component
        $this->dispatch('refreshTable');
    }

    #[Computed]
    public function fieldsForFilter()
    {
        return $this->fields->mapWithKeys(function ($field) {
            $fieldInstance = app($field['type']);

            return [
                $field['slug'] => [
                    'name' => $field['name'],
                    'type' => class_basename($field['type']),
                    'filterOptions' => $fieldInstance->filterOptions(),
                    'filterValues' => $fieldInstance->getFilterValues($this->model, $field),
                ],
            ];
        });
    }

    #[Computed]
    public function getFields()
    {
        return $this->fields->mapWithKeys(function ($field) {
            return [$field['slug'] => $field];
        });
    }

    // /**
    //  * Get the fields for filter .
    //  *
    //  * @return mixed
    //  */
    // #[Computed]
    // public function fieldsForFilter()
    // {
    //     return $this->fields->pluck('name', 'slug');
    // }

    /**
     * Remove a custom filter.
     *
     * @param  int  $index
     * @return void
     */
    public function removeCustomFilter($index)
    {
        unset($this->filters['custom'][$index]);
        $this->filters['custom'] = array_values($this->filters['custom']);
    }

    /**
     * Reset the filters.
     *
     * @return void
     */
    public function resetFilter()
    {
        $this->reset('filters');
    }

    /**
     * Save the selected filter.
     *
     * Validate the filter name is required, save the filter per user, and set the selected filter.
     */
    public function saveFilter()
    {
        $this->validate([
            'filter.name' => 'required',
            'filter.public' => 'required',
            'filter.global' => 'required',
            'filter.icon' => '',
        ]);

        $newFilter = array_merge($this->filters, $this->filter);
        $slug = Str::slug($this->filter['name']);

        // If the slug is empty (e.g., for numbers or special characters), generate a unique identifier
        if (empty($slug)) {
            $slug = 'filter_'.Str::random(10);
        }

        $newFilter['slug'] = $slug;

        if ($this->filters) {
            // Save for Team
            if ($this->filter['global']) {
                auth()->user()->currentTeam->updateOption($this->model->getType().'.filters.'.$slug, $newFilter);
            }
            // Save for User
            else {
                auth()->user()->updateOption($this->model->getType().'.filters.'.$slug, $newFilter);
            }
        }

        $this->selectedFilter = $slug;
        $this->notify('Filter saved successfully!');
        $this->showSaveFilterModal = false;
        $this->reset('filter');
        $this->clearFiltersCache();
    }


    public function updatedFiltersCustom($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) === 5 && $parts[4] === 'name') {
            $groupKey = $parts[1];
            $filterKey = $parts[3];
            // Reset the operator when the field changes
            $this->filters['custom'][$groupKey]['filters'][$filterKey]['operator'] = array_key_first($this->fieldsForFilter[$value]['filterOptions']);
            // Also reset the value
            $this->filters['custom'][$groupKey]['filters'][$filterKey]['value'] = null;
        }
    }

    /**
     * Update the selected filter.
     *
     * Get the filter from options in userFilters.
     *
     * @param  string  $filter
     */
    public function updatedSelectedFilter($filter)
    {
        ray('updatedSelectedFilter', $filter);
        $this->clearFiltersCache();

        // Reset filters first
        $this->reset('filters');

        if ($filter) {
            // Get the filter data
            $filterData = $this->userFilters[$filter];

            // Force a new array assignment to trigger reactivity
            $this->filters = [
                'custom' => array_values($filterData['custom'] ?? [])
            ];
        }

        // Force a rerender of the component
        $this->dispatch('refresh');
    }

    /**
     * Get the user filters .
     *
     * @return mixed
     */
    #[Computed]
    public function userFilters()
    {
        $userFilters = auth()->user()->getOption($this->model()->getType().'.filters.*') ?? collect();
        $teamFilters = collect();

        if (config('aura.teams')) {
            $teamFilters = optional(auth()->user()->currentTeam)->getOption($this->model()->getType().'.filters.*') ?? collect();
        }

        // Add 'type' => 'user' and ensure 'slug' exists for each user filter
        $userFilters = $userFilters->map(function ($filter, $key) {
            $filter['type'] = 'user';
            $filter['slug'] = $filter['slug'] ?? $key;

            return $filter;
        });

        // Add 'type' => 'team' and ensure 'slug' exists for each team filter
        $teamFilters = $teamFilters->map(function ($filter, $key) {
            $filter['type'] = 'team';
            $filter['slug'] = $filter['slug'] ?? $key;

            return $filter;
        });

        // Use concat to merge collections and convert to array
        return collect($userFilters)->merge($teamFilters)->keyBy('slug')->toArray();
    }

    public function addFilterGroup()
    {
        $this->filters['custom'][] = [
            'filters' => [
                $this->newFilter(),
            ],
        ];
    }

    public function addSubFilter($groupKey)
    {
        $this->filters['custom'][$groupKey]['filters'][] = $this->newFilter();
    }

    private function newFilter()
    {
        return [
            'name' => $this->fieldsForFilter->keys()->first(),
            'operator' => 'contains',
            'value' => null,
            'options' => [],
        ];
    }

    public function removeFilterGroup($groupKey)
    {
        unset($this->filters['custom'][$groupKey]);
        $this->filters['custom'] = array_values($this->filters['custom']);
    }

    public function removeFilter($groupKey, $filterKey)
    {
        unset($this->filters['custom'][$groupKey]['filters'][$filterKey]);
        $this->filters['custom'][$groupKey]['filters'] = array_values($this->filters['custom'][$groupKey]['filters']);

        if (empty($this->filters['custom'][$groupKey]['filters'])) {
            $this->removeFilterGroup($groupKey);
        }
    }
}
```

## ./Livewire/Table/Traits/Kanban.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Livewire\Attributes\On;

/**
 * Trait to handle sorting functionality.
 */
trait Kanban
{
    public $kanbanStatuses = [];

    public function mountKanban()
    {
        if ($this->currentView != 'kanban') {
            return;
        }

        $this->initializeKanbanStatuses();

        if (method_exists($this->model, 'kanbanPagination')) {
            $this->perPage = $this->model->kanbanPagination();
        }

    }

    public function reorderKanbanColumns($newOrder)
    {
        // Filter out empty values from $newOrder using Laravel's collection methods
        $newOrder = collect($newOrder)->filter()->values();

        $reorderedStatuses = collect();

        // Reorder based on $newOrder
        foreach ($newOrder as $key) {
            if (isset($this->kanbanStatuses[$key])) {
                $reorderedStatuses[$key] = $this->kanbanStatuses[$key];
            }
        }

        // Add any remaining statuses that weren't in $newOrder
        foreach ($this->kanbanStatuses as $key => $status) {
            if (! $reorderedStatuses->has($key)) {
                $reorderedStatuses[$key] = $status;
            }
        }

        $this->kanbanStatuses = $reorderedStatuses->toArray();

        $this->saveKanbanStatusesOrder();
    }

    public function reorderKanbanStatuses($statuses)
    {
        // Create a new collection from the ordered status keys
        $orderedStatuses = collect($statuses);

        // Create a new collection to store the reordered kanban statuses
        $reorderedKanbanStatuses = collect();

        // Iterate through the ordered status keys and rebuild the kanban statuses array
        foreach ($orderedStatuses as $statusKey) {
            if (isset($this->kanbanStatuses[$statusKey])) {
                $reorderedKanbanStatuses[$statusKey] = $this->kanbanStatuses[$statusKey];
            }
        }

        // Update the kanban statuses with the new order
        $this->kanbanStatuses = $reorderedKanbanStatuses->toArray();

        $this->saveKanbanStatusesOrder();
    }

    public function updatedKanbanStatuses()
    {
        $this->saveKanbanStatusesOrder();
    }

    protected function applyKanbanQuery($query)
    {

        if ($this->model->kanbanQuery($query)) {
            return $this->model->kanbanQuery($query);
        }

        return $query;
    }

    protected function initializeKanbanStatuses()
    {
        $statuses = $this->model->fieldBySlug('status')['options'];
        $this->kanbanStatuses = collect($statuses)->mapWithKeys(function ($status) {
            return [$status['key'] => [
                'value' => $status['value'],
                'color' => $status['color'],
                'visible' => true,
            ]];
        })->toArray();

        // Load user preferences if they exist
        $userPreferences = auth()->user()->getOption('kanban_statuses.'.$this->model()->getType());
        if ($userPreferences) {
            $this->kanbanStatuses = $userPreferences;
        }
    }

    protected function saveKanbanStatusesOrder()
    {
        auth()->user()->updateOption('kanban_statuses.'.$this->model()->getType(), $this->kanbanStatuses);
    }
}
```

## ./Livewire/Table/Traits/Settings.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

trait Settings
{
    public function defaultSettings()
    {
        return [
            'per_page' => 10,
            'columns' => $this->model->getTableHeaders(),
            'filters' => [],
            'search' => '',
            'sort' => [
                'column' => 'id',
                'direction' => 'desc',
            ],
            'settings' => true,
            'sort_columns' => true,
            'columns_global_key' => false,
            'columns_user_key' => 'columns.'.$this->model->getType(),
            'search' => true,
            'filters' => true,
            'global_filters' => true,
            'title' => true,
            'selectable' => true,
            'default_view' => $this->model->defaultTableView(),
            // 'current_view' => $this->model->defaultTableView(),
            'header_before' => true,
            'header_after' => true,
            'table_before' => true,
            'table_after' => true,
            'create' => true,
            'actions' => true,
            'bulk_actions' => true,
            'header' => true,
            'edit_in_modal' => false,
            'create_in_modal' => false,
            'views' => [
                'table' => 'aura::components.table.index',
                'list' => $this->model->tableView(),
                'grid' => $this->model->tableGridView(),
                'kanban' => $this->model->tableKanbanView(),
                'filter' => 'aura::components.table.filter',
                'header' => 'aura::components.table.header',
                'row' => $this->model->rowView(),
                'bulkActions' => 'aura::components.table.bulkActions',
                'table-header' => 'aura::components.table.table-header',
                'table_footer' => 'aura::components.table.footer',
            ],
        ];
    }

    public function mountSettings()
    {
        $this->settings = $this->array_merge_recursive_distinct($this->defaultSettings(), $this->settings ?: []);
    }

    protected function array_merge_recursive_distinct(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
```

## ./Livewire/Table/Traits/Sorting.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Str;

/**
 * Trait to handle sorting functionality.
 */
trait Sorting
{
    /**
     * Collection of sort field and direction.
     *
     * @var array
     */
    public $sorts = [];

    /**
     * Sort by the specified field.
     *
     * @param  string  $field
     * @return void
     */
    public function sortBy($field)
    {
        $this->sorts = collect($this->sorts)->filter(function ($value, $key) use ($field) {
            return $key === $field;
        })->toArray();

        if (! isset($this->sorts[$field])) {
            $this->sorts[$field] = 'asc';

            return;
        }

        if ($this->sorts[$field] === 'asc') {
            $this->sorts[$field] = 'desc';

            return;
        }

        unset($this->sorts[$field]);
    }

    /**
     * Apply sorting to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySorting($query)
    {
        if ($this->sorts) {
            $query->getQuery()->orders = null;
        }

        foreach ($this->sorts as $field => $direction) {
            if ($this->model->isTaxonomyField($field)) {
                $taxonomy = Str::singular(ucfirst($field));

                $query->withFirstTaxonomy($taxonomy, $this->model->getMorphClass())
                    ->orderByRaw('CASE WHEN first_taxonomy IS NULL THEN 1 WHEN first_taxonomy = "" THEN 1 ELSE 0 END')
                    ->orderBy('first_taxonomy', $direction)
                    ->orderBy('id', 'desc');

                return $query;
            }

            if ($this->model->usesMeta() && $this->model->isMetaField($field)) {
                $query->leftJoin('meta', function ($join) use ($field) {
                    $join->on('posts.id', '=', 'meta.metable_id')
                        ->where('meta.metable_type', '=', $this->model->getMorphClass())
                        ->where('meta.key', '=', "$field");
                })
                    ->select('posts.*')
                    ->when($this->model->isNumberField($field), function ($query) use ($direction) {
                        $query->orderByRaw('CAST(meta.value AS DECIMAL(10,2)) '.$direction);
                    })
                    ->when(! $this->model->isNumberField($field), function ($query) use ($direction) {
                        $query->orderByRaw('CAST(meta.value AS CHAR) '.$direction);
                    })
                    ->orderBy('id', 'desc');

                return $query;
            } else {
                $query->orderBy($field, $direction);

                return $query;
            }
        }

        $query->getQuery()->orders = null;

        // default sort
        $query->orderBy($this->model->getTable().'.'.$this->model->defaultTableSort(), $this->model->defaultTableSortDirection());

        return $query;
    }
}
```

## ./Livewire/Table/Traits/Search.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Facades\DB;

trait Search
{
    public $search;

    public function applySearch($query)
    {
        if ($this->search) {

            ray($this->search);

            // Check if there is a search method in the model (modifySearch()), and call it.
            if (method_exists($this->model, 'modifySearch')) {
                $query = $this->model->modifySearch($query, $this->search);

                return $query;
            }

            $searchableFields = $this->model->getSearchableFields()->pluck('slug');
            $metaFields = $searchableFields->filter(fn ($field) => $this->model->isMetaField($field));

            $query->where(function ($query) use ($searchableFields, $metaFields) {
                // Search in regular fields
                foreach ($searchableFields as $field) {
                    if (! $metaFields->contains($field)) {
                        $query->orWhere($this->model->getTable().'.'.$field, 'like', '%'.$this->search.'%');
                    }
                }

                // Search in meta fields
                if ($metaFields->count() > 0) {
                    $metaTable = $this->model->getMetaTable();
                    $query->orWhereExists(function ($query) use ($metaTable, $metaFields) {
                        $query->select(DB::raw(1))
                            ->from($metaTable)
                            ->whereColumn($this->model->getTable().'.id', $metaTable.'.'.$this->model->getMetaForeignKey())
                            ->whereIn($metaTable.'.key', $metaFields)
                            ->where($metaTable.'.value', 'like', '%'.$this->search.'%');
                    });
                }
            });

        }

        return $query;
    }
}
```

## ./Livewire/Table/Traits/QueryFilters.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

trait QueryFilters
{
    protected function applyCustomFilter(Builder $query): Builder
{
    if (empty($this->filters['custom'])) {
        return $query;
    }

    $groups = $this->filters['custom'];

    // Start by building the conditions from the first group
    $condition = function ($query) use ($groups) {
        $this->applyFilterGroup($query, $groups[0]);
    };

    for ($i = 1; $i < count($groups); $i++) {
        $group = $groups[$i];
        $operator = $group['operator'] ?? 'and';

        // Create a new condition that wraps the previous condition and combines it with the current group
        $previousCondition = $condition;

        $condition = function ($query) use ($previousCondition, $group, $operator) {
            $query->where(function ($q) use ($previousCondition, $group, $operator) {
                // Wrap previous conditions
                $q->where(function ($subQ) use ($previousCondition) {
                    $previousCondition($subQ);
                });

                // Combine with current group using its operator
                $method = $operator === 'and' ? 'where' : 'orWhere';

                $q->$method(function ($subQ) use ($group) {
                    $this->applyFilterGroup($subQ, $group);
                });
            });
        };
    }

    // Apply the accumulated condition to the main query
    $query->where(function ($q) use ($condition) {
        $condition($q);
    });

    return $query;
}


    protected function applyFilterGroup(Builder $query, array $group): void
    {
        foreach ($group['filters'] as $filterIndex => $filter) {
            if ($this->isValidFilter($filter)) {
                if ($filterIndex > 0) {
                    $groupOperator = $filter['main_operator'] ?? 'and';
                    $this->applyFilter($query, $filter, $groupOperator);
                } else {
                    $this->applyFilter($query, $filter, 'and');
                }
            }
        }
    }

    protected function applyFilter(Builder $query, array $filter, string $groupOperator): void
    {
        $method = $groupOperator === 'or' ? 'orWhere' : 'where';

        $query->$method(function ($subQuery) use ($filter) {
            $this->applyFilterBasedOnType($subQuery, $filter);
        });
    }

    protected function applyFilterBasedOnType(Builder $query, array $filter): void
    {
        if ($this->model->usesCustomTable() || $this->model->isTableField($filter['name'])) {
            $this->applyTableFieldFilter($query, $filter);
        } else {
            $this->applyMetaFieldFilter($query, $filter);
        }
    }

    protected function applyIsEmptyMetaFilter(Builder $query, array $filter): void
    {
        $query->where(function ($query) use ($filter) {
            $query->whereDoesntHave('meta', function (Builder $query) use ($filter) {
                $query->where('key', '=', $filter['name']);
            })
                ->orWhereHas('meta', function (Builder $query) use ($filter) {
                    $query->where('key', '=', $filter['name'])
                        ->where(function ($query) {
                            $query->where('value', '=', '')
                                ->orWhereNull('value');
                        });
                });
        });
    }

    protected function applyIsNotEmptyMetaFilter(Builder $query, array $filter): void
    {
        $query->whereHas('meta', function (Builder $query) use ($filter) {
            $query->where('key', '=', $filter['name'])
                ->where(function ($query) {
                    $query->where('value', '!=', '')
                        ->whereNotNull('value');
                });
        });
    }

    protected function applyMetaFieldFilter(Builder $query, array $filter): Builder
    {
        switch ($filter['operator']) {
            case 'is_empty':
                $this->applyIsEmptyMetaFilter($query, $filter);
                break;
            case 'is_not_empty':
                $this->applyIsNotEmptyMetaFilter($query, $filter);
                break;
            default:
                $this->applyStandardMetaFilter($query, $filter);
        }

        return $query;
    }

    protected function applyOperatorCondition(Builder $query, array $filter): void
    {
        switch ($filter['operator']) {
            case 'contains':
                $query->where('value', 'like', '%'.$filter['value'].'%');
                break;
            case 'does_not_contain':
                $query->where('value', 'not like', '%'.$filter['value'].'%');
                break;
            case 'starts_with':
                $query->where('value', 'like', $filter['value'].'%');
                break;
            case 'ends_with':
                $query->where('value', 'like', '%'.$filter['value']);
                break;
            case 'is':
            case 'equals':
                $query->where('value', '=', $filter['value']);
                break;
            case 'is_not':
            case 'not_equals':
                $query->where('value', '!=', $filter['value']);
                break;
            case 'greater_than':
                $query->where('value', '>', $filter['value']);
                break;
            case 'less_than':
                $query->where('value', '<', $filter['value']);
                break;
            case 'greater_than_or_equal':
                $query->where('value', '>=', $filter['value']);
                break;
            case 'less_than_or_equal':
                $query->where('value', '<=', $filter['value']);
                break;
            case 'in':
                $query->whereIn('value', explode(',', $filter['value']));
                break;
            case 'not_in':
                $query->whereNotIn('value', explode(',', $filter['value']));
                break;
            case 'like':
                $query->where('value', 'like', $filter['value']);
                break;
            case 'not_like':
                $query->where('value', 'not like', $filter['value']);
                break;
            case 'regex':
                $query->where('value', 'regexp', $filter['value']);
                break;
            case 'not_regex':
                $query->where('value', 'not regexp', $filter['value']);
                break;
            case 'date_is':
                $query->whereDate('value', '=', $filter['value']);
                break;
            case 'date_is_not':
                $query->whereDate('value', '!=', $filter['value']);
                break;
            case 'date_before':
                $query->whereDate('value', '<', $filter['value']);
                break;
            case 'date_after':
                $query->whereDate('value', '>', $filter['value']);
                break;
            case 'date_on_or_before':
                $query->whereDate('value', '<=', $filter['value']);
                break;
            case 'date_on_or_after':
                $query->whereDate('value', '>=', $filter['value']);
                break;
            case 'date_is_empty':
                $query->where(function ($query) {
                    $query->whereNull('value')
                        ->orWhere('value', '=', '');
                });
                break;
            case 'date_is_not_empty':
                $query->whereNotNull('value')
                    ->where('value', '!=', '');
                break;
        }
    }

protected function applyStandardMetaFilter(Builder $query, array $filter): void
{
    if (isset($filter['options']) && isset($filter['options']['resource_type'])) {

        // dd($filter);

        $resourceType = $filter['options']['resource_type'];
        $values = (array) $filter['value'];

        $slug = $filter['name'];
        $relatedType = get_class($query->getModel());

        // dd($resourceType, $values, $filter, $relatedType);
        if ($filter['operator'] === 'contains') {
            $query->whereIn('id', function ($subQuery) use ($resourceType, $values, $slug, $relatedType) {
                $subQuery->select('related_id')
                    ->from('post_relations')
                    ->where('post_relations.related_type', $relatedType)
                    ->where('post_relations.resource_type', $resourceType)
                    ->where('post_relations.slug', $slug)
                    ->whereIn('post_relations.resource_id', $values);
            });
        } elseif ($filter['operator'] === 'does_not_contain') {
            $query->whereNotIn('id', function ($subQuery) use ($resourceType, $values, $slug, $relatedType) {
                $subQuery->select('related_id')
                    ->from('post_relations')
                    ->where('post_relations.related_type', $relatedType)
                    ->where('post_relations.resource_type', $resourceType)
                    ->where('post_relations.slug', $slug)
                    ->whereIn('post_relations.resource_id', $values);
            });
        }

        return;
    }

    $query->whereHas('meta', function (Builder $query) use ($filter) {
        $query->where('key', '=', $filter['name']);
        $this->applyOperatorCondition($query, $filter);
    });
}

    protected function applyTableFieldFilter(Builder $query, array $filter): Builder
    {
        if (is_array($filter['value'])) {
            $filter['value'] = implode(',', $filter['value']);
        }
        switch ($filter['operator']) {
            case 'contains':
                $query->where($filter['name'], 'like', '%'.$filter['value'].'%');
                break;
            case 'does_not_contain':
                $query->where($filter['name'], 'not like', '%'.$filter['value'].'%');
                break;
            case 'starts_with':
                $query->where($filter['name'], 'like', $filter['value'].'%');
                break;
            case 'ends_with':
                $query->where($filter['name'], 'like', '%'.$filter['value']);
                break;
            case 'is':
            case 'equals':
                $query->where($filter['name'], '=', $filter['value']);
                break;
            case 'is_not':
            case 'not_equals':
                $query->where($filter['name'], '!=', $filter['value']);
                break;
            case 'greater_than':
                $query->where($filter['name'], '>', $filter['value']);
                break;
            case 'less_than':
                $query->where($filter['name'], '<', $filter['value']);
                break;
            case 'greater_than_or_equal':
                $query->where($filter['name'], '>=', $filter['value']);
                break;
            case 'less_than_or_equal':
                $query->where($filter['name'], '<=', $filter['value']);
                break;
            case 'in':
                $query->whereIn($filter['name'], explode(',', $filter['value']));
                break;
            case 'not_in':
                $query->whereNotIn($filter['name'], explode(',', $filter['value']));
                break;
            case 'like':
                $query->where($filter['name'], 'like', $filter['value']);
                break;
            case 'not_like':
                $query->where($filter['name'], 'not like', $filter['value']);
                break;
            case 'regex':
                $query->where($filter['name'], 'regexp', $filter['value']);
                break;
            case 'not_regex':
                $query->where($filter['name'], 'not regexp', $filter['value']);
                break;
            case 'is_empty':
                $query->where(function ($query) use ($filter) {
                    $query->whereNull($filter['name'])
                        ->orWhere($filter['name'], '=', '');
                });
                break;
            case 'is_not_empty':
                $query->whereNotNull($filter['name'])
                    ->where($filter['name'], '!=', '');
                break;
            case 'date_is':
                $query->whereDate($filter['name'], '=', $filter['value']);
                break;
            case 'date_is_not':
                $query->whereDate($filter['name'], '!=', $filter['value']);
                break;
            case 'date_before':
                $query->whereDate($filter['name'], '<', $filter['value']);
                break;
            case 'date_after':
                $query->whereDate($filter['name'], '>', $filter['value']);
                break;
            case 'date_on_or_before':
                $query->whereDate($filter['name'], '<=', $filter['value']);
                break;
            case 'date_on_or_after':
                $query->whereDate($filter['name'], '>=', $filter['value']);
                break;
            case 'date_is_empty':
                $query->where(function ($query) use ($filter) {
                    $query->whereNull($filter['name'])
                        ->orWhere($filter['name'], '=', '');
                });
                break;
            case 'date_is_not_empty':
                $query->whereNotNull($filter['name'])
                    ->where($filter['name'], '!=', '');
                break;
        }

        return $query;
    }

    protected function isValidFilter(array $filter): bool
    {
        return !empty($filter['name']) &&
               (!empty($filter['value']) || in_array($filter['operator'], ['is_empty', 'is_not_empty']));
    }
}
```

## ./Livewire/Table/Traits/Select.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

/**
 * Trait for bulk actions in Livewire table component
 */
trait Select
{
    /**
     * Indicates if all rows should be selected
     *
     * @var bool
     */
    public $selectAll = false;

    /**
     * Array of selected row IDs
     *
     * @var array
     */
    public $selected = [];

    /**
     * Indicates if all rows in the current page should be selected
     *
     * @var bool
     */
    public $selectPage = false;

    /**
     * Gets a query for selected rows
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getSelectedRowsQueryProperty()
    {
        return (clone $this->query())
            ->unless($this->selectAll, fn ($query) => $query->whereKey($this->selected));
    }

    // /**
    //  * Handles selecting all or page rows
    //  *
    //  * @return void
    //  */
    // public function renderingWithBulkActions()
    // {
    //     if ($this->selectAll) {
    //         $this->selectPageRows();
    //     }
    // }

    /**
     * Selects all rows
     *
     * @return void
     */
    public function selectAll()
    {
        $this->selectAll = true;
    }

    /**
     * Selects all rows in the current page
     *
     * @return void
     */
    public function selectPageRows()
    {
        $this->selected = collect($this->selected)
            ->merge($this->rows()->pluck('id')->map(fn ($id) => (string) $id))
            ->unique()
            ->values()
            ->all();
    }

    // when page is updated, reset selectPage
    public function updatedPage()
    {
        $this->selectPage = false;
    }

    /**
     * Handles updates to selected rows
     *
     * @return void
     */
    public function updatedSelected()
    {
        $this->selectAll = false;
        $this->selectPage = false;
    }

    /**
     * Handles updates to selecting all rows in the current page
     *
     * @param  bool  $value
     * @return void
     */
    public function updatedSelectPage($value)
    {
        if ($value) {
            return $this->selectPageRows();
        }

        $this->selectAll = false;
        $this->selected = [];
    }
}
```

## ./Livewire/Table/Traits/CachedRows.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

trait CachedRows
{
    /**
     * Use cache flag
     *
     * @var bool
     */
    protected $useCache = false;

    /**
     * Enable the use of cache
     *
     * @return void
     */
    public function useCachedRows()
    {
        $this->useCache = true;
    }

    /**
     * Store result in cache and return result
     *
     * @return mixed
     */
    protected function cache(callable $callback)
    {
        $cacheKey = $this->id;

        if ($this->useCache && cache()->has($cacheKey)) {
            return cache()->get($cacheKey);
        }

        $result = $callback();

        cache()->put($cacheKey, $result);

        return $result;
    }
}
```

## ./Livewire/Table/Traits/PerPagePagination.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Livewire\WithPagination;

/**
 * Trait to handle per-page pagination.
 */
trait PerPagePagination
{
    use WithPagination;

    /**
     * Number of items to be displayed per page.
     *
     * @var int
     */
    public $perPage = 10;

    /**
     * Paginate the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function applyPagination($query)
    {
        return $query->paginate($this->perPage, ['*']);
    }

    /**
     * Mount the pagination data from the session.
     *
     * @return void
     */
    public function mountPerPagePagination()
    {
        $this->perPage = session()->has('perPage') ? session()->get('perPage') : $this->model()->defaultPerPage();
    }

    /**
     * Update the per-page pagination data in the session.
     *
     * @param  int  $value
     * @return void
     */
    public function updatedPerPage($value)
    {
        session()->put('perPage', $value);
    }
}
```

## ./Livewire/Table/Table.php
```
<?php

namespace Aura\Base\Livewire\Table;

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Traits\BulkActions;
use Aura\Base\Livewire\Table\Traits\Filters;
use Aura\Base\Livewire\Table\Traits\Kanban;
use Aura\Base\Livewire\Table\Traits\PerPagePagination;
use Aura\Base\Livewire\Table\Traits\QueryFilters;
use Aura\Base\Livewire\Table\Traits\Search;
use Aura\Base\Livewire\Table\Traits\Select;
use Aura\Base\Livewire\Table\Traits\Settings;
use Aura\Base\Livewire\Table\Traits\Sorting;
use Aura\Base\Livewire\Table\Traits\SwitchView;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Class Table
 */
class Table extends Component
{
    use BulkActions;
    use Filters;
    use Kanban;
    use PerPagePagination;
    use QueryFilters;
    use Search;
    use Select;
    use Settings;
    use Sorting;
    use SwitchView;

    public $bulkActionsView = 'aura::components.table.bulkActions';

    /**
     * List of table columns.
     *
     * @var array
     */
    public $columns = [];

    /**
     * Indicates if the Create Component should be in a Modal.
     *
     * @var bool
     */
    public $createInModal = false;

    public $disabled;

    /**
     * Indicates if the Edit Component should be in a Modal.
     *
     * @var bool
     */
    public $editInModal = false;

    /**
     * The field of the parent.
     *
     * @var string
     */
    public $field;

    /**
     * The name of the filter in the modal.
     *
     * @var string
     */
    public $filter = [
        'name' => '',
        'public' => false,
        'global' => false,
    ];

    public $form;

    /**
     * The last clicked row.
     *
     * @var mixed
     */
    public $lastClickedRow;

    public $loaded = false;

    public $model;

    /**
     * The parent of the table.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $parent;

    public $query;

    public $resource;

    /**
     * Validation rules.
     *
     * @var array
     */
    public $rules = [
        'filter.name' => 'required',
        'filter.global' => '',
        'filter.public' => '',
        'filters.custom.*.name' => 'required',
        'filters.custom.*.operator' => 'required',
        'filters.custom.*.value' => 'required',
    ];

    /**
     * The settings of the table.
     *
     * @var array
     */
    public $settings;

    /**
     * List of events listened to by the component.
     *
     * @var array
     */
    protected $listeners = [
        'refreshTable' => '$refresh',
        'selectedRows' => '$refresh',
        'selectRowsRange' => 'selectRowsRange',
        'refreshTableSelected' => 'refreshTableSelected',
        'selectFieldRows',
    ];

    protected $queryString = ['selectedFilter'];

    public function action($data)
    {
        // return redirect to post view
        if ($data['action'] == 'view') {
            return redirect()->route('aura.'.$this->model()->getSlug().'.view', ['id' => $data['id']]);
        }
        // edit
        if ($data['action'] == 'edit') {
            return redirect()->route('aura.'.$this->model()->getSlug().'.edit', ['id' => $data['id']]);
        }

        // if custom
        // dd($data);

        if (method_exists($this->model, $data['action'])) {
            return $this->model()->find($data['id'])->{$data['action']}();
        }
    }

    public function allTableRows()
    {
        return $this->query()->pluck('id')->all();
    }

    public function boot() {}

    /**
     * Get the create link.
     *
     * @return string
     */
    #[Computed]
    public function createLink()
    {
        if ($this->model()->createUrl()) {
            return $this->model()->createUrl();
        }

        if ($this->parent) {
            return route('aura.'.$this->model()->getSlug().'.create', [
                'for' => $this->parent->getType(),
                'id' => $this->parent->id,
            ]);
        }

        return route('aura.'.$this->model()->getSlug().'.create');
    }

    /**
     * Get the input fields.
     *
     * @return mixed
     */
    #[Computed]
    public function fields()
    {
        return $this->model()->inputFields();
    }

    public function getAllTableRows()
    {
        return $this->query()->pluck('id')->all();
    }

    public function getParentModel()
    {
        return $this->parent;
    }

    public function getRows()
    {
        return $this->rows();
    }

    /**
     * Get the table headers.
     *
     * @return mixed
     */
    #[Computed]
    public function headers()
    {
        $headers = $this->settings['columns'];

        if ($this->settings['sort_columns'] && $this->settings['columns_global_key']) {
            $option = Aura::getOption($this->settings['columns_global_key']);

            return empty($option) ? $headers->toArray() : $option;
        }

        if ($this->settings['sort_columns'] && $this->settings['columns_user_key'] && $sort = auth()->user()->getOption($this->settings['columns_user_key'])) {

            $headers = collect($headers)->sortBy(function ($value, $key) use ($sort) {
                return array_search($key, array_keys($sort));
            })->toArray();
        }

        // ray('headers', $sort);

        return $headers;
    }

    public function loadTable()
    {
        $this->loaded = true;
    }

    #[Computed]
    public function model()
    {
        // ray('hier', $this->model);

        return $this->model;
    }

    /**
     * Get the model columns.
     *
     * @return mixed
     */
    #[Computed]
    public function modelColumns()
    {
        $columns = collect($this->model()->getColumns());

        if ($sort = auth()->user()->getOption('columns_sort.'.$this->model()->getType())) {
            $columns = $columns->sortBy(function ($value, $key) use ($sort) {
                return array_search($key, $sort);
            });
        }

        return $columns;
    }

    public function mount()
    {
        // if ($this->parentModel) {
        //     // dd($this->parentModel);
        // }

        $this->dispatch('tableMounted');

        if ($this->selectedFilter) {
            if (array_key_exists($this->selectedFilter, $this->userFilters)) {
                $this->filters = $this->userFilters[$this->selectedFilter];
            }
        }

        if (empty($this->columns)) {
            if (auth()->user()->getOptionColumns($this->model()->getType())) {
                $this->columns = auth()->user()->getOptionColumns($this->model()->getType());
            } else {
                $this->columns = $this->model()->getDefaultColumns();
            }
        }
    }

    public function openBulkActionModal($action, $data)
    {
        $this->dispatch('openModal', $data['modal'], [
            'action' => $action,
            'selected' => $this->selectedRowsQuery->pluck('id'),
            'model' => get_class($this->model),
        ]);
    }

    public function refreshRows()
    {
        unset($this->rowsQuery);
        unset($this->rows);
    }

    public function refreshTableSelected()
    {
        $this->selected = [];
    }

    /**
     * Render the component view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        ray('render', $this->search, count($this->rows()), $this->rows()->toArray());
        return view($this->model->tableComponentView(), [
            'parent' => $this->parent,
            'rows' => $this->rows(),
            'rowIds' => $this->rowIds,
        ]);
    }

    /**
     * Reorder the table columns.
     *
     * @param  $slugs  array The new column order.
     * @return void
     */
    public function reorder($slugs)
    {
        if ($this->settings['columns_global_key']) {
            $orderedSort = array_merge(array_flip($slugs), $this->headers());

            return Aura::updateOption($this->settings['columns_global_key'], $orderedSort);
        }

        // Save the columns for the current user.
        $headers = $this->columns;

        if ($headers instanceof \Illuminate\Support\Collection) {
            $headers = $headers->toArray();
        }

        $orderedSort = [];

        foreach ($slugs as $slug) {
            if (array_key_exists($slug, $headers)) {
                $orderedSort[$slug] = $headers[$slug];
            }
        }

        auth()->user()->updateOption($this->settings['columns_user_key'], $orderedSort);
    }

    #[Computed]
    public function rowIds()
    {
        $rowIds = $this->rows()->pluck('id')->toArray();

        $this->dispatch('rowIdsUpdated', $rowIds);

        return $rowIds;
    }

    public function selectFieldRows($value, $slug)
    {
        if ($slug == $this->field['slug']) {

            $this->selected = $value;
        }
    }

    /**
     * Select a single row in the table.
     *
     * @param  $id  int The id of the row to select.
     * @return void
     */
    public function selectRow($id)
    {
        $this->selected = $id;
        $this->lastClickedRow = $id;
    }

    public function updateCardStatus($cardId, $newStatus)
    {
        $card = $this->model->find($cardId);
        if ($card) {
            $card->status = $newStatus;
            $card->save();
            $this->notify('Card status updated successfully');
        } else {
            $this->notify('Card not found', 'error');
        }
    }

    /**
     * Update the columns in the table.
     *
     * @param  $columns  array The new columns.
     * @return void
     */
    public function updatedColumns($columns)
    {
        // Save the columns for the current user.
        if ($this->columns) {
            //ray('Save the columns for the current user', $this->columns);
            auth()->user()->updateOption('columns.'.$this->model()->getType(), $this->columns);
        }
    }

    /**
     * Update the selected rows in the table.
     *
     * @return void
     */
    public function updatedSelected()
    {
        // ray('table updatedSelected', $this->selected);
        // return;

        $this->selectAll = false;
        $this->selectPage = false;

        // Only allow the max number of selected rows.
        if (optional($this->field)['max'] && count($this->selected) > $this->field['max']) {
            $this->selected = array_slice($this->selected, 0, $this->field['max']);

            $this->dispatch('selectedRows', $this->selected);
            $this->notify('You can only select '.$this->field['max'].' items.', 'error');
        } else {
            $this->dispatch('selectedRows', $this->selected);
        }
    }

    protected function query()
    {
        $query = $this->model()->query()
            ->orderBy($this->model()->getTable().'.id', 'desc');

        if (method_exists($this->model, 'indexQuery')) {
            $query = $this->model->indexQuery($query, $this);
        }

        if ($this->field && method_exists(app($this->field['type']), 'queryFor')) {
            $query = app($this->field['type'])->queryFor($query, $this);
        }

        // If query is set, use it
        if ($this->query && is_string($this->query)) {
            try {
                $query = app('dynamicFunctions')::call($this->query);
            } catch (\Exception $e) {
                // Handle the exception
            }
        }

        // Kanban Query
        if ($this->currentView == 'kanban') {
            $query = $this->applyKanbanQuery($query);
        }

        // when model is instance Resource, eager load meta
        if ($this->model->usesMeta()) {
            $query = $query->with(['meta']);
        }

        return $query;
    }

    /**
     * Get the rows for the table.
     *
     * @return mixed
     */
    protected function rows()
    {
        $query = $this->query();

        if ($this->filters) {
            $query = $this->applyCustomFilter($query);
        }

        // Search
        $query = $this->applySearch($query);

        $query = $this->applySorting($query);

        $query = $query->paginate($this->perPage);

        return $query;
    }
}
```

## ./Livewire/Taxonomy/Index.php
```
<?php

namespace Aura\Base\Livewire\Taxonomy;

use Aura\Base\Facades\Aura;
use Livewire\Component;

class Index extends Component
{
    public $slug;

    public $taxonomy;

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->taxonomy = Aura::findTaxonomyBySlug($slug);

        abort_if(! $this->taxonomy, 404, 'Taxonomy not found.');
    }

    public function render()
    {
        return view('aura::livewire.taxonomy.index')->layout('aura::components.layout.app');
    }
}
```

## ./Livewire/Taxonomy/Create.php
```
<?php

namespace Aura\Base\Livewire\Taxonomy;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\InteractsWithFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use LivewireUI\Modal\ModalComponent;

class Create extends ModalComponent
{
    use AuthorizesRequests;
    use InteractsWithFields;

    public $inModal = false;

    public $model;

    public $resource;

    public $slug;

    public function mount($slug, $id = null)
    {
        $this->slug = $slug;
        $this->model = Aura::findTaxonomyBySlug($slug);

        // Authorize if the User can create
        $this->authorize('create', $this->model);

        // Array instead of Eloquent Model
        $this->resource = $this->model->toArray();
    }

    public function render()
    {
        return view('aura::livewire.taxonomy.create');
    }

    public function rules()
    {
        return Arr::dot([
            'resource.terms' => '',
            'form.fields' => $this->model->validationRules(),
        ]);
    }

    public function save()
    {
        $this->validate();

        // Set Fields
        $this->form['fields']['taxonomy'] = $this->slug;

        // dd($this->resource, $this->model);

        $model = $this->model->create($this->form['fields']);

        // dd('hier', $model);

        $this->closeModal();

        $this->notify('Successfully created.');
    }
}
```

## ./Livewire/Taxonomy/View.php
```
<?php

namespace Aura\Base\Livewire\Taxonomy;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\HasActions;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class View extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;
    use RepeaterFields;

    public $inModal = false;

    public $model;

    public $resource;

    public $slug;

    public $tax;

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public function getField($slug)
    {
        return $this->form['fields'][$slug];
    }

    public function mount($slug, $id)
    {
        $this->slug = $slug;

        $this->model = Aura::findTaxonomyBySlug($slug)->find($id);

        // Authorize
        $this->authorize('view', $this->model);

        // Array instead of Eloquent Model
        $this->resource = $this->model->attributesToArray();

        $this->resource['terms'] = $this->model->terms;
        $this->resource['terms']['tag'] = $this->resource['terms']['tag'] ?? null;
        $this->resource['terms']['category'] = $this->resource['terms']['category'] ?? null;
    }

    public function render()
    {
        // $view = "aura.{$this->slug}.view";

        // // if aura::aura.{$post->type}.view is set, use that view
        // if (view()->exists($view)) {
        //     return view($view)->layout('aura::components.layout.app');
        // }

        // if (view()->exists("aura::" . $view)) {
        //     return view("aura::" . $view)->layout('aura::components.layout.app');
        // }

        return view('aura::livewire.resource.view')->layout('aura::components.layout.app');
    }
}
```

## ./Livewire/Taxonomy/Edit.php
```
<?php

namespace Aura\Base\Livewire\Taxonomy;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\HasActions;
use Aura\Base\Traits\InteractsWithFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Livewire\Component;

class Edit extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;

    public $inModal = false;

    public $slug;

    public $taxonomy;

    public function getActionsProperty()
    {
        return $this->model->getActions();
    }

    public function mount($slug, $id)
    {
        $this->slug = $slug;
        $this->model = Aura::findTaxonomyBySlug($slug)->find($id);

        // Authorize

        // Array instead of Eloquent Model
        $this->resource = $this->model->toArray();

        // dd($this->resource);
        $this->form['fields'] = $this->model->toArray();
    }

    public function render()
    {
        return view('aura::livewire.taxonomy.edit')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return Arr::dot([
            'resource.terms' => '',
            'form.fields' => $this->model->validationRules(),
        ]);
    }

    public function save()
    {
        $this->validate();

        // Set Fields
        $this->form['fields']['taxonomy'] = $this->slug;

        $this->model->update($this->form['fields']);

        $this->notify(__('Successfully updated'));
    }
}
```

## ./Livewire/UserSettings.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Models\Option;
use Aura\Base\Traits\InputFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Livewire\Component;

class UserSettings extends Component
{
    use InputFields;

    public $form = [
        'fields' => [],
    ];

    public $model;

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'General',
                'label' => 'General',
                'slug' => 'tab-general',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Panel',
                'label' => 'Panel',
                'slug' => 'panel-DZzV',
            ],
            [
                'label' => 'Title',
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'title',
                'style' => [
                    'width' => '100',
                ],
            ],

        ];
    }

    public function getFieldsProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function mount()
    {
        $this->model = Option::firstOrCreate([
            'name' => 'user-settings',
        ], [
            'value' => [],
        ]);

        $this->form['fields'] = json_decode($this->model->value, true);
    }

    public function render()
    {
        return view('aura::livewire.user-settings');
    }

    public function reorderMedia($slug, $ids)
    {
        $ids = collect($ids)->map(function ($id) {
            return Str::after($id, '_file_');
        })->toArray();

        $this->updateField([
            'slug' => $slug,
            'value' => $ids,
        ]);
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        // Save Fields as user-settings in Option table
        $option = 'user-settings';
        $value = json_encode($this->form['fields']);
        $o = Option::updateOrCreate(['name' => $option], ['value' => $value]);

        // $this->validate();

        return $this->notify(__('Successfully updated'));

        // dd('hier')

        // $this->resource->save();

        // Artisan::call('make:resource', [
        //     'name' => $this->form['fields']['name'],
        // ]);

        // return $this->notify('Created successfully.');
    }

    // Select Attachment
    public function updateField($data)
    {
        $this->form['fields'][$data['slug']] = $data['value'];

        // dd($this->form['fields'][$data['slug']], $data['value']);
        // dd($this->resource);
        $this->save();
    }
}
```

## ./Livewire/ChooseTemplate.php
```
<?php

namespace Aura\Base\Livewire;

use LivewireUI\Modal\ModalComponent;

class ChooseTemplate extends ModalComponent
{
    public function render()
    {
        return view('aura::livewire.choose-template');
    }
}
```

## ./Livewire/Notifications.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Traits\InputFields;
use Livewire\Component;

class Notifications extends Component
{
    use InputFields;

    public $form = [
        'fields' => [],
    ];

    public $model;

    public $open = false;

    public function activate($params)
    {
        $this->open = true;
    }

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Unread',
                'slug' => 'tab-unread',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\View',
                'name' => 'Unread',
                'slug' => 'view-unread',
                'view' => 'aura::livewire.notifications-unread',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Read',
                'slug' => 'tab-read',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\View',
                'name' => 'read',
                'slug' => 'view-unread',
                'view' => 'aura::livewire.notifications-read',
            ],
        ];
    }

    public function getFieldsForViewProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function getFieldsProperty()
    {
        return $this->inputFields()->mapWithKeys(function ($field) {
            return [$field['slug'] => $this->form['fields'][$field['slug']] ?? null];
        });
    }

    public function getNotificationsProperty()
    {
        if (! auth()->check()) {
            return [];
        }

        return auth()->user()->readNotifications;
    }

    public function getUnreadNotificationsProperty()
    {
        if (! auth()->check()) {
            return [];
        }

        return auth()->user()->unreadNotifications;
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('aura::livewire.notifications');
    }
}
```

## ./Livewire/ResourceEditor.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\HasActions;
use Aura\Base\Traits\SaveFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Livewire\Component;

class ResourceEditor extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use SaveFields;

    public $fields = [];

    public $fieldsArray = [];

    public $globalTabs = [];

    public $hasGlobalTabs = false;

    public $model;

    public $newFields = [];

    public $reservedWords = ['id', 'type'];

    public $resource = [];

    public $slug;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'finishedSavingFields' => '$refresh',
        'savedField' => 'updateFields',
        'saveField',
        'deleteField',
        'saveNewField',
    ];

    public function addConditionalLogicRule($key, $group)
    {
        $this->fields[$key]['conditional_logic'][$group][] = ['param' => '', 'operator' => '=', 'value' => ''];
    }

    public function addConditionalLogicRuleGroup($key)
    {
        // code...
        $this->fields[$key]['conditional_logic'][] = [
            ['param' => '', 'operator' => '=', 'value' => ''],
        ];
    }

    public function addField($id, $slug, $type, $children, $model)
    {
        $this->dispatch('openSlideOver', component: 'edit-field', parameters: ['create' => true, 'type' => $type, 'id' => $id, 'slug' => $slug, 'children' => $children, 'model' => $model]);
    }

    public function addNewTab()
    {
        $fields = collect($this->fieldsArray);

        // check if collection has an item with type = "Aura\Base\Fields\Tab" and global = true
        $hasGlobalTabs = $fields->where('type', 'Aura\Base\Fields\Tab')->where('global', true)->count();
        $globalTab = [
            'type' => 'Aura\Base\Fields\Tab',
            'name' => 'Tab',
            'label' => 'Tab',
            'slug' => 'tab-'.Str::random(4),
            'global' => true,
        ];
        // if no global tabs, add one to the beginning of the collection
        if ($hasGlobalTabs == 0) {
            $fields->prepend($globalTab);
        }
        // else add it to the end of the collection
        else {
            $fields->push($globalTab);
        }

        $this->hasGlobalTabs = true;
        $this->updateGlobalTabs();

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        // $this->dispatch('refreshComponent');

        $this->dispatch('openSlideOver', component: 'edit-field', parameters: ['fieldSlug' => $globalTab['slug'], 'slug' => $this->slug, 'field' => $globalTab]);

        $this->dispatch('finishedSavingFields');
        $this->dispatch('refreshComponent');
    }

    public function addTemplateFields($slug)
    {
        $template = Aura::findTemplateBySlug($slug);

        $newFields = $template->getFields();

        // go through each field and add a random string to the slug
        foreach ($newFields as $key => $field) {
            $newFields[$key]['slug'] = $field['slug'].'_'.Str::random(4);
        }

        // check if newfields has a global tab
        $hasGlobalTab = collect($newFields)->where('type', 'Aura\Base\Fields\Tab')->where('global', true)->count();

        if ($hasGlobalTab > 0) {
            $this->hasGlobalTabs = true;
            $this->updateGlobalTabs();
        }

        $fields = collect($this->fieldsArray);

        // get index of the field
        $index = 0;

        // duplicate field in at index of the field + 1
        $fields->splice($index + 1, 0, $newFields);

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->dispatch('finishedSavingFields');
        $this->dispatch('refreshComponent');
    }

    public function checkAuthorization()
    {
        if (config('aura.features.resource_editor') == false) {
            abort(404);
        }

        if ($this->model->isVendorResource()) {
            abort(403, 'Only App resources can be edited.');
        }
    }

    public function countChildren($item)
    {
        $count = 0;

        if (isset($item['fields'])) {
            foreach ($item['fields'] as $child) {
                $count++;
                $count += $this->countChildren($child);
            }
        }

        return $count;
    }

    public function delete()
    {
        $a = new \ReflectionClass($this->model::class);

        // Delete file
        unlink($a->getFileName());

        $this->notify('Successfully deleted: '.$this->model->name);

        return redirect()->route('aura.dashboard');
    }

    public function deleteField($data)
    {
        $fields = collect($this->fieldsArray);

        $field = $fields->where('slug', $data['slug'])->first();

        $fields = $fields->reject(function ($item) use ($data) {
            return $item['slug'] == $data['slug'];
        });

        $this->fieldsArray = $fields->toArray();

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->dispatch('finishedSavingFields');
        $this->dispatch('refreshComponent');
    }

    public function duplicateField($id, $slug, $model)
    {
        $fields = collect($this->fieldsArray);

        $field = $fields->where('slug', $slug)->first();

        $field['slug'] = $field['slug'].'_'.Str::random(4);
        $field['name'] = $field['name'].' Copy';

        // get index of the field
        $index = $fields->search(function ($item) use ($slug) {
            return $item['slug'] == $slug;
        });

        // duplicate field in at index of the field + 1
        $fields->splice($index + 1, 0, [$field]);

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->dispatch('openSlideOver', component: 'edit-field', parameters: ['fieldSlug' => $field['slug'], 'slug' => $this->slug, 'field' => $field, 'model' => $model]);

        $this->dispatch('finishedSavingFields');

    }

    public function generateMigration()
    {
        // We need to set
        // public static $customTable = true;
        // in the resource class
        // and set public static ?string $slug = 'company';

        $resourceClass = new \ReflectionClass($this->model::class);
        $filePath = $resourceClass->getFileName();

        if (file_exists($filePath)) {
            $file = file_get_contents($filePath);

            // Add or update $customTable
            if (strpos($file, 'public static $customTable') === false) {
                $file = preg_replace(
                    '/(class\s+'.$resourceClass->getShortName().'\s+extends\s+\S+\s*{)/i',
                    "$1\n    public static \$customTable = true;",
                    $file
                );
            } else {
                $file = preg_replace(
                    '/public\s+static\s+\$customTable\s*=\s*(?:true|false);/i',
                    'public static $customTable = true;',
                    $file
                );
            }

            // Add or update $slug
            $tableName = Str::lower($this->model->getPluralName());
            if (strpos($file, 'protected $table') === false) {
                $file = preg_replace(
                    '/(class\s+'.$resourceClass->getShortName().'\s+extends\s+\S+\s*{)/i',
                    "$1\n    protected \$table = '$tableName';",
                    $file
                );
            } else {
                $file = preg_replace(
                    '/protected\s+\$table\s*=\s*[\'"].*?[\'"]\s*;/i',
                    "protected \$table = '$tableName';",
                    $file
                );
            }

            file_put_contents($filePath, $file);
        }

        Artisan::call('aura:create-resource-migration', [
            'resource' => $this->model::class,
        ]);

        // $this->notify('Successfully generated migration for: '.$this->model->name);

        $this->dispatch('close-modal');
    }

    public function getActionsProperty()
    {
        return [
            'delete' => [
                'label' => 'Delete',
                'icon-view' => 'aura::components.actions.trash',
                'class' => 'hover:text-red-700 text-red-500 font-bold',
                'confirm' => true,
                'confirm-title' => 'Delete Resource?',
                'confirm-content' => 'Are you sure you want to delete this Resource?',
                'confirm-button' => 'Delete',
                'confirm-button-class' => 'ml-3 bg-red-600 hover:bg-red-700',
            ],
            'generateMigration' => [
                'label' => 'Generate Migration',
                'class' => 'hover:text-primary-700 text-primary-500 font-bold',
                'confirm' => true,
                'icon' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 20C14 21.1046 13.1046 22 12 22C10.8954 22 10 21.1046 10 20M14 20C14 18.8954 13.1046 18 12 18M14 20H21M10 20C10 18.8954 10.8954 18 12 18M10 20H3M12 18V14M21 5C21 6.65685 16.9706 8 12 8C7.02944 8 3 6.65685 3 5M21 5C21 3.34315 16.9706 2 12 2C7.02944 2 3 3.34315 3 5M21 5V11C21 12.66 17 14 12 14M3 5V11C3 12.66 7 14 12 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'confirm-title' => 'Generate Resource Migration',
                'confirm-content' => 'Are you sure you want to generate a Migration? Make sure to have a look at the migration file before running it. You have to use the CustomTable trait in your resource class. Link to the documentation: <a href="https://eminiarts.com/docs/aura/resources#custom-tables">Custom Tables</a>',
                'confirm-button' => 'Generate',
            ],
        ];

    }

    public function getMappedFieldsProperty()
    {
        ray('getMappedFieldsProperty', $this->newFields)->blue();

        if ($this->newFields) {
            $this->updateGlobalTabs();

            return $this->newFields;
        }

        return $this->model->getFieldsForEdit();
    }

    public function insertTemplateFields($id, $slug, $type)
    {
        $template = Aura::findTemplateBySlug($type);
        $newFields = $template->getFields();

        // go through each field and add a random string to the slug
        foreach ($newFields as $key => $field) {
            $newFields[$key]['slug'] = $field['slug'].'_'.Str::random(4);
        }

        $fields = collect($this->fieldsArray);

        // get index of the field
        $index = $fields->search(function ($item) use ($slug) {
            return $item['slug'] == $slug;
        });

        // duplicate field in at index of the field + 1
        $fields->splice($index + 1, 0, $newFields);

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->dispatch('finishedSavingFields');
        $this->dispatch('refreshComponent');
    }

    public function mount($slug)
    {
        $this->slug = $slug;

        $this->model = Aura::findResourceBySlug($slug);

        $this->checkAuthorization();

        // Check if fields have closures
        if ($this->model->fieldsHaveClosures($this->model->getFields())) {
            abort(403, 'Your fields have closures. You can not use the Resource Builder with Closures.');
        }

        $this->fieldsArray = $this->model->getFieldsWithIds()->toArray();

        if (count($this->mappedFields) > 0 && $this->mappedFields[0]['type'] == "Aura\Base\Fields\Tab" && array_key_exists('global', $this->mappedFields[0]) && $this->mappedFields[0]['global']) {
            $this->hasGlobalTabs = true;

            // Global Tabs
            collect($this->mappedFields)->each(function ($field) {
                if ($field['type'] == "Aura\Base\Fields\Tab" && $field['global']) {
                    $this->globalTabs[] = [
                        'slug' => $field['slug'],
                        'name' => $field['name'],
                    ];
                }
            });
        }

        $this->resource = [
            'type' => $this->model->getType(),
            'slug' => $this->model->getSlug(),
            'icon' => $this->model->getIcon(),
            'group' => $this->model->getGroup(),
            'dropdown' => $this->model->getDropdown(),
            'sort' => $this->model->getSort(),
        ];
    }

    public function openSidebar($fieldSlug, $slug)
    {
        // get field with fieldSlug from this fieldsarray
        $field = collect($this->fieldsArray)->where('slug', $fieldSlug)->first();

        $this->dispatch('openSlideOver', component: 'edit-field', parameters: ['fieldSlug' => $fieldSlug, 'slug' => $slug, 'field' => $field]);
    }

    // Add this method to handle the refresh
    public function refreshResourceEditor()
    {
        $this->fieldsArray = $this->model->getFieldsWithIds()->toArray();
        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);
    }

    public function removeConditionalLogicRule($key, $groupKey, $ruleKey)
    {
        unset($this->fields[$key]['conditional_logic'][$groupKey][$ruleKey]);

        if (count($this->fields[$key]['conditional_logic'][$groupKey]) == 0) {
            unset($this->fields[$key]['conditional_logic'][$groupKey]);
        }
    }

    public function render()
    {
        return view('aura::livewire.resource-editor')->layout('aura::components.layout.app');
    }

    public function reorder($ids)
    {
        $this->validate();

        $ids = collect($ids)->map(function ($id) {
            return (int) Str::after($id, 'field_') - 1;
        });

        $fields = array_values($this->fieldsArray);

        $fields = $ids->map(function ($id) use ($fields) {
            return $fields[$id];
        })->toArray();

        $this->fieldsArray = $fields;

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->saveFields($this->fieldsArray);

        // Remove the dispatch('refreshComponent') call
        $this->dispatch('refreshComponent');

        $this->dispatch('finishedSavingFields');
    }

    public function rules()
    {
        return [
            'resource.type' => 'required|regex:/^[a-zA-Z]+$/',
            'resource.slug' => 'required|regex:/^[a-zA-Z][a-zA-Z0-9_-]*$/|not_regex:/^\d+$/',
            'resource.icon' => "required|not_regex:/'/",
            'resource.group' => '',
            'resource.dropdown' => '',
            'resource.sort' => '',
            'fields.*.name' => '',
            'fields.*.slug' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (collect($this->fields)->pluck('slug')->duplicates()->values()->contains($value)) {
                        $fail('The '.$attribute.' can not be used twice.');
                    }

                    // check if slug is a reserved word with "in_array"
                    if (in_array($value, $this->reservedWords)) {
                        $fail('The '.$attribute.' can not be a reserved word.');
                    }
                },
            ],
            'fields.*.type' => '',
            'fields.*.key' => '',
            'fields.*.validation' => '',
            'fields.*.wrapper' => '',
            'fields.*.conditional_logic' => '',
        ];
    }

    public function save()
    {
        $this->validate();
        if (count($this->fields) == 0) {
            $this->fields = $this->fieldsArray;
        }
        // $this->model->save();

        $fields = collect($this->fieldsArray);

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->saveProps($this->resource);
    }

    public function saveField($data)
    {
        $fields = collect($this->fieldsArray);

        // get index of the field with the slug $data['slug']
        $index = $fields->search(function ($item) use ($data) {
            return $item['slug'] == $data['slug'];
        });

        if ($index === false) {
            return;
        }

        $this->fieldsArray[$index] = $data['value'];

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        // emit new fields
        $this->dispatch('newFields', $this->fieldsArray);

        $this->dispatch('refreshComponent');

        $this->dispatch('finishedSavingFields');
    }

    public function saveNewField($field, $index, $slug)
    {
        ray('saveNewField', $field, $index, $slug)->blue();

        // Find the index of the item with slug of $slug in $this->fieldsArray
        $parentIndex = collect($this->fieldsArray)->search(function ($item) use ($slug) {
            return $item['slug'] === $slug;
        });
        $newFieldIndex = (int) $parentIndex + (int) $index + 1;

        ray('fieldsarray before', $this->fieldsArray)->blue();

        array_splice($this->fieldsArray, $newFieldIndex, 0, [$field]);

        ray('fieldsarray after', $this->fieldsArray)->blue();

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->saveFields($this->fieldsArray);

        ray('new fields', $this->newFields)->blue();

        // emit new fields
        $this->dispatch('newFields', $this->fieldsArray);
        $this->dispatch('refreshComponent');
        $this->dispatch('finishedSavingFields');

        // Add this line to trigger a re-render
        $this->dispatch('refreshResourceEditor');
    }

    public function sendField($slug)
    {
        // get field with fieldSlug from this fieldsarray
        return $field = collect($this->fieldsArray)->where('slug', $slug)->first();
    }

    public function singleAction($action)
    {
        $this->{$action}();

        $this->notify('Successfully ran: '.$action);
    }

    public function updateFields($fields)
    {
        $this->newFields = $this->model->mapToGroupedFields($fields);
    }

    // when newFields updated
    public function updateGlobalTabs()
    {
        if ($this->hasGlobalTabs) {
            $this->globalTabs = [];

            // Global Tabs
            collect($this->newFields)->each(function ($field) {
                if ($field['type'] == "Aura\Base\Fields\Tab" && $field['global']) {
                    $this->globalTabs[] = [
                        'slug' => $field['slug'],
                        'name' => $field['name'],
                    ];
                }
            });
        }
    }

    public function updatingFields($value)
    {
        // Make Sure Name is always a Slug
        foreach ($this->fields as $key => $field) {
            if (! optional($field)['slug']) {
                $this->fields[$key]['slug'] = Str::slug($field['name']);
            } else {
                $this->fields[$key]['slug'] = Str::slug($field['slug']);
            }
        }
    }
}
```

## ./Livewire/Profile.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\User;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\MediaFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Profile extends Component
{
    use InputFields;
    use MediaFields;

    public $confirmingUserDeletion = false;

    public $form = [
        'fields' => [],
    ];

    public $model;

    // protected $validationAttributes = [
    //     'form.fields.signatur' => 'signatur',
    // ];

    // protected function validationAttributes()
    // {
    //     return [
    //         'form.fields.signatur' => __('Signature'),
    //     ];
    // }

    /**
     * The user's current password.
     *
     * @var string
     */
    public $password = '';

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public function checkAuthorization()
    {
        if (config('aura.features.profile') == false) {
            abort(403, 'User profile is turned off.');
        }
    }

    /**
     * Confirm that the user would like to delete their account.
     *
     * @return void
     */
    public function confirmUserDeletion()
    {
        $this->confirmingUserDeletion = true;
    }

    /**
     * Delete the current user.
     */
    public function deleteUser(Request $request)
    {
        $this->validate(['password' => ['required', 'current_password']]);

        $user = User::find(auth()->id());

        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        Auth::logout();

        return Redirect::to('/');
    }

    public function getFields()
    {
        // dd($this->user->fieldsForView());
        return $this->user->getProfileFields();
    }

    public function getFieldsForViewProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function getFieldsProperty()
    {
        return $this->inputFields()->mapWithKeys(function ($field) {
            return [$field['slug'] => $this->form['fields'][$field['slug']] ?? null];
        });
    }

    public function getUserProperty()
    {
        return User::find(auth()->id());
    }

    public function logoutOtherBrowserSessions()
    {
        if (request()->hasSession() && Schema::hasTable('sessions')) {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', Auth::user()->getAuthIdentifier())
                ->where('id', '!=', request()->session()->getId())
                ->delete();
        }
    }

    public function mount()
    {
        $this->checkAuthorization();

        $this->model = auth()->user();

        $this->form = $this->model->attributesToArray();

        // dd($this->form['fields'], $this->model);
    }

    public function render()
    {
        return view('aura::livewire.user.profile')->layout('aura::components.layout.app');
    }

    public function reorderMedia($slug, $ids)
    {
        $ids = collect($ids)->map(function ($id) {
            return Str::after($id, '_file_');
        })->toArray();

        $this->updateField([
            'slug' => $slug,
            'value' => $ids,
        ]);
    }

    public function rules()
    {
        return $this->resourceFieldValidationRules();
    }

    public function save()
    {
        // dd($this->form['fields']);
        // $this->validate();

        $validatedData = $this->validate();
        // dd($validatedData['form']['fields'], $this->form);

        // dd('hier');

        // if $this->form['fields']['current_password'] and  is set, save password
        if (optional($this->form['fields'])['current_password'] && optional($this->form['fields'])['password']) {

            $this->model->update([
                'password' => bcrypt($this->form['fields']['password']),
            ]);

            // unset password fields
            unset($this->form['fields']['current_password']);
            unset($this->form['fields']['password']);
            unset($this->form['fields']['password_confirmation']);

            unset($validatedData['form']['fields']['current_password']);
            unset($validatedData['form']['fields']['password']);
            unset($validatedData['form']['fields']['password_confirmation']);

            // Logout other devices

            $this->logoutOtherBrowserSessions();

        }
        if (empty(optional($this->form['fields'])['password'])) {
            unset($this->form['fields']['current_password']);
            unset($this->form['fields']['password']);
            unset($this->form['fields']['password_confirmation']);
        }

        // dd('here2', $this->form['fields']);
        // dd('here 3', $this->form, $validatedData['form']['fields']);
        $this->model->update($validatedData['form']['fields']);

        // dd('here3');
        // dd($this->form['fields'], $this->rules(), $this->model);
        return $this->notify(__('Successfully updated'));
    }

    public function updateField($data)
    {
        // dd($data);
        $this->form['fields'][$data['slug']] = $data['value'];
        // $this->save();

        $this->dispatch('selectedMediaUpdated', [
            'slug' => $data['slug'],
            'value' => $data['value'],
        ]);
    }
}
```

## ./Livewire/BookmarkPage.php
```
<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class BookmarkPage extends Component
{
    public $bookmarks;

    public $site;

    public function getIsBookmarkedProperty()
    {
        if (! auth()->check()) {
            return false;
        }

        $bookmarks = auth()->user()->getOptionBookmarks();
        $bookmarkUrls = array_column($bookmarks, 'url');
        $key = array_search($this->site['url'], $bookmarkUrls);

        if ($key !== false) {
            return false;
        } else {
            return true;
        }
    }

    public function mount($site)
    {
        $this->site = $site;

        if (auth()->check()) {
            $this->bookmarks = auth()->user()->getOptionBookmarks();
        }
    }

    public function render()
    {
        return view('aura::livewire.bookmark-page');
    }

    public function toggleBookmark()
    {
        $bookmarks = auth()->user()->getOptionBookmarks();
        $bookmarkUrls = array_column($bookmarks, 'url');
        $key = array_search($this->site['url'], $bookmarkUrls);

        if ($key !== false) {
            unset($bookmarks[$key]);
        } else {
            $bookmarks[] = $this->site;
        }

        // save without keys
        $bookmarks = array_values($bookmarks);

        auth()->user()->updateOption('bookmarks', $bookmarks);
        // dump('toggleBookmark', $bookmarks, $bookmarkUrls, $key);
    }
}
```

## ./Livewire/Resource/Index.php
```
<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public $resource;

    public $slug;

    public function mount()
    {
        // Get the slug from the current route
        $routeName = request()->route()->getName();
        $this->slug = explode('.', $routeName)[1] ?? null;

        if (! $this->slug) {
            // If we couldn't extract the slug, redirect to dashboard
            return redirect()->route('aura.dashboard');
        }

        $this->resource = Aura::findResourceBySlug($this->slug);

        // if this post is null redirect to dashboard
        if (is_null($this->resource)) {
            return redirect()->route('aura.dashboard');
        }

        if (! $this->resource::$indexViewEnabled) {
            return redirect()->route('aura.dashboard');
        }

        // Authorize if the User can see this Post
        $this->authorize('viewAny', $this->resource);
    }

    public function render()
    {
        return view($this->resource->indexView())->layout('aura::components.layout.app');
    }
}
```

## ./Livewire/Resource/Create.php
```
<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Aura\Base\Models\Post;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\MediaFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Create extends Component
{
    use AuthorizesRequests;
    use InteractsWithFields;
    use MediaFields;
    use RepeaterFields;

    public $form;

    public $inModal = false;

    public $mode = 'edit';

    public $model;

    public $params;

    public $showSaveButton = true;

    public $slug;

    public $tax;

    protected $listeners = ['updateField' => 'updateField'];

    public function callMethod($method, $params = [], $captureReturnValueCallback = null)
    {
        // If the method exists in this component, call it directly.
        if (method_exists($this, $method) || ! optional($params)[0]) {
            return parent::callMethod($method, $params, $captureReturnValueCallback);
        }

        // Assuming the first parameter is always the slug to identify the field.
        $slug = $params[0];

        // Get the corresponding field instance based on the slug.
        $field = $this->model->fieldBySlug($slug);

        // Forward the call to the field's method.
        if ($field) {

            $fieldTypeInstance = app($field['type']);

            // If the method exists in the field type, call it directly.
            if (method_exists($fieldTypeInstance, $method)) {
                $post = call_user_func_array([$fieldTypeInstance, $method], array_merge([$this->model, $this->form], $params));

                // If the field type method returns a post, update the post.
                if ($post) {
                    $this->form = $post;
                }

                // Make sure to return here, otherwise the parent callMethod will be called.
                return;
            }
        }

        // Run parent callMethod
        return parent::callMethod($method, $params, $captureReturnValueCallback);
    }

    public function mount($slug = null)
    {
        $this->slug = $slug;

        if (! $this->slug) {
            $routeName = request()->route()->getName();
            $this->slug = explode('.', $routeName)[1] ?? null;
        }

        $this->model = Aura::findResourceBySlug($this->slug);

        // ray($this->model);

        // Authorize
        $this->authorize('create', $this->model);

        // Array instead of Eloquent Model
        $this->form = $this->model->toArray();

        // get "for" and "id" params from url
        $for = request()->get('for');
        $id = request()->get('id');

        // if params are set, set the post's "for" and "id" fields
        if ($this->params) {
            if ($this->params['for'] == 'User') {
                $this->form['fields']['user_id'] = (int) $this->params['id'];
            }

            // if there is a key in post's fields named $this->params['for'], set it to $this->params['id']
            if (optional($this->params)['for'] && optional($this->params)['id'] && array_key_exists($this->params['for'], $this->form['fields'])) {
                $this->form['fields'][$this->params['for']] = (int) $this->params['id'];
            }
        }

        // If $for is "User", set the user_id to the $id
        // This needs to be more dynamic, but it works for now
        if ($for == 'User') {
            $this->form['fields']['user_id'] = (int) $id;
        }

        // Initialize the post fields with defaults
        $this->initializeFieldsWithDefaults();

    }

    public function render()
    {
        return view('aura::livewire.resource.create')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return collect($this->model->validationRules())->mapWithKeys(function ($rule, $key) {
            return ["form.fields.$key" => $rule];
        })->toArray();
    }

    public function save()
    {
        // dd($this->model->toArray(), $this->rules(), $this->model->validationRules());

        $this->validate();

        // dd('save', $this->form);

        if ($this->model->usesCustomTable()) {

            $model = $this->model->create($this->form['fields']);

        } else {

            $model = $this->model->create($this->form);

        }

        $this->notify('Successfully created.');

        if ($this->inModal) {
            $this->dispatch('closeModal');
            $this->dispatch('refreshTable');

            if ($this->params['for']) {
                $this->dispatch('resourceCreated', ['for' => $this->params['for'], 'resource' => $model, 'title' => $model->title()]);
            }
        } else {
            return redirect()->route('aura.'.$this->slug.'.edit', $model->id);
        }
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    protected function initializeFieldsWithDefaults()
    {
        $fields = $this->model->getFields(); // Assume this returns the fields configurations

        foreach ($fields as $field) {
            $slug = $field['slug'] ?? null;

            if ($field['type'] == "Aura\Base\Fields\Boolean" && ! isset($field['default'])) {
                $this->form['fields'][$slug] = false;

                continue;
            }

            if ($slug && ! isset($this->form['fields'][$slug]) && isset($field['default'])) {

                if ($field['type'] == "Aura\Base\Fields\Checkbox" && isset($field['options']) && is_array($field['options']) && ! is_array($field['default'])) {
                    $field['default'] = [$field['default']];
                }

                $this->form['fields'][$slug] = $field['default'];
            }
        }
    }
}
```

## ./Livewire/Resource/View.php
```
<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\HasActions;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class View extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;
    use RepeaterFields;

    public $form;

    public $inModal = false;

    public $mode = 'view';

    public $model;

    public $slug;

    public $tax;

    // Listen for selectedAttachment
    protected $listeners = [
        'updateField' => 'updateField',
        'refreshComponent' => '$refresh',
        'reload',
    ];

    public function getField($slug)
    {
        return $this->form['fields'][$slug];
    }

    public function mount($id)
    {
        // Get the slug from the current route
        $routeName = request()->route()->getName();
        $this->slug = explode('.', $routeName)[1] ?? null;

        $this->model = Aura::findResourceBySlug($this->slug)->find($id);

        // Authorize
        // ray($this->model, $slug, auth()->user());

        $this->authorize('view', $this->model);

        // Array instead of Eloquent Model
        $this->form = $this->model->attributesToArray();

        $this->form['terms'] = $this->model->terms;
        $this->form['terms']['tag'] = $this->form['terms']['tag'] ?? null;
        $this->form['terms']['category'] = $this->form['terms']['category'] ?? null;
    }

    public function reload()
    {
        $this->model = $this->model->fresh();
        $this->form = $this->model->attributesToArray();

        $this->dispatch('refreshComponent');
    }

    public function render()
    {
        // $view = "aura.{$this->slug}.view";

        // // if aura::aura.{$post->type}.view is set, use that view
        // if (view()->exists($view)) {
        //     return view($view)->layout('aura::components.layout.app');
        // }

        // if (view()->exists("aura::" . $view)) {
        //     return view("aura::" . $view)->layout('aura::components.layout.app');
        // }
        return view($this->model->viewView())->layout('aura::components.layout.app');

    }
}
```

## ./Livewire/Resource/Edit.php
```
<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Aura\Base\Models\Post;
use Aura\Base\Traits\HasActions;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\MediaFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Traits\Macroable;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;
    use MediaFields;
    use RepeaterFields;

    // use Macroable;
    use WithFileUploads;

    public $form;

    public $inModal = false;

    public $mode = 'edit';

    public $model;

    public $slug;

    public $tab;

    public $tax;

    // Listen for selectedAttachment
    protected $listeners = [
        'updateField' => 'updateField',
        'saveModel' => 'save',
        'refreshComponent' => '$refresh',
        'reload',
        'saveBeforeAction',
    ];

    public function initializeModelFields()
    {
        foreach ($this->model->inputFields() as $field) {
            // If the method exists in the field type, call it directly.
            if (method_exists($field['field'], 'hydrate')) {
                $this->form['fields'][$field['slug']] = $field['field']->hydrate();
            }

            if ($field['field']->on_forms === false) {
                unset($this->form['fields'][$field['slug']]);
            }

            if (optional($field)['on_forms'] === false) {
                unset($this->form['fields'][$field['slug']]);
            }
        }
    }

    public function mount($id)
    {
        // Get the slug from the current route
        $routeName = request()->route()->getName();
        $this->slug = explode('.', $routeName)[1] ?? null;

        $this->model = Aura::findResourceBySlug($this->slug)->find($id);

        // Authorize
        $this->authorize('update', $this->model);

        // Array instead of Eloquent Model
        $this->form = $this->model->attributesToArray();

        // dd($this->model->attributesToArray());

        // foreach fields, call the hydration method on the field
        $this->initializeModelFields();

        // foreach fields, call the hydration method on the field

        // ray('mount', $this->form, $this->model);

        // Set on model instead of here
        // if $this->form['terms']['tag'] is not set, set it to null
    }

    public function reload()
    {
        $this->model = $this->model->fresh();
        // $this->form = $this->model->attributesToArray();
        // The GET method is not supported for this route. Only POST is supported.
        // Therefore, we cannot use redirect()->to(url()->current()).
        // Instead, we will refresh the component.
        $this->dispatch('refreshComponent');
    }

    public function render()
    {
        return view($this->model->editView())->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return collect($this->model->validationRules())->mapWithKeys(function ($rule, $key) {
            return ["form.fields.$key" => $rule];
        })->toArray();
    }

    public function save()
    {
        ray('saving', $this->form);
        $this->validate();

        //  
        // // dd('saving', $this->form, $this->model);
        // ray('saving', $this->form['fields']);

        // unset($this->form['fields']['group']);

        // unset this post fields group
        if ($this->model->usesCustomTable()) {
            $this->model->update($this->form['fields']);
        } else {
            $this->model->update($this->form);
        }

        $this->notify(__('Successfully updated'));

        if ($this->inModal) {
            $this->dispatch('closeModal');
            $this->dispatch('refreshTable');
        }

        $this->model = $this->model->refresh();
        $this->form = $this->model->attributesToArray();

        $this->dispatch('refreshComponent');
    }

    public function updatedPost($value, $array)
    {
        // dd('updatedPostFields', $value, $array, $this->form);
    }

    protected function callComponentMethod($method, $params)
    {
        $callbacks = array_filter(
            $params,
            fn ($param) => $param instanceof Closure
        );

        $params = array_filter(
            $params,
            fn ($param) => ! $param instanceof Closure
        );

        $result = parent::callMethod($method, $params);

        foreach ($callbacks as $callback) {
            $callback($this);
        }

        return $result;
    }
}
```

## ./Livewire/Resource/EditModal.php
```
<?php

namespace Aura\Base\Livewire\Resource;

class EditModal extends Edit
{
    public $resource;

    public $type;

    public function mount($id) {}

    public function render()
    {
        return view('aura::livewire.resource.edit-modal');
    }
}
```

## ./Livewire/Resource/CreateModal.php
```
<?php

namespace Aura\Base\Livewire\Resource;

use LivewireUI\Modal\ModalComponent;

class CreateModal extends ModalComponent
{
    public $params;

    public $resource;

    public $type;

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public function render()
    {
        return view('aura::livewire.resource.create-modal');
    }
}
```

## ./Livewire/SlideOver.php
```
<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class SlideOver extends Component
{
    protected $listeners = ['slideOverOpened' => 'activate'];

    public function activate($id, $params)
    {
        $this->mount($id, $params);
        // dd('activated', $id, $params);
    }

    public function mount($id = null, $params = null)
    {
        if ($id) {
            // dd('mounted', $id, $params);
        }
    }

    public function render()
    {
        return view('aura::livewire.slide-over');
    }
}
```

## ./Livewire/GlobalSearch.php
```
<?php

namespace Aura\Base\Livewire;

use Livewire\Component;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;

class GlobalSearch extends Component
{
    public $bookmarks;

    public $search = '';

    public function getSearchResultsProperty()
    {
        // if no $this->search return
        if (! $this->search || $this->search === '') {
            return [];
        }

        // Get all accessible resources
        $resources = app('aura')::getResources();

        // Initialize search results array

        // filter out flows and flow_logs from resources
        $resources = array_filter($resources, function ($resource) {
            if ($resource === null) {
                return false;
            }

            if ($resource::getGlobalSearch() === false) {
                return false;
            }

            return $resource::getSlug() !== 'resource' && $resource::getSlug() !== 'flow' && $resource::getSlug() !== 'flowlog' && $resource::getSlug() !== 'operation' && $resource::getSlug() !== 'flowoperation' && $resource::getSlug() !== 'operationlog' && $resource::getSlug() !== 'option' && $resource::getSlug() !== 'team' && $resource::getSlug() !== 'user' && $resource::getSlug() !== 'product';
        });

        $searchResults = collect([]);

        // Search in each resource model
        foreach ($resources as $resource) {
            $model = $resource::query();

            // if no resource then continue
            if (! $resource) {
                continue;
            }

            $searchableFields = app($resource)->getSearchableFields()->pluck('slug');

            $metaFields = $searchableFields->filter(function ($field) use ($resource) {
                // check if it is a meta field
                return app($resource)->isMetaField($field);
            });

            $results = $model->select('posts.*')
                ->leftJoin('meta', function ($join) use ($metaFields, $resource) {
                    $join->on('posts.id', '=', 'meta.metable_id')
                        ->where('meta.metable_type', $resource)
                        ->whereIn('meta.key', $metaFields);
                })
                ->where(function ($query) {
                    $query->where('posts.title', 'like', '%'.$this->search.'%')
                        ->orWhere(function ($query) {
                            $query->where('meta.value', 'LIKE', '%'.$this->search.'%');
                        });
                })
                ->distinct()
                ->get();

            $searchResults->push(...$results);
        }

        // Search in User model
        $userResults = User::where('name', 'like', '%'.$this->search.'%')
            ->orWhere('email', 'like', '%'.$this->search.'%')
            ->get();
        $searchResults->push(...$userResults);

        $searchResults = $searchResults->flatten()->map(function ($item) {
            // return route aura.resource.view with slug and id
            if (isset($item->type)) {
                $item['view_url'] = route('aura.'.strtolower($item->type).'.view', ['id' => $item->id]);
            } else {
                $item['view_url'] = route('aura.user.view', ['id' => $item->id]);
            }

            return $item;
        });

        // limit to 15
        $searchResults = $searchResults->take(15);

        // group by type
        $searchResults = $searchResults->groupBy('type');

        return $searchResults;
    }

    public function mount()
    {
        if (auth()->check()) {
            $this->bookmarks = auth()->user()->getOptionBookmarks();
        } else {
            $this->bookmarks = [];
        }
    }

    public function render()

    {
        if (auth()->check()) {
            $this->bookmarks = auth()->user()->getOptionBookmarks();
        } else {
            $this->bookmarks = [];
        }

        return view('aura::livewire.global-search');
    }
}
```

## ./Livewire/EditResourceField.php
```
<?php

namespace Aura\Base\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\RepeaterFields;
use Aura\Base\Traits\SaveFields;
use Illuminate\Support\Arr;
use Livewire\Component;

class EditResourceField extends Component
{
    use RepeaterFields;
    use SaveFields;

    public $field;

    public $fieldSlug;

    public $form;

    public $mode = 'edit';

    public $model;

    public $newFieldIndex = null;

    public $newFieldSlug = null;

    public $open = false;

    public $reservedWords = ['id', 'type'];

    // listener for newFields
    protected $listeners = ['newFields' => 'newFields'];

    public function activate($params)
    {
        ray('activate', $params)->orange();

        if (optional($params)['create']) {
            $this->field = [
                'type' => $params['type'] ?? 'Aura\Base\Fields\Text',
                'slug' => '',
                'name' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
                'validation' => '',
                'conditional_logic' => '',
            ];

            ray('children', $params['slug'], $params['children']);
            $this->newFieldIndex = $params['children'];
            $this->newFieldSlug = $params['slug'];

            $this->model = Aura::findResourceBySlug($params['model']);

            $this->form['fields'] = $this->field;

            $this->updatedField();

            $this->open = true;

            $this->mode = 'create';

            ray('add Field', $this->field)->orange();

            return;
        }

        $this->mode = 'edit';

        $this->fieldSlug = $params['fieldSlug'];
        $this->form['fields'] = $params['field'];

        $this->model = Aura::findResourceBySlug($params['model']);

        // dd( $params['field'] );
        $this->field = $params['field'];

        // Check if field is an input field
        if (app($this->field['type'])->isInputField()) {
            if (! isset($this->form['fields']['on_index'])) {
                $this->form['fields']['on_index'] = true;
            }
            if (! isset($this->form['fields']['on_forms'])) {
                $this->form['fields']['on_forms'] = true;
            }
            if (! isset($this->form['fields']['on_view'])) {
                $this->form['fields']['on_view'] = true;
            }
            if (! isset($this->form['fields']['searchable'])) {
                $this->form['fields']['searchable'] = false;
            }
        }
        $this->updatedField();
        $this->open = true;
    }

    public function deleteField($slug)
    {
        $this->dispatch('deleteField', ['slug' => $this->fieldSlug, 'value' => $this->form['fields']]);

        $this->open = false;
    }

    public function getGroupedFieldsProperty()
    {
        return app($this->field['type'])->getGroupedFields();
    }

    public function newFields($fields)
    {
        $field = collect($fields)->firstWhere('slug', $this->field['slug']);

        if (! $field) {
            return;
        }

        foreach ($field as $key => $value) {
            if (is_null($value)) {
                $field[$key] = false;
            }
        }

        $this->field = $field;
        $this->form['fields'] = $field;
        $this->updatedField();

        // $this->dispatch('refreshComponent');
    }

    public function render()
    {
        return view('aura::livewire.edit-resource-field')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        $rules = Arr::dot([
            'form.fields' => app($this->field['type'])->validationRules(),
        ]);

        $rules['form.fields.slug'] = [
            'required',
            'regex:/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/',
            'not_regex:/^[0-9]+$/',
            function ($attribute, $value, $fail) {

                // Check if a field with the same slug already exists in mappedFields
                $existingFields = $this->model->mappedFields();
                $slugExists = collect($existingFields)->pluck('slug')->contains($value);

                if ($slugExists && $value !== $this->field['slug']) {
                    $fail("A field with the slug '{$value}' already exists.");

                    return;
                }

                return false;

                if (collect($this->form['fields'])->pluck('slug')->duplicates()->values()->contains($value)) {
                    $fail('The '.$attribute.' can not be used twice.');
                }

                // check if slug is a reserved word with "in_array"
                if (in_array($value, $this->reservedWords)) {
                    $fail('The '.$attribute.' can not be a reserved word.');
                }
            },
        ];

        return $rules;
    }

    public function save()
    {
        // Validate
        // remove all NULL values from $this->form['fields']
        $this->form['fields'] = array_filter($this->form['fields'], function ($value) {
            return ! is_null($value);
        });

        $this->validate();

        if ($this->mode == 'create') {
            // ray('saveNewField', $this->form['fields'], $this->newFieldIndex, $this->newFieldSlug)->blue();
            $this->dispatch('saveNewField', $this->form['fields'], $this->newFieldIndex, $this->newFieldSlug);
        } else {
            // emit event to parent with slug and value
            $this->dispatch('saveField', ['slug' => $this->fieldSlug, 'value' => $this->form['fields']]);
        }

        $this->dispatch('finishedSavingFields');
        $this->dispatch('refresh-resource-editor');

        $this->open = false;
    }

    public function updated($property)
    {
        // $property: The name of the current property that was updated
        ray('updated', $property)->orange();

        if ($property === 'form.fields.type') {
            // $this->username = strtolower($this->username);
            $this->updateType();
        }
    }

    public function updatedField()
    {
        // if $this->field is undefined, return
        if (! isset($this->field['type'])) {
            return;
        }

        // dd(app($this->field['type'])->inputFields());
        $fields = app($this->field['type'])->inputFields()->pluck('slug');

        // fields are not set on $this->form['fields'] set it to false
        foreach ($fields as $field) {
            if (! isset($this->form['fields'][$field])) {
                $this->form['fields'][$field] = null;
            }
        }
    }

    public function updateType()
    {
        // Validate
        // $this->validate();

        // emit event to parent with slug and value
        $this->dispatch('saveField', ['slug' => $this->fieldSlug, 'value' => $this->form['fields']]);
    }
}
```

## ./Livewire/Modal.php
```
<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class Modal extends Component
{
    protected $listeners = ['modalOpened' => 'activate'];

    public function activate($id, $params)
    {
        $this->mount($id, $params);
    }

    public function render()
    {
        return view('aura::livewire.modal');
    }
}
```

## ./Livewire/Navigation.php
```
<?php

namespace Aura\Base\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

class Navigation extends Component
{
    public $iconClass;

    public $toggledGroups = [];

    #[Computed]
    public function compact(): string
    {
        return ($this->settings['sidebar-size'] ?? null) === 'compact';
    }

    #[Computed]
    public function darkmodeType(): string
    {
        return $this->settings['darkmode-type'] ?? 'auto';
    }

    public function getIconClass($sidebarType)
    {
        return '';
    }

    public function isToggled($group)
    {
        return ! in_array($group, $this->toggledGroups);
    }

    public function mount($query = null)
    {
        $this->dispatch('NavigationMounted');

        if (auth()->check() && auth()->user()->getOptionSidebar()) {
            $this->toggledGroups = auth()->user()->getOptionSidebar();
        } else {
            $this->toggledGroups = [];
            // $this->sidebarToggled = true;
        }

        if (auth()->check()) {
            $this->sidebarToggled = auth()->user()->getOptionSidebarToggled();
        }

        $this->iconClass = $this->getIconClass($this->sidebarType);
    }

    public function render()
    {
        return view('aura::livewire.navigation');
    }

    #[Computed]
    public function settings()
    {
        if (config('aura.teams')) {
            return app('aura')::getOption('settings');
        }

        return app('aura')::getOption('settings');
    }

    #[Computed]
    public function sidebarDarkmodeType(): string
    {
        return $this->settings['sidebar-darkmode-type'] ?? 'dark';
    }

    #[Computed]
    public function sidebarToggled()
    {
        return auth()->check() ? auth()->user()->getOptionSidebarToggled() : true;
    }

    #[Computed]
    public function sidebarType(): string
    {
        return $this->settings['sidebar-type'] ?? 'primary';
    }

    public function toggleGroup($group)
    {
        if (in_array($group, $this->toggledGroups)) {
            $this->toggledGroups = array_diff($this->toggledGroups, [$group]);
        } else {
            $this->toggledGroups[] = $group;
        }

        auth()->user()->updateOption('sidebar', $this->toggledGroups);
    }

    public function toggleSidebar()
    {
        $this->sidebarToggled = ! $this->sidebarToggled;

        auth()->user()->updateOption('sidebarToggled', $this->sidebarToggled);
    }
}
```

## ./Http/Middleware/VerifyCsrfToken.php
```
<?php

namespace Aura\Base\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
```

## ./Http/Middleware/RedirectIfAuthenticated.php
```
<?php

namespace Aura\Base\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect(config('aura.auth.redirect'));
            }
        }

        return $next($request);
    }
}
```

## ./Http/Middleware/TrimStrings.php
```
<?php

namespace Aura\Base\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
    ];
}
```

## ./Http/Middleware/Authenticate.php
```
<?php

namespace Aura\Base\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
```

## ./Http/Middleware/TrustProxies.php
```
<?php

namespace Aura\Base\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;
}
```

## ./Http/Middleware/PreventRequestsDuringMaintenance.php
```
<?php

namespace Aura\Base\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
```

## ./Http/Middleware/EncryptCookies.php
```
<?php

namespace Aura\Base\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
```

## ./Http/Middleware/TrustHosts.php
```
<?php

namespace Aura\Base\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts()
    {
        return [
            $this->allSubdomainsOfApplicationUrl(),
        ];
    }
}
```

## ./Http/Requests/StorePostRequest.php
```
<?php

namespace Aura\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
```

## ./Http/Requests/StoreTaxonomyRequest.php
```
<?php

namespace Aura\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxonomyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
```

## ./Http/Requests/UpdateTaxonomyRequest.php
```
<?php

namespace Aura\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxonomyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
```

## ./Http/Requests/UpdateSettingRequest.php
```
<?php

namespace Aura\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
```

## ./Http/Requests/Auth/LoginRequest.php
```
<?php

namespace Aura\Base\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited()
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
```

## ./Http/Requests/StoreOptionRequest.php
```
<?php

namespace Aura\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
```

## ./Http/Requests/UpdatePostRequest.php
```
<?php

namespace Aura\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
```

## ./Http/Requests/ProfileUpdateRequest.php
```
<?php

namespace Aura\Base\Http\Requests;

use Aura\Base\Resources\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['string', 'max:255'],
            'email' => ['email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
        ];
    }
}
```

## ./Http/Requests/UpdateMetaRequest.php
```
<?php

namespace Aura\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMetaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
```

## ./Http/Requests/StoreMetaRequest.php
```
<?php

namespace Aura\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMetaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
```

## ./Http/Requests/UpdateOptionRequest.php
```
<?php

namespace Aura\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
```

## ./Http/Controllers/MetaController.php
```
<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Http\Requests\StoreMetaRequest;
use Aura\Base\Http\Requests\UpdateMetaRequest;
use Aura\Base\Models\Meta;

class MetaController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Http\Response
     */
    public function destroy(Meta $meta)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Http\Response
     */
    public function edit(Meta $meta)
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Http\Response
     */
    public function show(Meta $meta)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreMetaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMetaRequest $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMetaRequest  $request
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMetaRequest $request, Meta $meta)
    {
        //
    }
}
```

## ./Http/Controllers/Controller.php
```
<?php

namespace Aura\Base\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
}
```

## ./Http/Controllers/SwitchTeamController.php
```
<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Resources\Team;
use Illuminate\Http\Request;

class SwitchTeamController extends Controller
{
    /**
     * Update the authenticated user's current team.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $team = Team::findOrFail($request->team_id);

        if (! $request->user()->switchTeam($team)) {
            abort(403);
        }

        return redirect(route('aura.dashboard'), 303);
    }
}
```

## ./Http/Controllers/Auth/NewPasswordController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        return view('aura::auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
```

## ./Http/Controllers/Auth/EmailVerificationPromptController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     *
     * @return mixed
     */
    public function __invoke(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(config('aura.auth.redirect'))
                    : view('aura::auth.verify-email');
    }
}
```

## ./Http/Controllers/Auth/InvitationRegisterUserController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class InvitationRegisterUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request, Team $team, TeamInvitation $teamInvitation)
    {
        // If team registration is disabled, we show a 404 page.
        abort_if(! config('aura.auth.user_invitations'), 404);

        return view('aura::auth.user_invitation', [
            'team' => $team,
            'teamInvitation' => $teamInvitation,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, Team $team, TeamInvitation $teamInvitation)
    {
        abort_if(! config('aura.auth.user_invitations'), 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $teamInvitation->email,
            'password' => Hash::make($request->password),
            'current_team_id' => $team->id,
            'fields' => ['roles' => [$teamInvitation->role]],
        ]);

        // dd($user->fresh()->toArray());

        // Delete the invitation
        $teamInvitation->delete();

        event(new Registered($user));

        Auth::login($user);

        return redirect(config('aura.auth.redirect'));
    }
}
```

## ./Http/Controllers/Auth/VerifyEmailController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(config('aura.auth.redirect').'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(config('aura.auth.redirect').'?verified=1');
    }
}
```

## ./Http/Controllers/Auth/PasswordResetLinkController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('aura::auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // dd('hier', $status);

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
```

## ./Http/Controllers/Auth/PasswordController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
```

## ./Http/Controllers/Auth/TeamInvitationController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Resources\TeamInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Jetstream;

class TeamInvitationController extends Controller
{
    /**
     * Accept a team invitation.
     *
     * @param  int  $invitationId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accept(Request $request, $invitationId)
    {
        $model = new TeamInvitation;

        $invitation = $model::whereKey($invitationId)->firstOrFail();

        app(AddsTeamMembers::class)->add(
            $invitation->team->owner,
            $invitation->team,
            $invitation->email,
            $invitation->role
        );

        $invitation->delete();

        return redirect(config('fortify.home'))->banner(
            __('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]),
        );
    }

    /**
     * Cancel the given team invitation.
     *
     * @param  int  $invitationId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $invitationId)
    {
        $model = Jetstream::teamInvitationModel();

        $invitation = $model::whereKey($invitationId)->firstOrFail();

        if (! Gate::forUser($request->user())->check('removeTeamMember', $invitation->team)) {
            throw new AuthorizationException;
        }

        $invitation->delete();

        return back(303);
    }
}
```

## ./Http/Controllers/Auth/EmailVerificationNotificationController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(config('aura.auth.redirect'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
```

## ./Http/Controllers/Auth/AuthenticatedSessionController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('aura::auth.login');
    }

    /**
     * Destroy an authenticated session.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        // Here we need to handle 2FA from Laravel Fortify

        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(config('aura.auth.redirect'));
    }
}
```

## ./Http/Controllers/Auth/RegisteredUserController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Facades\Aura;
use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // If team registration is disabled, we show a 404 page.
        abort_if(! config('aura.auth.registration'), 404);

        return view('aura::auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        abort_if(! config('aura.auth.registration'), 404);

        if (config('aura.teams')) {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'team' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $team = Team::create([
                'name' => $request->team,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user->current_team_id = $team->id;

            $user->save();

            $role = $team->roles->first();

            $user->update(['roles' => [$role->id]]);
        } else {
            // no aura.teams
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $role = Role::where('slug', 'user')->firstOrFail();

            $user->update(['roles' => [$role->id]]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(config('aura.auth.redirect'));
    }
}
```

## ./Http/Controllers/Auth/ConfirmablePasswordController.php
```
<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('aura::auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(config('aura.auth.redirect'));
    }
}
```

## ./Http/Controllers/OptionController.php
```
<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Http\Requests\StoreOptionRequest;
use Aura\Base\Http\Requests\UpdateOptionRequest;
use Aura\Base\Models\Option;

class OptionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Option  $option
     * @return \Illuminate\Http\Response
     */
    public function destroy(Option $option)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Option  $option
     * @return \Illuminate\Http\Response
     */
    public function edit(Option $option)
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Option  $option
     * @return \Illuminate\Http\Response
     */
    public function show(Option $option)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreOptionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOptionRequest $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateOptionRequest  $request
     * @param  \App\Models\Option  $option
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOptionRequest $request, Option $option)
    {
        //
    }
}
```

## ./Http/Controllers/ImageController.php
```
<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Services\ThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function __construct(
        protected ThumbnailGenerator $thumbnailGenerator
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $path)
    {
        // Get query parameters for width and height if set
        $width = $request->query('width', 200);
        $height = $request->query('height');

        // Generate the thumbnail
        $thumbnailPath = $this->thumbnailGenerator->generate($path, $width, $height);

        // ray($thumbnailPath);

        // Return the thumbnail image
        return response()->file(storage_path('app/public/'.$thumbnailPath));
    }
}
```

## ./Http/Controllers/ProfileController.php
```
<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ProfileController extends Controller
{
    /**
     * Delete the user's account.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Display the user's profile form.
     *
     * @return \Illuminate\View\View
     */
    public function edit(Request $request)
    {
        return view('aura::profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     *
     * @param  \App\Http\Requests\ProfileUpdateRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProfileUpdateRequest $request)
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('aura.profile.edit')->with('status', 'profile-updated');
    }
}
```

## ./Http/Controllers/PostController.php
```
<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Http\Requests\StorePostRequest;
use Aura\Base\Http\Requests\UpdatePostRequest;
use Aura\Base\Models\Post;

class PostController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePostRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePostRequest $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePostRequest  $request
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        //
    }
}
```

## ./Http/Controllers/Api/FieldsController.php
```
<?php

namespace Aura\Base\Http\Controllers\Api;

use Aura\Base\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FieldsController extends Controller
{
    public function values(Request $request)
    {
        // if $request->model or $request->slug are missing, throw an error
        if (! $request->model || ! $request->slug) {
            return response()->json([
                'error' => 'Missing model or slug',
            ], 400);
        }

        // Get the field
        $field = app($request->field)->api($request);

        return $field;
    }
}
```

## ./Http/Kernel.php
```
<?php

namespace Aura\Base\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];
}
```

## ./Facades/Aura.php
```
<?php

namespace Aura\Base\Facades;

use Aura\Base\AuraFake;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Aura\Base\Aura
 */
class Aura extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \Illuminate\Support\Testing\Fakes\MailFake
     */
    public static function fake()
    {
        static::swap($fake = new AuraFake);

        return $fake;
    }

    protected static function getFacadeAccessor()
    {
        return \Aura\Base\Aura::class;
    }
}
```

## ./Facades/DynamicFunctions.php
```
<?php

namespace Aura\Base\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Aura\Base\Aura
 */
class DynamicFunctions extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aura\Base\DynamicFunctions::class;
    }
}
```

## ./Aura.php
```
<?php

namespace Aura\Base;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\User;
use Aura\Base\Traits\DefaultFields;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class Aura
{
    use DefaultFields;

    // public function __construct()
    // {
    // }

    /**
     * The user model that should be used by Jetstream.
     *
     * @var string
     */
    public static $userModel = User::class;

    protected array $config = [];

    protected array $fields = [];

    protected array $injectViews = [];

    protected array $resources = [];

    protected array $widgets = [];

    /**
     * Determine if Aura's published assets are up-to-date.
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public static function assetsAreCurrent()
    {
        if (app()->environment('testing')) {
            return true;
        }

        $publishedPath = public_path('vendor/aura/manifest.json');

        if (! File::exists($publishedPath)) {
            throw new RuntimeException('Aura CMS assets are not published. Please run: php artisan aura:publish');
        }

        return File::get($publishedPath) === File::get(__DIR__.'/../resources/dist/manifest.json');
    }

    public static function checkCondition($model, $field, $post = null)
    {
        return ConditionalLogic::shouldDisplayField($model, $field, $post);
    }

    public function clear()
    {
        $this->clearRoutes();

        Cache::clear();
    }

    public function clearConditionsCache()
    {
        return ConditionalLogic::clearConditionsCache();
    }

    public function clearRoutes()
    {
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    public function findResourceBySlug($slug)
    {
        if (in_array($slug, $this->getResources())) {
            return app($slug);
        }

        $resources = collect($this->getResources())->map(function ($resource) {
            return Str::afterLast($resource, '\\');
        });

        $index = $resources->search(function ($item) use ($slug) {
            return Str::slug($item) == Str::slug($slug);
        });

        if ($index !== false) {
            return app($this->getResources()[$index]);
        }
    }

    public static function findTemplateBySlug($slug)
    {
        return app('Aura\Base\Templates\\'.str($slug)->title);
    }

    public function getAppFields()
    {
        $path = config('aura.fields.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Field', $namespace = config('aura.fields.namespace'));
    }

    public function getAppFiles($path, $filter, $namespace)
    {

        return collect(app(Filesystem::class)->allFiles($path))
            ->map(function (SplFileInfo $file): string {
                return (string) Str::of($file->getRelativePathname())
                    ->replace(['/', '.php'], ['\\', '']);
            })
            ->filter(fn (string $class): bool => $class != $filter)
            ->map(fn ($item) => $namespace.'\\'.$item)
            ->unique()->toArray();
    }

    /**
     * Register the App resources
     *
     * @param  array  $resources
     * @return static
     */
    public function getAppResources()
    {
        $path = config('aura-settings.paths.resources.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Resource', $namespace = config('aura-settings.paths.resources.namespace'));
    }

    public function getAppWidgets()
    {
        $path = config('aura-settings.widgets.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Widget', $namespace = config('aura-settings.widgets.namespace'));
    }

    public function getFields(): array
    {
        return array_unique($this->fields);
    }

    public function getFieldsWithGroups(): array
    {
        return collect($this->fields)
            ->groupBy(function ($field) {
                $fieldClass = app($field);

                return property_exists($fieldClass, 'optionGroup') && ! empty($fieldClass->optionGroup) ? $fieldClass->optionGroup : 'Fields';
            })
            ->mapWithKeys(function ($fields, $groupName) {
                return [$groupName => collect($fields)->mapWithKeys(function ($field) {
                    return [$field => class_basename($field)];
                })->sortKeys()->toArray()];
            })
            ->sortKeys()
            ->toArray();
    }

    public function getInjectViews(): array
    {
        return $this->injectViews;
    }

    public function getOption($name)
    {
        if (config('aura.teams') && optional(optional(auth()->user())->resource)->currentTeam) {
            return Cache::remember(auth()->user()->current_team_id.'.aura.'.$name, now()->addHour(), function () use ($name) {
                $option = auth()->user()->currentTeam->getOption($name);

                if ($option) {
                    if (is_string($option)) {
                        $settings = json_decode($option, true);
                    } else {
                        $settings = $option;
                    }
                } else {
                    $settings = [];
                }

                // ray($settings);

                return $settings;

            });
        }

        return Cache::remember('aura.'.$name, now()->addHour(), function () use ($name) {

            $option = Option::where('name', $name)->first();

            if ($option) {
                if (is_string($option->value)) {
                    $settings = json_decode($option->value, true);
                } else {
                    $settings = $option->value;
                }
            } else {
                $settings = [];
            }

            return $settings;
        });

    }

    public static function getPath($id)
    {
        $attachment = Attachment::find($id);

        return $attachment ? $attachment->url : null;
    }

    public function getResources(): array
    {
        return array_unique(array_filter($this->resources, function ($resource) {
            return ! is_null($resource);
        }));
    }

    public function getWidgets(): array
    {
        return array_unique($this->widgets);
    }

    public function injectView(string $name): Htmlable
    {
        if (isset($this->injectViews[$name])) {
            // ray($name, $this->injectViews[$name]);
        }

        // ray($name);

        $hooks = array_map(
            fn (callable $hook): string => (string) app()->call($hook),
            $this->injectViews[$name] ?? [],
        );

        return new HtmlString(implode('', $hooks));
    }

    public function navigation()
    {
        // Necessary to add TeamIds?

        return Cache::remember('user-'.auth()->id().'-'.auth()->user()->current_team_id.'-navigation', 3600, function () {

            $resources = collect($this->getResources());

            // filter resources by permission and check if user has viewAny permission
            $resources = $resources->filter(function ($resource) {
                if (class_exists($resource)) {
                    $resource = app($resource);
                } else {
                    return false;
                }

                return auth()->user()->can('viewAny', $resource);
            });

            // If a Resource is overriden, we want to remove the original from the navigation
            $keys = $resources->map(function ($resource) {
                return Str::afterLast($resource, '\\');
            })->reverse()->unique()->reverse()->keys();

            $resources = $resources->filter(function ($value, $key) use ($keys) {
                return $keys->contains($key);
            })
                ->map(fn ($r) => app($r)->navigation())
                ->filter(fn ($r) => $r['showInNavigation'] ?? true)
                ->sortBy('sort');

            $resources = app('hook_manager')->applyHooks('navigation', $resources->values());

            $resources = $resources->sortBy('sort')->filter(function ($value, $key) {
                if (isset($value['conditional_logic'])) {
                    return app('dynamicFunctions')::call($value['conditional_logic']);
                }

                return true;
            });

            $grouped = array_reduce(collect($resources)->toArray(), function ($carry, $item) {
                if (isset($item['dropdown']) && $item['dropdown'] !== false) {
                    if (! isset($carry[$item['dropdown']])) {
                        $carry[$item['dropdown']] = [];
                    }
                    $carry[$item['dropdown']]['group'] = $item['group'];
                    $carry[$item['dropdown']]['dropdown'] = $item['dropdown'];
                    $carry[$item['dropdown']]['items'][] = $item;
                } else {
                    $carry[] = $item;
                }

                return $carry;
            }, []);

            return collect($grouped)->groupBy('group');
        });
    }

    public function option($key)
    {
        return $this->options()[$key] ?? null;
    }

    public function options()
    {
        return config('aura');
    }

    public function registerFields(array $fields): void
    {
        $this->fields = array_merge($this->fields, $fields);
    }

    public function registerInjectView(string $name, Closure $callback): void
    {
        $this->injectViews[$name][] = $callback;

        // ray($this->injectViews);
    }

    public function registerResources(array $resources): void
    {
        $this->resources = array_merge($this->resources, $resources);
    }

    public function registerRoutes($slug)
    {
        ray('hier');
        Route::domain(config('aura.domain'))
            ->middleware(config('aura-settings.middleware.aura-admin'))
            ->prefix(config('aura.path')) // This is likely 'admin' from your config
            ->name('aura.')
            ->group(function () use ($slug) {
                Route::get("/{$slug}", Index::class)->name("{$slug}.index");
                Route::get("/{$slug}/create", Create::class)->name("{$slug}.create");
                Route::get("/{$slug}/{id}/edit", Edit::class)->name("{$slug}.edit");
                Route::get("/{$slug}/{id}", View::class)->name("{$slug}.view");
            });
    }

    public function registerWidgets(array $widgets): void
    {
        $this->widgets = array_merge($this->widgets, $widgets);
    }

    public function scripts()
    {
        return view('aura::components.layout.scripts');
    }

    public function styles()
    {
        return view('aura::components.layout.styles');
    }

    public static function templates()
    {
        return Cache::remember('aura.templates', now()->addHour(), function () {
            $filesystem = app(Filesystem::class);

            $files = collect($filesystem->allFiles(app_path('Aura/Templates')))
                ->map(function (SplFileInfo $file): string {
                    return (string) Str::of($file->getRelativePathname())
                        ->replace(['/', '.php'], ['\\', '']);
                })->filter(fn (string $class): bool => $class != 'Template');

            return $files;
        });
    }

    // public function setOption($key, $value)
    // {
    //     $option = $this->getGlobalOptions();

    //     if ($option && is_string($option->value)) {
    //         $settings = json_decode($option->value, true);
    //     } else {
    //         $settings = [];
    //     }

    //     $settings[$key] = $value;

    //     $option->value = json_encode($settings);
    //     $option->save();

    //     Cache::forget('aura-settings');
    // }

    public function updateOption($key, $value)
    {
        if (config('aura.teams')) {
            auth()->user()->currentTeam->updateOption($key, $value);
        } else {
            Option::withoutGlobalScopes([TeamScope::class])->updateOrCreate(['name' => $key], ['value' => $value]);
        }
    }

    /**
     * Get the name of the user model used by the application.
     *
     * @return string
     */
    public static function userModel()
    {
        return static::$userModel;
    }

    public static function useUserModel(string $model)
    {
        static::$userModel = $model;

        return new static;
    }

    public function varexport($expression, $return = false)
    {
        if (! is_array($expression)) {
            return var_export($expression, $return);
        }
        $export = var_export($expression, true);
        $export = preg_replace('/^([ ]*)(.*)/m', '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [null, ']$1', ' => ['], $array);
        $array = preg_replace(["/\d+\s=>\s/"], [null], $array);
        $export = implode(PHP_EOL, array_filter(['['] + $array));
        if ((bool) $return) {
            return $export;
        } else {
            echo $export;
        }
    }

    public function viteScripts()
    {
        return Vite::getFacadeRoot()
            ->useHotFile('vendor/aura/hot')
            ->useBuildDirectory('vendor/aura')->withEntryPoints([
                'resources/js/app.js',
            ]);
    }

    public function viteStyles()
    {
        return Vite::getFacadeRoot()
            ->useHotFile('vendor/aura/hot')
            ->useBuildDirectory('vendor/aura')->withEntryPoints([
                'resources/css/app.css',
            ]);
    }
}
```

## ./Templates/Plain.php
```
<?php

namespace Aura\Base\Templates;

class Plain
{
    public string $name = 'Plain';

    public function getFields()
    {
        return [
            [
                'name' => 'Text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'title',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}
```

## ./Templates/TabsWithPanels.php
```
<?php

namespace Aura\Base\Templates;

class TabsWithPanels
{
    public string $name = 'TabsWithPanels';

    public function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'global' => true,
                'conditional_logic' => [],
                'slug' => 'tab-1',
            ],
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'panel-1',
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'text-1',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'global' => true,
                'conditional_logic' => [],
                'slug' => 'tab-2',
            ],
            [
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'panel-2',
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'text-2',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}
```

## ./Templates/PanelWithSidebar.php
```
<?php

namespace Aura\Base\Templates;

class PanelWithSidebar
{
    public string $name = 'PanelWithSidebar';

    public function getFields()
    {
        return [
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'panel-1',
                'style' => [
                    'width' => '70',
                ],
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'text-1',
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'panel-2',
                'style' => [
                    'width' => '30',
                ],
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'text-2',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}
```

## ./Templates/PanelWithTabs.php
```
<?php

namespace Aura\Base\Templates;

class PanelWithTabs
{
    public string $name = 'PanelWithTabs';

    public function getFields()
    {
        return [
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'panel-1',
            ],
            [
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'tab-1',
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'text-1',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'tab-2',
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'text-2',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}
```

## ./Fields/Email.php
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

## ./Fields/Tabs.php
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

## ./Fields/Wysiwyg.php
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

## ./Fields/Datetime.php
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

## ./Fields/Boolean.php
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

## ./Fields/Code.php
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

## ./Fields/Radio.php
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

## ./Fields/Time.php
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

## ./Fields/Roles.php
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

## ./Fields/Number.php
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

## ./Fields/Group.php
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

## ./Fields/AdvancedSelect.php
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

## ./Fields/Json.php
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

## ./Fields/HorizontalLine.php
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

## ./Fields/Permissions.php
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

## ./Fields/BelongsTo.php
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

## ./Fields/File.php
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

## ./Fields/Hidden.php
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

## ./Fields/BelongsToMany.php
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

## ./Fields/Heading.php
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

## ./Fields/Tab.php
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

## ./Fields/Repeater.php
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

## ./Fields/Field.php
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

## ./Fields/Embed.php
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

## ./Fields/Password.php
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

## ./Fields/ID.php
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

## ./Fields/ViewValue.php
```
<?php

namespace Aura\Base\Fields;

class ViewValue extends Field
{
    public $edit = 'aura::fields.view-value';

    public $view = 'aura::fields.view-value';
}
```

## ./Fields/Date.php
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

## ./Fields/Status.php
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

## ./Fields/Color.php
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

## ./Fields/Panel.php
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

## ./Fields/Tags.php
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

## ./Fields/View.php
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

## ./Fields/Slug.php
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

## ./Fields/Phone.php
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

## ./Fields/Textarea.php
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

## ./Fields/Select.php
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

## ./Fields/Checkbox.php
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

## ./Fields/HasMany.php
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

## ./Fields/LivewireComponent.php
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

## ./Fields/Text.php
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

## ./Fields/Image.php
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

## ./Fields/HasOne.php
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

## ./Commands/Stubs/make-field-view.stub
```
<x-aura::fields.wrapper :field="$field">
    {!! $this->model->display($field['slug']) !!}
</x-aura::fields.wrapper>```

## ./Commands/Stubs/make-taxonomy.stub
```
<?php

namespace App\Aura\Taxonomies;

use Aura\Base\Taxonomies\Taxonomy;

class TaxonomyName extends Taxonomy
{
    public static $hierarchical = false;

    public static string $type = 'TaxonomyName';

    public static ?string $slug = 'TaxonomySlug';

    public function getIcon()
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 7L11.8845 4.76892C11.5634 4.1268 11.4029 3.80573 11.1634 3.57116C10.9516 3.36373 10.6963 3.20597 10.4161 3.10931C10.0992 3 9.74021 3 9.02229 3H5.2C4.0799 3 3.51984 3 3.09202 3.21799C2.71569 3.40973 2.40973 3.71569 2.21799 4.09202C2 4.51984 2 5.0799 2 6.2V7M2 7H17.2C18.8802 7 19.7202 7 20.362 7.32698C20.9265 7.6146 21.3854 8.07354 21.673 8.63803C22 9.27976 22 10.1198 22 11.8V16.2C22 17.8802 22 18.7202 21.673 19.362C21.3854 19.9265 20.9265 20.3854 20.362 20.673C19.7202 21 18.8802 21 17.2 21H6.8C5.11984 21 4.27976 21 3.63803 20.673C3.07354 20.3854 2.6146 19.9265 2.32698 19.362C2 18.7202 2 17.8802 2 16.2V7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
}
```

## ./Commands/Stubs/make-field-edit.stub
```
@dump(':fieldSlug')

<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text :disabled="optional($field)['disabled']" wire:model="form.fields.{{ optional($field)['slug'] }}" error="form.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" id="resource-field-{{ optional($field)['slug'] }}"></x-aura::input.text>
</x-aura::fields.wrapper>
```

## ./Commands/Stubs/livewire.custom.stub
```
<?php

namespace {{ namespace }};

use {{ baseClass }};

class {{ class }} extends {{ componentType }}
{
    // Add your custom logic here

    public function mount($id, $slug = '{{ resourceClass }}')
    {
        parent::mount($slug, $id);
    }
}```

## ./Commands/Stubs/make-custom-resource.stub
```
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class PostName extends Resource
{
    public static string $type = 'PostName';

    public static ?string $slug = 'PostSlug';

    public static $customTable = true;

    protected $table = 'post_slug';

    public static function getWidgets(): array
    {
        return [];
    }

    public function getIcon()
    {
        return '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.5 7.27783L12 12.0001M12 12.0001L3.49997 7.27783M12 12.0001L12 21.5001M21 16.0586V7.94153C21 7.59889 21 7.42757 20.9495 7.27477C20.9049 7.13959 20.8318 7.01551 20.7354 6.91082C20.6263 6.79248 20.4766 6.70928 20.177 6.54288L12.777 2.43177C12.4934 2.27421 12.3516 2.19543 12.2015 2.16454C12.0685 2.13721 11.9315 2.13721 11.7986 2.16454C11.6484 2.19543 11.5066 2.27421 11.223 2.43177L3.82297 6.54288C3.52345 6.70928 3.37369 6.79248 3.26463 6.91082C3.16816 7.01551 3.09515 7.13959 3.05048 7.27477C3 7.42757 3 7.59889 3 7.94153V16.0586C3 16.4013 3 16.5726 3.05048 16.7254C3.09515 16.8606 3.16816 16.9847 3.26463 17.0893C3.37369 17.2077 3.52345 17.2909 3.82297 17.4573L11.223 21.5684C11.5066 21.726 11.6484 21.8047 11.7986 21.8356C11.9315 21.863 12.0685 21.863 12.2015 21.8356C12.3516 21.8047 12.4934 21.726 12.777 21.5684L20.177 17.4573C20.4766 17.2909 20.6263 17.2077 20.7354 17.0893C20.8318 16.9847 20.9049 16.8606 20.9495 16.7254C21 16.5726 21 16.4013 21 16.0586Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getFields()
    {
        return [];
    }


}
```

## ./Commands/Stubs/make-field.stub
```
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class FieldName extends Field
{
    public $edit = 'fields.FieldSlug';

    public $view = 'fields.FieldSlug-view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            // Custom Fields for this field
            // See Documentation for more info
        ]);
    }
}
```

## ./Commands/Stubs/make-resource.stub
```
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class PostName extends Resource
{
    public static string $type = 'PostName';

    public static ?string $slug = 'PostSlug';

    public static function getWidgets(): array
    {
        return [];
    }

    public function getIcon()
    {
        return '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.5 7.27783L12 12.0001M12 12.0001L3.49997 7.27783M12 12.0001L12 21.5001M21 16.0586V7.94153C21 7.59889 21 7.42757 20.9495 7.27477C20.9049 7.13959 20.8318 7.01551 20.7354 6.91082C20.6263 6.79248 20.4766 6.70928 20.177 6.54288L12.777 2.43177C12.4934 2.27421 12.3516 2.19543 12.2015 2.16454C12.0685 2.13721 11.9315 2.13721 11.7986 2.16454C11.6484 2.19543 11.5066 2.27421 11.223 2.43177L3.82297 6.54288C3.52345 6.70928 3.37369 6.79248 3.26463 6.91082C3.16816 7.01551 3.09515 7.13959 3.05048 7.27477C3 7.42757 3 7.59889 3 7.94153V16.0586C3 16.4013 3 16.5726 3.05048 16.7254C3.09515 16.8606 3.16816 16.9847 3.26463 17.0893C3.37369 17.2077 3.52345 17.2909 3.82297 17.4573L11.223 21.5684C11.5066 21.726 11.6484 21.8047 11.7986 21.8356C11.9315 21.863 12.0685 21.863 12.2015 21.8356C12.3516 21.8047 12.4934 21.726 12.777 21.5684L20.177 17.4573C20.4766 17.2909 20.6263 17.2077 20.7354 17.0893C20.8318 16.9847 20.9049 16.8606 20.9495 16.7254C21 16.5726 21 16.4013 21 16.0586Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getFields()
    {
        return [];
    }
}
```

## ./Commands/DatabaseToResources.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DatabaseToResources extends Command
{
    protected $description = 'Create resources based on existing database tables';

    protected $signature = 'aura:database-to-resources';

    public function handle()
    {
        $tables = $this->getAllTables();

        // dd($tables);

        foreach ($tables as $table) {
            if (in_array($table, ['migrations', 'failed_jobs', 'password_resets', 'settions'])) {
                continue;
            }

            $this->call('aura:transform-table-to-resource', ['table' => $table]);
        }

        $this->info('Resources generated successfully');
    }

    private function getAllTables()
    {
        return Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
    }
}
```

## ./Commands/MigrateFromPostsToCustomTable.php
```
<?php

namespace Aura\Base\Commands;

use ReflectionClass;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class MigrateFromPostsToCustomTable extends Command
{
    protected $signature = 'aura:migrate-from-posts-to-custom-table {resource?}';
    protected $description = 'Migrate resources from posts and meta tables to custom tables';

    public function handle()
    {
        // Step 1: Ask which resource to use
        $resources = Aura::getResources();
        $resourceOptions = [];

        foreach ($resources as $resourceClass) {
            $resourceInstance = new $resourceClass;
            $resourceName = $resourceInstance->name ?? class_basename($resourceClass);
            $resourceOptions[$resourceName] = $resourceClass;
        }

        $resourceName = select(
            'Which resource do you want to migrate?',
            array_keys($resourceOptions)
        );
        $resourceClass = $resourceOptions[$resourceName];

        // Step 2: Generate migration and modify resource
        info('Generating migration for resource: ' . $resourceName);
        $this->generateMigration($resourceClass);

        // Step 3: Ask if should run migration
        if (confirm('Do you want to run the migration now?', true)) {
            $this->call('migrate');
        }

        // Step 4: Ask if should transfer data
        if (confirm('Do you want to transfer data from posts and meta tables?', true)) {
            $this->call('aura:transfer-from-posts-to-custom-table', [
                'resource' => $resourceClass
            ]);
        }

        info('Migration process completed.');
    }

    protected function generateMigration($resourceClass)
    {
        // Reflect on the resource class
        $reflection = new ReflectionClass($resourceClass);
        $filePath = $reflection->getFileName();

        if (!file_exists($filePath)) {
            error('Resource class file not found: ' . $filePath);
            return;
        }

        $file = file_get_contents($filePath);

        // Add or update $customTable
        if (strpos($file, 'public static $customTable') === false) {
            $file = preg_replace(
                '/(class\s+' . $reflection->getShortName() . '\s+extends\s+\S+\s*{)/i',
                "$1\n    public static \$customTable = true;",
                $file
            );
        } else {
            $file = preg_replace(
                '/public\s+static\s+\$customTable\s*=\s*(?:true|false);/i',
                'public static $customTable = true;',
                $file
            );
        }

        // Add or update $table
        $resourceInstance = new $resourceClass;
        $modelClass = $resourceInstance->model ?? $resourceInstance->getModel();
        $tableName = Str::snake(Str::pluralStudly(class_basename($modelClass)));

        if (strpos($file, 'protected $table') === false) {
            $file = preg_replace(
                '/(class\s+' . $reflection->getShortName() . '\s+extends\s+\S+\s*{)/i',
                "$1\n    protected \$table = '$tableName';",
                $file
            );
        } else {
            $file = preg_replace(
                '/protected\s+\$table\s*=\s*[\'"].*?[\'"]\s*;/i',
                "protected \$table = '$tableName';",
                $file
            );
        }

        file_put_contents($filePath, $file);
        info('Modified resource class file: ' . $filePath);

        // dd($resourceClass); // double backslashes to $resourceClass

        // $resourceClass = str_replace('\\', '\\\\', $resourceClass);

        // Call the artisan command to create the migration
        $this->call('aura:create-resource-migration', [
            'resource' => $resourceClass,
        ]);

        info('Migration generated for resource: ' . $resourceClass);
    }
}
```

## ./Commands/UpdateSchemaFromMigration.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\select;

class UpdateSchemaFromMigration extends Command
{
    protected $description = 'Update the database schema based on the provided migration file';

    protected $signature = 'aura:schema-update {migration?}';

    public function handle()
    {
        $migrationFile = $this->argument('migration');

        if (! $migrationFile) {
            $migrationFiles = glob(database_path('migrations/*.php'));
            $migrationFile = select(
                label: 'Which migration file would you like to use?',
                options: array_combine($migrationFiles, array_map('basename', $migrationFiles))
            );
        }

        if (! file_exists($migrationFile)) {
            $this->error('Migration file does not exist.');

            return;
        }

        $table = $this->getTableNameFromMigration($migrationFile);

        if (! $table) {
            $this->error('Unable to determine table name from the migration.');

            return;
        }

        $existingColumns = DB::getSchemaBuilder()->getColumnListing($table);
        $desiredColumns = $this->getDesiredColumnsFromMigration($migrationFile);

        $newColumns = array_diff(array_keys($desiredColumns), $existingColumns);

        $dropColumns = array_diff($existingColumns, array_keys($desiredColumns));
        $dropColumns = array_diff($dropColumns, ['id', 'created_at', 'updated_at', 'deleted_at']);

        if (! Schema::hasTable($table)) {
            $this->info("Table '{$table}' does not exist. Running the migration...");

            // Run the migration
            Artisan::call('migrate');

            $this->info("Migration completed. Table '{$table}' has been created.");

            return;
        }

        // Add new columns
        Schema::table($table, function (Blueprint $table) use ($existingColumns, $desiredColumns) {
            $newColumns = array_diff(array_keys($desiredColumns), $existingColumns);

            foreach ($newColumns as $column) {

                $table->{$desiredColumns[$column]['type']}($column)->nullable();
            }

            // Drop outdated columns
            $dropColumns = array_diff($existingColumns, array_keys($desiredColumns));
            $dropColumns = array_diff($dropColumns, ['id', 'created_at', 'updated_at', 'deleted_at']);

            foreach ($dropColumns as $column) {
                $table->dropColumn($column);
            }
        });

        // Modify existing columns if needed
        Schema::table($table, function (Blueprint $table) use ($desiredColumns) {
            foreach ($desiredColumns as $column => $definition) {
                $table->{$definition['type']}($column)->nullable()->change();
            }
        });

        $this->info('Schema updated successfully based on the migration file.');
    }

    protected function getDesiredColumnsFromMigration($migrationFile)
    {
        $body = file($migrationFile);
        $upMethodStarted = false;
        $columns = [];

        foreach ($body as $line) {
            if (preg_match('/public function up\(\)/', $line)) {
                $upMethodStarted = true;

                continue;
            }

            if ($upMethodStarted) {
                if (preg_match('/\}/', $line)) {
                    break;
                }

                if (preg_match('/\$table->([a-zA-Z]+)\(\'([a-zA-Z0-9_]+)\'\)/', $line, $matches)) {
                    $columns[$matches[2]] = ['type' => $matches[1]];
                }
            }
        }

        return $columns;
    }

    protected function getTableNameFromMigration($migration)
    {
        $body = file($migration);

        foreach ($body as $line) {
            if (preg_match('/Schema::create\(\'([a-zA-Z0-9_]+)\'/', $line, $matches)) {
                return $matches[1];
            }
        }
    }
}
```

## ./Commands/CreateResourceMigration.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;

class CreateResourceMigration extends Command
{
    protected $description = 'Create a migration based on the fields of a resource';

    protected $files;

    protected $signature = 'aura:create-resource-migration {resource}';

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $resourceClass = $this->argument('resource');

        if (! class_exists($resourceClass)) {
            $this->error("Resource class '{$resourceClass}' not found.");

            return 1;
        }

        $resource = app($resourceClass);

        if (! method_exists($resource, 'getFields')) {
            $this->error("Method 'getFields' not found in the '{$resourceClass}' class.");

            return 1;
        }

        $tableName = Str::lower($resource->getPluralName());

        $migrationName = "create_{$tableName}_table";

        $baseFields = collect([
            [
                'name' => 'ID',
                'type' => 'Aura\\Base\\Fields\\ID',
                'slug' => 'id',
            ],
            // [
            //     'name' => 'Title',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'title',
            // ],
            // [
            //     'name' => 'Slug',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'slug',
            // ],
            // [
            //     'name' => 'Content',
            //     'type' => 'Aura\\Base\\Fields\\Textarea',
            //     'slug' => 'content',
            // ],
            // [
            //     'name' => 'Status',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'status',
            // ],
            // [
            //     'name' => 'Parent ID',
            //     'type' => 'Aura\\Base\\Fields\\ID',
            //     'slug' => 'parent_id',
            // ],
            // [
            //     'name' => 'Order',
            //     'type' => 'Aura\\Base\\Fields\\Number',
            //     'slug' => 'order',
            // ],

        ]);

        $fields = $resource->inputFields();

        $combined = $baseFields->merge($fields)->merge(collect([
            [
                'name' => 'User Id',
                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                'slug' => 'user_id',
            ],
            [
                'name' => 'Team Id',
                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                'slug' => 'team_id',
            ],
            [
                'name' => 'created_at',
                'type' => 'Aura\\Base\\Fields\\DateTime',
                'slug' => 'created_at',
            ],
            [
                'name' => 'updated_at',
                'type' => 'Aura\\Base\\Fields\\DateTime',
                'slug' => 'updated_at',
            ],
        ]));

        $combined = $combined->unique('slug');

        $schema = $this->generateSchema($combined);

        // dd($schema);

        if ($this->migrationExists($migrationName)) {
            //$this->error("Migration '{$migrationName}' already exists.");
            //return 1;
            $migrationFile = $this->getMigrationPath($migrationName);
        } else {
            Artisan::call('make:migration', [
                'name' => $migrationName,
                '--create' => $tableName,
            ]);

            $migrationFile = $this->getMigrationPath($migrationName);
        }

        if ($migrationFile === null) {
            $this->error("Unable to find migration file '{$migrationName}'.");

            return 1;
        }

        $content = $this->files->get($migrationFile);

        // Up method
        $pattern = '/(public function up\(\): void[\s\S]*?Schema::create\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
        $replacement = '${1}'.$schema.'${3}';
        $replacedContent = preg_replace($pattern, $replacement, $content);

        // Down method
        $down = "Schema::dropIfExists('{$tableName}');";
        $pattern = '/(public function down\(\): void[\s\S]*?{)[\s\S]*?Schema::table\(.*?function \(Blueprint \$table\) \{[\s\S]*?\/\/[\s\S]*?\}\);[\s\S]*?\}/';
        $replacement = '${1}'.PHP_EOL.'    '.$down.PHP_EOL.'}';
        $replacedContent2 = preg_replace($pattern, $replacement, $replacedContent);

        $this->files->put($migrationFile, $replacedContent2);

        $this->info("Migration '{$migrationName}' created successfully.");

        // Run "pint" on the migration file
        $this->runPint($migrationFile);
    }

    protected function generateColumn($field)
    {
        $fieldInstance = app($field['type']);
        $columnType = $fieldInstance->tableColumnType;

        $column = "\$table->{$columnType}('{$field['slug']}')";

        if ($fieldInstance->tableNullable) {
            $column .= '->nullable()';
        }

        return $column.";\n";
    }

    protected function generateSchema($fields)
    {
        $schema = '';

        // Maybe custom Schema instead of Fields?
        // $schema .= "$table->id();\n";

        foreach ($fields as $field) {
            $schema .= $this->generateColumn($field);
        }

        return $schema;
    }

    protected function getMigrationPath($name)
    {
        $migrationFiles = $this->files->glob(database_path('migrations/*.php'));
        $name = Str::snake($name);

        foreach ($migrationFiles as $file) {
            if (strpos($file, $name) !== false) {
                return $file;
            }
        }
    }

    protected function migrationExists($name)
    {
        $migrationFiles = $this->files->glob(database_path('migrations/*.php'));
        $name = Str::snake($name);

        foreach ($migrationFiles as $file) {
            if (strpos($file, $name) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function runPint($migrationFile)
    {
        return;
        $command = [
            (new ExecutableFinder)->find('php', 'php', [
                '/usr/local/bin',
                '/opt/homebrew/bin',
            ]),

            'vendor/bin/pint', $migrationFile,
        ];

        $result = Process::path(base_path())->run($command);
    }
}
```

## ./Commands/CreateResourcePermissions.php
```
<?php

namespace Aura\Base\Commands;

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CreateResourcePermissions extends Command
{
    protected $description = 'Create permissions for all resources';

    protected $signature = 'aura:create-resource-permissions';

    public function handle()
    {
        // Permissions
        foreach (Aura::getResources() as $resource) {
            $r = app($resource);

            $this->info('Creating missing permissions for '.$r->pluralName().'...');

            // login user 1
            Auth::loginUsingId(1);

            Permission::firstOrCreate(
                ['slug' => 'view-'.$r::$slug],
                [
                    'title' => 'View '.$r->pluralName(),
                    'name' => 'View '.$r->pluralName(),
                    'slug' => 'view-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'viewAny-'.$r::$slug],
                [
                    'title' => 'View Any '.$r->pluralName(),
                    'name' => 'View Any '.$r->pluralName(),
                    'slug' => 'viewAny-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'create-'.$r::$slug],
                [
                    'title' => 'Create '.$r->pluralName(),
                    'name' => 'Create '.$r->pluralName(),
                    'slug' => 'create-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'update-'.$r::$slug],
                [
                    'title' => 'Update '.$r->pluralName(),
                    'name' => 'Update '.$r->pluralName(),
                    'slug' => 'update-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'restore-'.$r::$slug],
                [
                    'title' => 'Restore '.$r->pluralName(),
                    'name' => 'Restore '.$r->pluralName(),
                    'slug' => 'restore-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'delete-'.$r::$slug],
                [
                    'title' => 'Delete '.$r->pluralName(),
                    'name' => 'Delete '.$r->pluralName(),
                    'slug' => 'delete-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'forceDelete-'.$r::$slug],
                [
                    'title' => 'Force Delete '.$r->pluralName(),
                    'name' => 'Force Delete '.$r->pluralName(),
                    'slug' => 'forceDelete-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'scope-'.$r::$slug],
                [
                    'title' => 'Scope '.$r->pluralName(),
                    'name' => 'Scope '.$r->pluralName(),
                    'slug' => 'scope-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );
        }

        $this->info('Resource permissions created successfully');
    }
}
```

## ./Commands/PublishCommand.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all of the Aura resources';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:publish';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $assetPath = public_path('vendor/aura/assets');

        if (File::exists($assetPath)) {
            File::deleteDirectory($assetPath);
        }

        $this->call('vendor:publish', [
            '--tag' => 'aura-assets',
            '--force' => true,
        ]);
    }
}
```

## ./Commands/TransferFromPostsToCustomTable.php
```
<?php

namespace Aura\Base\Commands;

use ReflectionClass;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class TransferFromPostsToCustomTable extends Command
{
    protected $signature = 'aura:transfer-from-posts-to-custom-table {resource?}';
    protected $description = 'Transfer resources from posts and meta tables to custom tables';

    public function handle()
    {
        // Get resource class from argument or prompt user to select
        $resourceClass = $this->argument('resource');

        if ($resourceClass) {
            // Validate that the provided resource class exists
            if (!class_exists($resourceClass)) {
                error("Resource class '{$resourceClass}' does not exist.");
                return Command::FAILURE;
            }
        } else {
            // Step 1: Ask which resource to use if not provided
            $resources = Aura::getResources();
            $resourceOptions = [];

            foreach ($resources as $resourceClass) {
                $resourceInstance = new $resourceClass;
                $resourceName = $resourceInstance->name ?? class_basename($resourceClass);
                $resourceOptions[$resourceName] = $resourceClass;
            }

            $resourceName = select(
                'Which resource do you want to migrate?',
                array_keys($resourceOptions)
            );
            $resourceClass = $resourceOptions[$resourceName];
        }

        $this->transferData($resourceClass);
        info('Transfer process completed.');
    }

    protected function transferData($resourceClass)
    {
        $resourceInstance = new $resourceClass;
        $type = $resourceInstance->getType();

        info('Transferring data from posts to: ' . $resourceClass);

        // Fetch posts of the specific type along with their meta data
        $posts = DB::table('posts')->where('type', $type)->get();

        // Initialize progress bar
        $this->output->progressStart($posts->count());

        foreach ($posts as $post) {

            // Get all meta for this post
            $metas = DB::table('meta')
                ->where('metable_type', get_class($resourceInstance))
                ->where('metable_id', $post->id)
                ->get();

            // Prepare record data combining post and meta fields
            $newRecord = [
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
                'user_id' => $post->user_id,
                'team_id' => $post->team_id,
            ];

            foreach ($metas as $meta) {
                $newRecord[$meta->key] = $meta->value;
            }

            // Create new record using the resource
            app($resourceClass)->create($newRecord);

            // Advance the progress bar
            $this->output->progressAdvance();
        }

        // Finish the progress bar
        $this->output->progressFinish();

        info('Data transfer completed.');
    }
}
```

## ./Commands/AuraLayoutCommand.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuraLayoutCommand extends Command
{
    protected $description = 'Copy Aura layout file to the project for customization';

    protected $signature = 'aura:layout';

    public function handle()
    {
        $sourcePath = 'vendor/eminiarts/aura/resources/views/components/layout/app.blade.php';
        $destinationPath = 'resources/views/vendor/aura/components/layout/app.blade.php';

        if (! File::exists($sourcePath)) {
            $this->error('Aura layout file not found. Make sure the Aura package is installed.');

            return 1;
        }

        File::ensureDirectoryExists(dirname($destinationPath));

        try {
            File::copy($sourcePath, $destinationPath);
            $this->info('Aura layout file copied successfully.');
            $this->info("You can now customize the layout at: $destinationPath");
        } catch (\Exception $e) {
            $this->error('Failed to copy Aura layout file: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
```

## ./Commands/TransformTableToResource.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TransformTableToResource extends Command
{
    protected $description = 'Create a resource based on a specific database table';

    protected $signature = 'aura:transform-table-to-resource {table}';

    public function handle()
    {
        $table = $this->argument('table');
        $columns = Schema::getColumnListing($table);
        $resourceName = Str::studly(Str::singular($table));

        $fields = $this->generateFields($columns);

        $resourceContent = $this->generateResourceContent($resourceName, $fields);
        $this->saveResourceFile($resourceName, $resourceContent);

        $this->info("Resource {$resourceName} generated successfully");
    }

    private function generateFields(array $columns): array
    {
        $fields = [];

        // Add your custom column to field type mapping logic here
        foreach ($columns as $column) {
            $columnType = Schema::getColumnType($this->argument('table'), $column);
            $fieldType = $this->getFieldTypeFromColumnType($columnType);

            $fields[] = [
                'name' => ucfirst($column),
                'slug' => $column,
                'type' => $fieldType,
                'validation' => '',
            ];
        }

        return $fields;
    }

    private function generateResourceContent(string $resourceName, array $fields): string
    {
        $fieldsContent = '';

        foreach ($fields as $field) {
            $fieldsContent .= "            [\n";
            $fieldsContent .= "                'name' => '{$field['name']}',\n";
            $fieldsContent .= "                'slug' => '{$field['slug']}',\n";
            $fieldsContent .= "                'type' => '{$field['type']}',\n";
            $fieldsContent .= "                'validation' => '{$field['validation']}',\n";
            $fieldsContent .= "            ],\n";
        }

        return <<<EOT
<?php

namespace App\Aura\Resources;

use Aura\Base\Models\Post;

class {$resourceName} extends Resource
{
    public static string \$type = '{$resourceName}';

    public static ?string \$slug = '{$resourceName}';

    public static function getWidgets(): array
    {
        return [];
    }

    public function getIcon()
    {
        return '<svg class="w-5 h-5" viewBox="0 0 18 18" fill="none" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 9a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getFields()
    {
        return [
{$fieldsContent}
        ];
    }
}

EOT;
    }

    private function getFieldTypeFromColumnType(string $columnType): string
    {
        switch ($columnType) {
            case 'text':
            case 'longtext':
                return 'Aura\\Base\\Fields\\Textarea';
            case 'integer':
            case 'float':
            case 'double':
                return 'Aura\\Base\\Fields\\Number';
            case 'date':
                return 'Aura\\Base\\Fields\\Date';
                // Add more cases as needed
            default:
                return 'Aura\\Base\\Fields\\Text';
        }
    }

    private function saveResourceFile(string $resourceName, string $resourceContent)
    {
        $filesystem = new Filesystem;

        $resourcesDirectory = app_path('Aura/Resources');
        if (! $filesystem->exists($resourcesDirectory)) {
            $filesystem->makeDirectory($resourcesDirectory, 0755, true);
        }

        $resourceFile = "{$resourcesDirectory}/{$resourceName}.php";

        if ($filesystem->exists($resourceFile)) {
            $this->error("Resource file '{$resourceName}.php' already exists.");

            return;
        }

        $filesystem->put($resourceFile, $resourceContent);
    }
}
```

## ./Commands/CreateAuraPlugin.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateAuraPlugin extends Command
{
    protected $description = 'Create a new Aura plugin';

    protected $signature = 'aura:plugin {name?}';

    public function getStubsDirectory($path)
    {
        return __DIR__.'/../../stubs/'.$path;
    }

    public function handle()
    {

        if ($this->argument('name')) {

            $vendorAndName = $this->argument('name');

        } else {

            $vendorAndName = text(
                label: 'What is the name of your plugin?',
                placeholder: 'E.g. aura/plugin (vendor/name)',
            );

        }

        [$vendor, $name] = explode('/', $vendorAndName);

        $pluginType = select(
            label: 'What type of plugin do you want to create?',
            options: [
                'plugin' => 'Complete plugin',
                'plugin-resource' => 'Resource plugin',
                'plugin-field' => 'Field plugin',
                'plugin-widget' => 'Widget plugin',
            ],
            default: 'plugin',
        );

        $pluginDirectory = base_path("plugins/{$vendor}/{$name}");
        File::makeDirectory($pluginDirectory, 0755, true);

        $stubDirectory = $this->getStubsDirectory($pluginType);

        File::copyDirectory($stubDirectory, $pluginDirectory);

        $this->line("{$pluginType} created at {$pluginDirectory}");

        $this->line('Replacing placeholders...');
        // $this->runProcess("php {$pluginDirectory}/configure.php --vendor={$vendor} --name={$name}");

        $result = Process::path($pluginDirectory)->run("php ./configure.php --vendor={$vendor} --name={$name}");

        $this->line($result->output());

        if ($this->confirm('Do you want to append '.str($name)->title().'ServiceProvider to config/app.php?')) {
            $providerClassName = str($name)->title().'ServiceProvider';
            $configFile = base_path('config/app.php');
            $configContent = File::get($configFile);
            $newProvider = str($vendor)->title().'\\'.str($name)->title()."\\{$providerClassName}::class";
            $configContent = str_replace("App\Providers\AppServiceProvider::class,", "{$newProvider},\n\n        App\Providers\AppServiceProvider::class,", $configContent);
            File::put($configFile, $configContent);
            $this->line("{$providerClassName} added to config/app.php");
        }

        $this->line('Updating composer.json...');
        $composerJsonFile = base_path('composer.json');
        $composerJson = json_decode(File::get($composerJsonFile), true);
        $composerJson['autoload']['psr-4'][ucfirst($vendor).'\\'.ucfirst($name).'\\']
        = "plugins/{$vendor}/{$name}/src";
        File::put($composerJsonFile, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line('composer.json updated');

        $this->line('composer dump-autoload...');

        Process::run('composer dump-autoload');

        $this->line('Plugin created successfully!');
    }
}
```

## ./Commands/CustomizeComponent.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;

class CustomizeComponent extends Command
{
    protected $description = 'Customize a component for a specific resource';

    protected $signature = 'aura:customize-component';

    public function handle()
    {
        $componentType = select(
            label: 'Which component would you like to customize?',
            options: ['Index', 'Create', 'Edit', 'View'],
            default: 'Edit'
        );

        $resources = collect(app('aura')::getResources())->mapWithKeys(function ($resource) {
            return [$resource => class_basename($resource)];
        });

        $resourceOptions = collect($resources)->mapWithKeys(function ($resourceName, $resourceClass) {
            return [$resourceClass => $resourceName];
        })->toArray();

        $resourceClass = select(
            label: 'For which resource?',
            options: $resourceOptions,
            scroll: 10
        );

        // dd($resourceClass, $resources);

        $resourceName = $resources[$resourceClass];

        $this->createCustomComponent($componentType, $resourceClass, $resourceName);
        $this->updateRoute($componentType, $resourceClass, $resourceName);

        $this->components->info("Custom {$componentType} component for {$resourceName} has been created and route has been updated.");
    }

    protected function createCustomComponent($componentType, $resourceClass, $resourceName)
    {
        $componentName = "{$componentType}{$resourceName}";

        $stubPath = __DIR__.'/Stubs/livewire.custom.stub';
        $componentPath = app_path("Livewire/{$componentName}.php");

        $stub = file_get_contents($stubPath);
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ baseClass }}', '{{ componentType }}', '{{ resourceClass }}'],
            ['App\\Livewire', $componentName, "Aura\\Base\\Livewire\\Resource\\{$componentType}", $componentType, $resourceName],
            $stub
        );

        // Ensure the directory exists
        $directory = dirname($componentPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($componentPath, $stub);

        $this->components->info("Created component: {$componentName}");
    }

    protected function updateRoute($componentType, $resourceClass, $resourceName)
    {
        $routeFile = base_path('routes/web.php');
        $routeContents = file_get_contents($routeFile);

        $resourceSlug = Str::kebab($resourceName);

        $newRoute = "Route::get('admin/{$resourceSlug}/{id}/".Str::lower($componentType)."', App\\Livewire\\{$componentType}{$resourceName}::class)->name('{$resourceSlug}.".Str::lower($componentType)."');";

        // Append the new route to the end of the file
        $updatedContents = $routeContents."\n".$newRoute;

        file_put_contents($routeFile, $updatedContents);

        $this->components->info("Added new route for: {$componentType}{$resourceName}");
    }
}
```

## ./Commands/Installation.php
```
<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeUser extends Command
{
    protected $description = 'Creates an Aura Super Admin.';

    protected $signature = 'aura:user
                            {--name= : The name of the user}
                            {--email= : A valid email address}
                            {--password= : The password for the user}';

    public function handle(): int
    {
        $name = $this->option('name') ?? text('What is your name?');
        $email = $this->option('email') ?? text('What is your email?');
        $password = $this->option('password') ?? password('What is your password?');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'fields' => [
                'password' => $password,
            ],
        ]);

        if (config('aura.teams')) {
            DB::table('teams')->insert([
                'name' => $name,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $team = Team::first();
            $user->current_team_id = $team->id;
            $user->save();
        }

        auth()->loginUsingId($user->id);

        $roleData = [
            'name' => 'Super Admin',
            'slug' => 'super_admin',
            'description' => 'Super Admin can perform everything.',
            'super_admin' => true,
            'permissions' => [],
            'user_id' => $user->id,
            'team_id' => $team->id ?? null,
        ];

        if (config('aura.teams')) {
            $roleData['team_id'] = $team->id;
        }

        $role = Role::create($roleData);

        $user->update(['roles' => [$role->id]]);

        $this->info('User created successfully.');

        return static::SUCCESS;
    }
}
```

## ./Commands/InstallConfigCommand.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use Symfony\Component\Process\Process;

class InstallConfigCommand extends Command
{
    public $description = 'Install Aura Configuration';

    public $signature = 'aura:install-config';

    public function handle(): int
    {
        // 1. Do you want to use teams?
        $useTeams = confirm('Do you want to use teams?');

        // Get the config path
        $configPath = config_path('aura.php');

        // Include the config array
        $config = include $configPath;

        // Modify the 'teams' value
        $config['teams'] = $useTeams;

        // 2. Do you want to modify default features?
        $modifyFeatures = confirm('Do you want to modify the default features?');

        if ($modifyFeatures) {
            // For each feature, ask if they want to enable/disable it
            $features = $config['features'];

            foreach ($features as $feature => $value) {
                $features[$feature] = confirm("Enable feature '{$feature}'?", $value);
            }

            // Update the features in config
            $config['features'] = $features;
        }

        // 3. Do you want to allow registration?
        $allowRegistration = confirm('Do you want to allow registration?');

        // Update the env variable AURA_REGISTRATION
        $this->setEnvValue('AURA_REGISTRATION', $allowRegistration ? 'true' : 'false');

        // 4. Do you want to modify the default theme?
        $modifyTheme = confirm('Do you want to modify the default theme?');

        if ($modifyTheme) {
            $theme = $config['theme'];

            foreach ($theme as $option => $currentValue) {

                if (in_array($option, ['login-bg', 'login-bg-darkmode', 'app-favicon', 'app-favicon-darkmode', 'sidebar-darkmode-type'])) {
                    continue;
                }

                if ($option == 'color-palette') {
                    $choices = ['aura', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 'mountain-meadow', 'sandal', 'slate', 'gray', 'zinc', 'neutral', 'stone'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'gray-color-palette') {
                    $choices = ['slate', 'purple-slate', 'gray', 'zinc', 'neutral', 'stone', 'blue', 'smaragd', 'dark-slate', 'blackout'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'darkmode-type') {
                    $choices = ['auto', 'light', 'dark'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'sidebar-size') {
                    $choices = ['standard', 'compact'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'sidebar-type') {
                    $choices = ['primary', 'light', 'dark'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif (is_bool($currentValue)) {
                    // Boolean option
                    $theme[$option] = confirm("Enable '{$option}'?", $currentValue);
                } else {
                    // For other options, just ask for the value
                    $theme[$option] = text(
                        label: "Enter value for '{$option}':",
                        default: $currentValue
                    );
                }
            }

            // Update the theme in config
            $config['theme'] = $theme;
        }

        // Now, write back the config file
        $arrayExport = var_export($config, true);
        
        // Remove numeric array keys
        $arrayExport = preg_replace("/[0-9]+ => /", "", $arrayExport);
        
        $code = '<?php' . PHP_EOL . PHP_EOL . 'return ' . str_replace(
            ['array (', ')', "[\n    ]"],
            ['[', ']', '[]'],
            $arrayExport
        ) . ';' . PHP_EOL;
        
        file_put_contents($configPath, $code);

        $this->info('Aura configuration has been updated.');

         // Run Pint on the file after the file has been written
        $process = new Process(['vendor/bin/pint', $configPath]);
        $process->run();

        // Check if the process was successful
        if (!$process->isSuccessful()) {
            $this->error('Pint formatting failed: ' . $process->getErrorOutput());
        } else {
            $this->info('Pint formatting completed.');
        }
        
    

        return self::SUCCESS;
    }

    private function setEnvValue($key, $value)
    {
        $envPath = base_path('.env');

        if (file_exists($envPath)) {
            // Read the .env file
            $env = file_get_contents($envPath);

            // Replace the value
            $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
            $replacement = $key . '=' . $value;

            if (preg_match($pattern, $env)) {
                // Replace existing value
                $env = preg_replace($pattern, $replacement, $env);
            } else {
                // Add new value
                $env .= PHP_EOL . $replacement;
            }

            // Write back to the .env file
            file_put_contents($envPath, $env);
        }
    }
}
```

## ./Commands/ExtendUserModel.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ExtendUserModel extends Command
{
    protected $description = 'Extend the User model with AuraUser';

    protected $signature = 'aura:extend-user-model';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $filesystem = new Filesystem;
        $userModelPath = app_path('Models/User.php');

        if ($filesystem->exists($userModelPath)) {
            $content = $filesystem->get($userModelPath);

            if (strpos($content, 'extends AuraUser') === false) {
                if ($this->confirm('Do you want to extend the User model with AuraUser?', true)) {
                    // Remove any incorrect `use` statements and add the correct one
                    $content = preg_replace('/use .+Aura\\\\Base\\\\Resources\\\\User as AuraUser;/m', '', $content);
                    $content = str_replace('extends Authenticatable', 'extends AuraUser', $content);
                    $content = preg_replace('/^namespace [^;]+;/m', "$0\nuse Aura\\Base\\Resources\\User as AuraUser;", $content);

                    $filesystem->put($userModelPath, $content);

                    $this->info('User model successfully extended with AuraUser.');
                } else {
                    $this->info('User model extension cancelled.');
                }
            } else {
                $this->info('User model already extends AuraUser.');
            }
        } else {
            $this->error('User model not found.');
        }
    }
}
```

## ./Commands/MakeField.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeField extends GeneratorCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Aura Field';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:field {name}';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Field';

    public function handle()
    {
        parent::handle();

        $this->createViewFile();
        $this->createEditFile();

        $this->info('Field created successfully.');
    }

    protected function buildEditFileContents()
    {
        $contents = $this->files->get(__DIR__.'/Stubs/make-field-edit.stub');

        // replace :fieldSlug with the actual slug
        $contents = str_replace(':fieldSlug', str($this->argument('name'))->slug(), $contents);

        return $contents;
    }

    protected function buildViewFileContents()
    {
        return $this->files->get(__DIR__.'/Stubs/make-field-view.stub');
    }

    protected function createEditFile()
    {
        $name = $this->argument('name');
        $slug = str($name)->slug();

        $path = resource_path('views/components/fields/'.$slug.'.blade.php');

        if (! $this->files->exists(dirname($path))) {
            // create the directory if it doesn't exist
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        if (! $this->files->exists($path)) {
            $this->files->put($path, $this->buildEditFileContents());
        }
    }

    protected function createViewFile()
    {
        $name = $this->argument('name');
        $slug = str($name)->slug();

        $path = resource_path('views/components/fields/'.$slug.'-view.blade.php');

        if (! $this->files->exists(dirname($path))) {
            // create the directory if it doesn't exist
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        if (! $this->files->exists($path)) {
            $this->files->put($path, $this->buildViewFileContents());
        }
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Aura\Fields';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/Stubs/make-field.stub';
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $stub = str_replace('FieldName', ucfirst($this->argument('name')), $stub);
        $stub = str_replace('FieldSlug', str($this->argument('name'))->slug(), $stub);

        return $stub;
    }
}
```

## ./Commands/CreateResourceFactory.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\search;

class CreateResourceFactory extends Command
{
    protected $description = 'Create a factory based on the fields of a resource';

    protected $files;

    protected $signature = 'aura:create-resource-factory {resource?}';

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $resourceClass = $this->argument('resource');

        if (! $resourceClass) {
            $resources = collect(\Aura\Base\Facades\Aura::getResources())->mapWithKeys(function ($resource) {
                return [$resource => $resource];
            });

            $resourceClass = search(
                'Search for the resource you want to create a factory for',
                fn (string $value) => strlen($value) > 0
                    ? $resources->filter(function ($resource) use ($value) {
                        return str_contains(strtolower($resource), strtolower($value));
                    })->all()
                    : $resources->all()
            );
        }

        if (! class_exists($resourceClass)) {
            $this->error("Resource class '{$resourceClass}' not found.");

            return 1;
        }

        $resource = app($resourceClass);

        if (! method_exists($resource, 'getFields')) {
            $this->error("Method 'getFields' not found in the '{$resourceClass}' class.");

            return 1;
        }

        $modelName = class_basename($resourceClass);
        $factoryName = "{$modelName}Factory";

        // Create the factory file
        Artisan::call('make:factory', [
            'name' => $factoryName,
            '--model' => $modelName,
        ]);

        $factoryPath = database_path("factories/{$factoryName}.php");

        if (! $this->files->exists($factoryPath)) {
            $this->error("Unable to create factory file '{$factoryName}'.");

            return 1;
        }

        // Generate factory content
        $factoryContent = $this->generateFactoryContent($resourceClass, $modelName);

        // Update the factory file
        $this->files->put($factoryPath, $factoryContent);

        $this->info("Factory '{$factoryName}' created successfully.");

        // Inform the user about adding the newFactory method to the Resource
        $this->info("Don't forget to add the following method to your {$modelName} Resource:");
        $this->info("
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return {$factoryName}::new();
    }
        ");

    }

    protected function generateFactoryContent($resource, $modelName)
    {
        $fields = app($resource)->getFields();
        $factoryDefinition = $this->generateFactoryDefinition($fields);

        return <<<PHP
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use {$resource};

class {$modelName}Factory extends Factory
{
    protected \$model = {$modelName}::class;

    public function definition()
    {
        return [
{$factoryDefinition}
        ];
    }
}
PHP;
    }

    protected function generateFactoryDefinition($fields)
    {
        $definition = '';

        foreach ($fields as $field) {
            $faker = $this->getFakerMethod($field);
            $definition .= "            '{$field['slug']}' => {$faker},\n";
        }

        return rtrim($definition);
    }

    protected function getFakerMethod($field)
    {
        $type = class_basename($field['type']);

        switch ($type) {
            case 'Text':
                return '$this->faker->sentence';
            case 'Textarea':
                return '$this->faker->paragraph';
            case 'Email':
                return '$this->faker->unique()->safeEmail';
            case 'Number':
                return '$this->faker->randomNumber()';
            case 'DateTime':
                return '$this->faker->dateTime()';
            case 'Date':
                return '$this->faker->date()';
            case 'Boolean':
                return '$this->faker->boolean';
            case 'Select':
            case 'Radio':
                // Assuming options are available, adjust if necessary
                return '$this->faker->randomElement(["option1", "option2", "option3"])';
            case 'BelongsTo':
                $relatedModel = class_basename($field['resource']);

                return "\\{$field['resource']}::factory()";
            default:
                return '$this->faker->word';
        }
    }
}
```

## ./Commands/MigratePostMetaToMeta.php
```
<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Post;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class MigratePostMetaToMeta extends Command
{
    protected $description = 'Migrate post_meta to meta';

    protected $signature = 'aura:migrate-post-meta-to-meta';

    public function handle()
    {
        $this->info('Starting migration of post_meta, team_meta, and user_meta to meta table...');

        if (!Schema::hasTable('meta')) {
            Schema::create('meta', function (Blueprint $table) {
                $table->id();
                $table->morphs('metable');
                $table->string('key')->nullable()->index();
                $table->longText('value')->nullable();
                $table->index(['metable_type', 'metable_id', 'key']);
            });
        }

        // Migrate post_meta
        $postMeta = DB::table('post_meta')->get();
        foreach ($postMeta as $meta) {
            $post = DB::table('posts')->where('id', $meta->post_id)->first();
            $type = $post->type;
            $metableType = \Aura\Base\Facades\Aura::findResourceBySlug($type);

            // dd($metableType::class);

            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->post_id,
                'metable_type' => $metableType::class,
            ]);
        }
        $this->info('Migrated post_meta to meta table.');

        // Migrate team_meta
        $teamMeta = DB::table('team_meta')->get();
        foreach ($teamMeta as $meta) {
            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->team_id,
                'metable_type' => Team::class,
            ]);
        }
        $this->info('Migrated team_meta to meta table.');

        // Migrate user_meta
        $userMeta = DB::table('user_meta')->get();
        foreach ($userMeta as $meta) {
            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->user_id,
                'metable_type' => User::class,
            ]);
        }
        $this->info('Migrated user_meta to meta table.');

        $this->info('Migration completed successfully.');
    }
}
```

## ./Commands/MakeUser.php
```
<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeUser extends Command
{
    protected $description = 'Creates an Aura Super Admin.';

    protected $signature = 'aura:user
                            {--name= : The name of the user}
                            {--email= : A valid email address}
                            {--password= : The password for the user}';

    public function handle(): int
    {
        $name = $this->option('name') ?? text('What is your name?');
        $email = $this->option('email') ?? text('What is your email?');
        $password = $this->option('password') ?? password('What is your password?');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'fields' => [
                'password' => $password,
            ],
        ]);

        if (config('aura.teams')) {
            DB::table('teams')->insert([
                'name' => $name,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $team = Team::first();
            $user->current_team_id = $team->id;
            $user->save();
        }

        auth()->loginUsingId($user->id);

        $roleData = [
            'name' => 'Super Admin',
            'slug' => 'super_admin',
            'description' => 'Super Admin can perform everything.',
            'super_admin' => true,
            'permissions' => [],
            'user_id' => $user->id,
            'team_id' => $team->id ?? null,
        ];

        if (config('aura.teams')) {
            $roleData['team_id'] = $team->id;
        }

        $role = Role::create($roleData);

        $user->update(['roles' => [$role->id]]);

        $this->info('User created successfully.');

        return static::SUCCESS;
    }
}
```

## ./Commands/MakeResource.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeResource extends GeneratorCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Aura Resource';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:resource {name} {--custom}';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Aura\Resources';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('custom')) {
            return __DIR__.'/Stubs/make-custom-resource.stub';
        }

        return __DIR__.'/Stubs/make-resource.stub';
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $stub = str_replace('PostName', ucfirst($this->argument('name')), $stub);
        $stub = str_replace('PostSlug', str($this->argument('name'))->slug(), $stub);
        $stub = str_replace('post_slug', str($this->argument('name'))->snake()->plural(), $stub);

        return $stub;
    }
}
```

## ./Jobs/GenerateAllResourcePermissions.php
```
<?php

namespace Aura\Base\Jobs;

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Team;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateAllResourcePermissions
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    private $teamId;

    public function __construct(?int $teamId = null)
    {
        $this->teamId = $teamId ?? auth()->user()?->current_team_id;
    }

    public function handle()
    {
        $resources = collect(Aura::getResources())->filter(function ($resource) {
            try {
                $resourceInstance = app($resource);

                return is_subclass_of($resourceInstance, Resource::class) &&
                       ! is_a($resourceInstance, Team::class) &&
                       ! is_subclass_of($resourceInstance, Team::class);
            } catch (\Throwable $e) {
                Log::warning("Resource class not found: $resource");

                return false;
            }
        });

        DB::transaction(function () use ($resources) {
            foreach ($resources as $resource) {
                $this->generatePermissionsForResource(app($resource));
            }
        });
    }

    private function generatePermissionsForResource(Resource $resource)
    {
        $permissions = [
            'view' => "View {$resource->pluralName()}",
            'viewAny' => "View Any {$resource->pluralName()}",
            'create' => "Create {$resource->pluralName()}",
            'update' => "Update {$resource->pluralName()}",
            'restore' => "Restore {$resource->pluralName()}",
            'delete' => "Delete {$resource->pluralName()}",
            'forceDelete' => "Force Delete {$resource->pluralName()}",
            'scope' => "Scope {$resource->pluralName()}",
        ];

        foreach ($permissions as $action => $name) {
            try {
                Permission::withoutGlobalScopes()->updateOrCreate(
                    [
                        'slug' => "{$action}-{$resource::$slug}",
                        'team_id' => $this->teamId,
                    ],
                    [
                        'name' => $name,
                        'group' => $resource->pluralName(),
                    ]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Check if it's a duplicate entry error
                Log::error($e->getMessage());
            }
        }
    }
}
```

## ./Jobs/GenerateImageThumbnail.php
```
<?php

namespace Aura\Base\Jobs;

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Attachment;
use Aura\Base\Services\ThumbnailGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateImageThumbnail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The attachment to generate a thumbnail for.
     *
     * @var Attachment
     */
    public $attachment;

    /**
     * Create a new job instance.
     */
    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Execute the job.
     */
    public function handle(ThumbnailGenerator $thumbnailGenerator)
    {
        // Skip in tests
        if (app()->environment('testing')) {
            return;
        }

        // Get the settings from aura and check which thumbnail sizes are enabled
        $settings = Aura::option('media');

        if (!$settings || !($settings['generate_thumbnails'] ?? false)) {
            return;
        }

        // Get the path from the fields array
        $relativePath = $this->attachment->fields['url'] ?? null;
        if (empty($relativePath)) {
            logger()->error('Empty attachment URL', [
                'attachment' => $this->attachment->toArray()
            ]);
            return;
        }

        logger()->info('Processing attachment', [
            'relativePath' => $relativePath
        ]);

        // Generate thumbnails for each configured size
        foreach ($settings['dimensions'] as $thumbnail) {
            try {
                logger()->info('Generating thumbnail', [
                    'relativePath' => $relativePath,
                    'size' => $thumbnail
                ]);

                $width = $thumbnail['width'] ?? null;
                $height = $thumbnail['height'] ?? null;

                if ($width === null) {
                    throw new \InvalidArgumentException("Width is not defined for thumbnail size: " . ($thumbnail['name'] ?? 'unknown'));
                }

                $thumbnailGenerator->generate(
                    $relativePath,
                    $width,
                    $height
                );

            } catch (\Exception $e) {
                logger()->error('Failed to generate thumbnail', [
                    'size' => $thumbnail,
                    'path' => $relativePath,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
```

## ./Jobs/GenerateResourcePermissions.php
```
<?php

namespace Aura\Base\Jobs;

use Aura\Base\Resources\Permission;
use Aura\Base\Resources\resource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateResourcePermissions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The resource to generate the permissions for.
     *
     * @var resource
     */
    public $resource;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $r = app($this->resource);

        Permission::firstOrCreate(
            ['slug' => 'view-'.$r::$slug],
            [
                'name' => 'View '.$r->pluralName(),
                'slug' => 'view-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'viewAny-'.$r::$slug],
            [
                'name' => 'View Any '.$r->pluralName(),
                'slug' => 'viewAny-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'create-'.$r::$slug],
            [
                'name' => 'Create '.$r->pluralName(),
                'slug' => 'create-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'update-'.$r::$slug],
            [
                'name' => 'Update '.$r->pluralName(),
                'slug' => 'update-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'restore-'.$r::$slug],
            [
                'name' => 'Restore '.$r->pluralName(),
                'slug' => 'restore-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'delete-'.$r::$slug],
            [
                'name' => 'Delete '.$r->pluralName(),
                'slug' => 'delete-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'forceDelete-'.$r::$slug],
            [
                'name' => 'Force Delete '.$r->pluralName(),
                'slug' => 'forceDelete-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'scope-'.$r::$slug],
            [
                'name' => 'Scope '.$r->pluralName(),
                'slug' => 'scope-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
        );
    }
}
```

## ./DynamicFunctions.php
```
<?php

namespace Aura\Base;

class DynamicFunctions
{
    public $closures = [];

    public function add($callback)
    {
        $reflection = new \ReflectionFunction($callback);
        $file = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();
        $hash = md5("{$file}_{$startLine}_{$endLine}");

        $this->closures[$hash][] = $callback;

        return $hash;
    }

    public function call($hash)
    {
        if (! isset($this->closures[$hash])) {
            throw new \Exception("No registered closures for hash: {$hash}");
        }

        foreach ($this->closures[$hash] as $callback) {
            return $callback();
        }
    }
}
```

## ./Events/SaveFields.php
```
<?php

namespace Aura\Base\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaveFields
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fields;

    public $model;

    public $oldFields;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $fields, $oldFields, $model)
    {
        $this->fields = $fields;
        $this->oldFields = $oldFields;
        $this->model = $model;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
```

## ./Resource.php
```
<?php

namespace Aura\Base;

use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\Scopes\TypeScope;
use Aura\Base\Traits\AuraModelConfig;
use Aura\Base\Traits\InitialPostFields;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\InteractsWithTable;
use Aura\Base\Traits\SaveFieldAttributes;
use Aura\Base\Traits\SaveMetaFields;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Resource extends Model
{
    use AuraModelConfig;
    use HasFactory;
    use HasTimestamps;

    // Aura
    use InitialPostFields;
    use InputFields;
    use InteractsWithTable;
    use SaveFieldAttributes;
    use SaveMetaFields;

    public $fieldsAttributeCache;

    protected $appends = ['fields'];

    protected $fillable = ['title', 'content', 'type', 'status', 'fields', 'slug', 'user_id', 'parent_id', 'order', 'team_id', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['meta'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    protected $with = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->baseFillable = $this->getFillable();

        // Merge fillable fields from fields
        $this->mergeFillable($this->inputFieldsSlugs());

        if ($this->usesMeta()) {
            $this->with[] = 'meta';
        }
    }

    public function __call($method, $parameters)
    {
        if ($method == 'actors') {
            ray('call', $method)->red();
        }
        if ($this->getFieldSlugs()->contains($method)) {

            $fieldClass = $this->fieldClassBySlug($method);

            if ($fieldClass->isRelation()) {

                $field = $this->fieldBySlug($method);

                return $fieldClass->relationship($this, $field);
            }
        }

        // Default behavior for methods not handled dynamically
        return parent::__call($method, $parameters);
    }

    // public function getMorphClass(): string
    // {
    //     return $this->getType();
    // }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $value = parent::__get($key);

        if ($value) {
            return $value;
        }

        if ($this->getFieldSlugs()->contains($key)) {
            $fieldClass = $this->fieldClassBySlug($key);
            if ($fieldClass->isRelation()) {
                $field = $this->fieldBySlug($key);
                $relation = $fieldClass->getRelation($this, $field);

                return $relation ?: collect();  // Return an empty collection if relation is null
            }
        }

        // If the key is in the fields array, then we want to return that
        if (is_null($value) && isset($this->fields[$key])) {
            return $this->fields[$key];
        }

        return $value;
    }

    // public function __isset($key)
    // {
    //     if(parent::__isset($key)) {
    //         return true;
    //     }

    //     if ($this->getFieldSlugs()->contains($key)) {
    //         ray('contains', $key);
    //         $fieldClass = $this->fieldClassBySlug($key);
    //         if ($fieldClass->isRelation()) {
    //             $field = $this->fieldBySlug($key);
    //             $relation = $fieldClass->getRelation($this, $field);
    //             return $relation && $relation->count() > 0;
    //         }
    //     }

    //     return parent::__isset($key);

    //     return isset($this->attributes[$key]) || isset($this->relations[$key]) || $this->hasGetMutator($key);
    // }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachment()
    {
        return $this->hasMany(self::class, 'post_parent')
            ->where('post_type', 'attachment');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(get_class($this), 'parent_id');
    }

    public function clearFieldsAttributeCache()
    {
        $this->fieldsAttributeCache = null;

        if ($this->usesMeta()) {
            $this->load('meta'); // This will refresh only the 'meta' relationship
        }

        // $this->refresh();
    }

    public function getBulkActions()
    {
        // get all flows with type "manual"

        // $flows = Flow::where('trigger', 'manual')
        //     ->where('options->resource', $this->type)
        //     ->get();

        // foreach ($flows as $flow) {
        //     $this->bulkActions['callManualFlow'] = $flow->name;
        // }

        return $this->bulkActions;
    }

    /**
     * @return string
     */
    public function getExcerptAttribute()
    {
        return $this->stripShortcodes($this->resource_excerpt);
    }

    public function getFieldsAttribute()
    {
        if (! isset($this->fieldsAttributeCache) || $this->fieldsAttributeCache === null) {
            $meta = $this->getMeta();

            try {
                $defaultValues = collect($this->inputFieldsSlugs())
                    ->mapWithKeys(fn ($value) => [$value => null])
                    ->map(function ($value, $key) {
                        return $value;
                    });
            } catch (\Exception $e) {
                ray($e);
                throw new \Exception('An error occurred while processing fields');
            }

            //         dd(collect($this->inputFieldsSlugs())
            //     ->mapWithKeys(fn ($value) => [$value => null])
            // ->map(function ($value, $key) use ($meta) {
            //     return [$value, $key];
            // }));
            // return [];
            $defaultValues = collect($this->inputFieldsSlugs())
                ->mapWithKeys(fn ($value) => [$value => null])
                ->filter(function ($value, $key) {
                    return strpos($key, '.') === false;
                })
                ->map(function ($value, $key) use ($meta) {
                    $class = $this->fieldClassBySlug($key);
                    $field = $this->fieldBySlug($key);

                    // if($key == 'actors') {
                    //     dd($this->{$key}, isset($this->{$key}));
                    // }

                    if ($class && $class->isRelation($field) && method_exists($class, 'get') && $field['type'] != 'Aura\\Base\\Fields\\Roles') {
                        // dd('hier', $key, $this->{$key}, $field);
                        return $class->get($class, $this->{$key}, $field);
                    }

                    // if ($class && $class->isRelation($field) && $this->{$key} && method_exists($class, 'get')) {
                    //     if($key == 'permissions') {
                    //         return;
                    //     }
                    //     return $class->get($class, $this->{$key}, $field);
                    // }

                    // Without relation
                    if ($class && isset($this->{$key}) && method_exists($class, 'get')) {
                        return $class->get($class, $this->{$key}, $field);
                    }

                    if (isset($this->{$key})) {
                        return $this->{$key};
                    }

                    if ($class && isset($this->attributes[$key]) && method_exists($class, 'get')) {
                        return $class->get($class, $this->attributes[$key], $field);
                    }

                    if (isset($this->attributes[$key])) {
                        return $this->attributes[$key];
                    }

                    $method = 'get'.Str::studly($key).'Field';

                    if (method_exists($this, $method)) {
                        return $this->{$method}($value);
                    }

                    if ($class && isset(optional($this)->{$key}) && method_exists($class, 'get')) {
                        return $class->get($class, $this->{$key} ?? null, $field);
                    }

                    if (optional($field)['polymorphic_relation'] === false && optional($field)['multiple'] === false) {
                        // dd($class, $class->isRelation($field), $this->{$key}, isset($this->{$key}), method_exists($class, 'get'));
                        return isset($meta[$key]) ? [$meta[$key]] : [];
                    }
                    // if ($class && $class->isRelation($field) && $this->{$key}) {
                    //     // dd($class, $class->isRelation($field), $this->{$key}, isset($this->{$key}), method_exists($class, 'get'));
                    //     // dd('hier', $key, $field);
                    //     return $class->get($class, $this->{$key}, $field);
                    // }

                    return $meta[$key] ?? $value;
                });

            $this->fieldsAttributeCache = $defaultValues
                ->filter(function ($value, $key) {

                    // return true;
                    $field = $this->fieldBySlug($key);

                    return $this->shouldDisplayField($field);
                });
        }

        return $this->fieldsAttributeCache;
    }

    public function getFieldsWithoutConditionalLogic()
    {
        $meta = $this->getMeta();

        return collect($this->inputFieldsSlugs())
            ->mapWithKeys(fn ($value, $key) => [$value => null])
            ->map(fn ($value, $key) => $meta[$key] ?? $value)
            ->filter(fn ($value, $key) => strpos($key, '.') === false)
            ->map(function ($value, $key) {
                $class = $this->fieldClassBySlug($key);
                $field = $this->fieldBySlug($key);

                if ($class && isset($this->attributes[$key]) && method_exists($class, 'get')) {
                    return $class->get($class, $this->attributes[$key], $field);
                }

                if (isset($this->attributes[$key])) {
                    return $this->attributes[$key];
                }

                return $value;
            })
            ->toArray();
    }

    public function getMeta($key = null)
    {
        if ($this->usesCustomTable() && ! $this->usesMeta()) {
            return collect();
        }

        if ($this->usesMeta() && optional($this)->meta && ! is_string($this->meta)) {

            $meta = $this->meta->pluck('value', 'key');

            // Cast Attributes
            $meta = $meta->map(function ($meta, $key) {
                $field = $this->fieldBySlug($key);

                $class = $this->fieldClassBySlug($key);

                if ($class && method_exists($class, 'get')) {
                    return $class->get($class, $meta, $field);
                }

                return $meta;
            });

            if ($key) {
                return $meta[$key] ?? null;
            }

            return $meta;
        }

        return collect();
    }

    public function getSearchableFields()
    {
        // get input fields and remove the ones that are not searchable
        $fields = $this->inputFields()->filter(function ($field) {
            // if $field is array or undefined, then we don't want to use it
            if (! is_array($field) || ! isset($field['searchable'])) {
                return false;
            }

            return $field['searchable'];
        });

        return $fields;
    }

    // Override isRelation
    public function isRelation($key)
    {
        $modelMethods = get_class_methods($this);

        $possibleRelationMethods = [$key, Str::camel($key)];

        foreach ($possibleRelationMethods as $method) {

            if (in_array($method, $modelMethods) && ($this->{$method}() instanceof \Illuminate\Database\Eloquent\Relations\Relation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(get_class($this), 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function revision()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('post_type', 'revision');
    }

    /**
     * Get the User associated with the Content
     *
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(config('aura.resources.user'));
    }

    public function widgets()
    {
        if (! $this->getWidgets()) {
            return;
        }

        return collect($this->getWidgets())->map(function ($item) {
            //$item['widget'] = app($item['type'])->widget($item);

            return $item;
        });
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        if (! static::$customTable) {
            static::addGlobalScope(new TypeScope);
        }

        static::addGlobalScope(app(TeamScope::class));

        static::addGlobalScope(new ScopedScope);

        static::creating(function ($model) {});

        static::saved(function ($model) {
            $model->clearFieldsAttributeCache();
        });
    }
}
```

## ./Listeners/SyncDatabase.php
```
<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SyncDatabase
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SaveFields $event)
    {
        $newFields = collect($event->fields);
        $existingFields = collect($event->oldFields);
        $model = $event->model;

        // Detect changes, additions, deletions, and reordering
        $fieldsToAdd = $newFields->diffKeys($existingFields);

        // Detect updates
        $fieldsToUpdate = $newFields->filter(function ($field) use ($existingFields) {
            $existingField = $existingFields->firstWhere('_id', $field['_id']);

            return isset($field['_id']) && $existingField && $existingField != $field;
        })->map(function ($field) use ($existingFields) {
            $oldField = $existingFields->firstWhere('_id', $field['_id']);

            return [
                'old' => $oldField,
                'new' => $field,
            ];
        })->values();

        $fieldsToDelete = $existingFields->diffKeys($newFields);

        // $fieldsReordered = $this->detectReorderedFields($newFields, $existingFields);

        // Option: Migration_Run_1:
        // Option: Migration_Run_2:
        // Option: Migration_Run_3:

        // migration: rename_title_to_title2
        // migration: rename_title2_to_title3
        // migration: rename_title3_to_title

        // What if:

        // migration: delete_title
        // migration: add_title

        // The data of Employee 2: title = "Manager" -> deleted

        // Idea: In the deletion migration: Check if the column is still there, if so, skip the deletion
        // Add_Title: Check if the column is already there, if so, skip the addition

        // What if 2:

        // migration: rename_title_to_title2
        // migration: rename_title2_to_title3
        // migration: create_title

        // Option 1: Keep only 1 Migration file and try to sync db -> cons: renaming would not be possible

        // Option 2: Keep all migration files and try to sync db -> cons: multiple migration files, more complex

        // ray('sync', $fieldsToAdd, $fieldsToUpdate, $fieldsToDelete, $model, $model->getTable());

        return;

        // Change Migration File
        // php artisan aura:db-sync

        // Apply schema changes
        Schema::table($model->getTable(), function (Blueprint $table) use ($fieldsToAdd, $fieldsToUpdate, $fieldsToDelete) {
            // Add new fields
            foreach ($fieldsToAdd as $field) {
                $this->addField($table, $field);
            }

            // Update existing fields
            foreach ($fieldsToUpdate as $field) {
                $this->updateField($table, $field);
            }

            // Remove deleted fields
            foreach ($fieldsToDelete as $field) {
                $table->dropColumn($field['slug']);
            }
        });

        // Handle reordering separately if necessary
        $this->handleReordering($fieldsReordered);

        // Return the new array (new schema) if necessary
        return $newFields->toArray();
    }

    /**
     * Add a new field to the table.
     */
    private function addField(Blueprint $table, array $field)
    {
        // Define field addition logic based on field type
        switch ($field['type']) {
            case 'Aura\Base\Fields\Text':
                $table->string($field['slug'])->nullable();
                break;
                // Add cases for other field types as needed
            default:
                throw new \Exception('Unknown field type: '.$field['type']);
        }
    }

    /**
     * Detect reordered fields.
     */
    private function detectReorderedFields($newFields, $existingFields)
    {
        $reorderedFields = [];

        $newFields->each(function ($field, $index) use ($existingFields, &$reorderedFields) {
            if (isset($field['_id'])) {
                $existingField = $existingFields->firstWhere('_id', $field['_id']);
                if ($existingField && $existingFields->search($existingField) !== $index) {
                    $reorderedFields[] = $field;
                }
            }
        });

        return $reorderedFields;
    }

    /**
     * Handle reordering of fields.
     */
    private function handleReordering($fieldsReordered)
    {
        // Implement logic for handling reordering if necessary
        // This could involve updating metadata or simply noting the changes
        // For this example, we'll just output the reordered fields
        // if (!empty($fieldsReordered)) {
        //     // You can handle reordering in the way your application requires
        //     // This could be updating an order column, metadata, or another mechanism
        //     dd('Reordered Fields:', $fieldsReordered);
        // }
    }

    /**
     * Update an existing field in the table.
     */
    private function updateField(Blueprint $table, array $field)
    {
        // Define field update logic based on field type
        switch ($field['type']) {
            case 'Aura\Base\Fields\Text':
                $table->string($field['slug'])->nullable()->change();
                break;
                // Add cases for other field types as needed
            default:
                throw new \Exception('Unknown field type: '.$field['type']);
        }
    }
}
```

## ./Listeners/CreateDatabaseMigration.php
```
<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;

class CreateDatabaseMigration
{
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function handle(SaveFields $event)
    {
        $newFields = collect($event->fields);
        $existingFields = collect($event->oldFields);
        $model = $event->model;
        $tableName = $model->getTable();

        if (! $model::$customTable) {
            return;
        }

        // Detect fields to add
        $fieldsToAdd = $newFields->filter(function ($field) {
            return ! isset($field['_id']);
        });

        // Detect fields to update
        $fieldsToUpdate = $newFields->filter(function ($field) use ($existingFields) {
            if (! isset($field['_id'])) {
                return false;
            }
            $existingField = $existingFields->firstWhere('_id', $field['_id']);

            return $existingField && $existingField != $field;
        })->map(function ($field) use ($existingFields) {
            $oldField = $existingFields->firstWhere('_id', $field['_id']);

            return ['old' => $oldField, 'new' => $field];
        })->values();

        // Detect fields to delete
        $fieldsToDelete = $existingFields->filter(function ($field) use ($newFields) {
            return ! $newFields->contains('_id', $field['_id']);
        });

        if ($fieldsToAdd->isEmpty() && $fieldsToUpdate->isEmpty() && $fieldsToDelete->isEmpty()) {
            return;
        }

        // Generate migration name
        $timestamp = date('Y_m_d_His');
        $migrationName = "update_{$tableName}_table_{$timestamp}";

        // Create the migration file
        Artisan::call('make:migration', [
            'name' => $migrationName,
            '--table' => $tableName,
        ]);

        $migrationFile = $this->getMigrationPath($migrationName);

        if ($migrationFile === null) {
            throw new \Exception("Unable to find migration file '{$migrationName}'.");
        }

        // Generate schema for additions, updates, and deletions
        $schemaAdditions = $this->generateSchema($fieldsToAdd, 'add');
        $schemaUpdates = $this->generateSchema($fieldsToUpdate, 'update');
        $schemaDeletions = $this->generateSchema($fieldsToDelete, 'delete');

        // Generate down schema for additions, updates, and deletions
        $schemaAdditionsDown = $this->generateDownSchema($fieldsToAdd, 'add');
        $schemaUpdatesDown = $this->generateDownSchema($fieldsToUpdate, 'update');
        $schemaDeletionsDown = $this->generateDownSchema($fieldsToDelete, 'delete');

        // Update the migration file content
        $content = $this->files->get($migrationFile);
        $updatedContent = $this->updateMigrationContent($content, $schemaAdditions, $schemaUpdates, $schemaDeletions, $schemaAdditionsDown, $schemaUpdatesDown, $schemaDeletionsDown);

        // Update the migration file content
        $content = $this->files->get($migrationFile);

        $updatedContent = $this->updateMigrationContent($content, $schemaAdditions, $schemaUpdates, $schemaDeletions, $schemaAdditionsDown, $schemaUpdatesDown, $schemaDeletionsDown);

        // Write the updated content back to the migration file
        $this->files->put($migrationFile, $updatedContent);

        try {
            // Run Pint to format the migration file
            $this->runPint($migrationFile);

            // Run the migration
            Artisan::call('migrate');
        } catch (\Exception $e) {
            // We don't want to throw an exception here, just log it
            Log::error($e->getMessage());
        }

    }

    protected function generateColumn($field)
    {
        $fieldInstance = app($field['type']);
        $columnType = $fieldInstance->tableColumnType;

        return "\$table->{$columnType}('{$field['slug']}')->nullable();\n";
    }

    protected function generateDownSchema($fields, $action)
    {
        $downSchema = '';

        foreach ($fields as $field) {

            switch ($action) {
                case 'add':
                    // For additions in the up method, we need to drop the columns in the down method
                    $downSchema .= "\$table->dropColumn('{$field['slug']}');\n";
                    break;
                case 'update':
                    $oldSlug = $field['old']['slug'];
                    $newSlug = $field['new']['slug'];
                    $oldType = app($field['old']['type'])->tableColumnType;
                    $newType = app($field['new']['type'])->tableColumnType;

                    if ($oldType !== $newType) {
                        $downSchema .= "\$table->{$oldType}('{$newSlug}')->nullable()->change();\n";
                    }

                    if ($oldSlug !== $newSlug) {
                        $downSchema .= "\$table->renameColumn('{$newSlug}', '{$oldSlug}');\n";
                    }
                    break;
                case 'delete':
                    // For deletions in the up method, we need to re-add the columns in the down method
                    $downSchema .= $this->generateColumn($field);
                    break;
            }
        }

        return $downSchema;
    }

    protected function generateSchema($fields, $action)
    {
        $schema = '';

        foreach ($fields as $field) {

            switch ($action) {
                case 'add':
                    $schema .= $this->generateColumn($field);
                    break;
                case 'update':
                    $oldSlug = $field['old']['slug'];
                    $newSlug = $field['new']['slug'];
                    $oldType = app($field['old']['type'])->tableColumnType;
                    $newType = app($field['new']['type'])->tableColumnType;

                    if ($oldSlug !== $newSlug) {
                        $schema .= "\$table->renameColumn('{$oldSlug}', '{$newSlug}');\n";
                    }

                    if ($oldType !== $newType) {
                        $schema .= "\$table->{$newType}('{$newSlug}')->nullable()->change();\n";
                    }
                    break;
                case 'delete':
                    // Dont Drop ID, Created At, Updated At
                    if (in_array($field['slug'], ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                        break;
                    }

                    $schema .= "\$table->dropColumn('{$field['slug']}');\n";
                    break;
            }
        }

        return $schema;
    }

    protected function getMigrationPath($name)
    {
        $migrationFiles = $this->files->glob(database_path('migrations/*.php'));
        $name = Str::snake($name);

        foreach ($migrationFiles as $file) {
            if (strpos($file, $name) !== false) {
                return $file;
            }
        }

    }

    protected function runPint($migrationFile)
    {
        $command = [
            (new ExecutableFinder)->find('php', 'php', [
                '/usr/local/bin',
                '/opt/homebrew/bin',
            ]),

            'vendor/bin/pint', $migrationFile,
        ];

        $result = Process::path(base_path())->run($command);
    }

    protected function updateMigrationContent($content, $additions, $updates, $deletions, $additionsDown, $updatesDown, $deletionsDown)
    {
        // Up method
        $pattern = '/(public function up\(\): void[\s\S]*?Schema::table\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
        $replacement = '${1}'.PHP_EOL.$additions.PHP_EOL.$updates.PHP_EOL.$deletions.PHP_EOL.'${3}';
        $updatedContent = preg_replace($pattern, $replacement, $content);

        // Down method
        $downPattern = '/(public function down\(\): void[\s\S]*?Schema::table\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
        $downReplacement = '${1}'.PHP_EOL.$additionsDown.PHP_EOL.$updatesDown.PHP_EOL.$deletionsDown.PHP_EOL.'${3}';
        $updatedContent = preg_replace($downPattern, $downReplacement, $updatedContent);

        return $updatedContent;
    }
}
```

## ./Listeners/ModifyDatabaseMigration.php
```
<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;

class ModifyDatabaseMigration
{
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Handle the event.
     */
    public function handle(SaveFields $event)
    {
        $model = $event->model;
        $newFields = collect($event->fields);
        $existingFields = collect($event->oldFields);

        if (! $model::$customTable) {
            return;
        }

        $tableName = $model->getTable();

        $migrationName = "create_{$tableName}_table";

        $schema = $this->generateSchema($newFields);

        if ($this->migrationExists($migrationName)) {
            //$this->error("Migration '{$migrationName}' already exists.");
            //return 1;
            $migrationFile = $this->getMigrationPath($migrationName);
        } else {
            Artisan::call('make:migration', [
                'name' => $migrationName,
                '--create' => $tableName,
            ]);

            $migrationFile = $this->getMigrationPath($migrationName);
        }

        if ($migrationFile === null) {
            throw new \Exception("Unable to find migration file '{$migrationName}'.");
        }

        $content = $this->files->get($migrationFile);

        // Up method
        $pattern = '/(public function up\(\): void[\s\S]*?Schema::create\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
        $replacement = '${1}'.$schema.'${3}';
        $replacedContent = preg_replace($pattern, $replacement, $content);

        // Down method
        $down = "Schema::dropIfExists('{$tableName}');";
        $pattern = '/(public function down\(\): void[\s\S]*?{)[\s\S]*?Schema::table\(.*?function \(Blueprint \$table\) \{[\s\S]*?\/\/[\s\S]*?\}\);[\s\S]*?\}/';
        $replacement = '${1}'.PHP_EOL.'    '.$down.PHP_EOL.'}';
        $replacedContent2 = preg_replace($pattern, $replacement, $replacedContent);

        $this->files->put($migrationFile, $replacedContent2);

        // Run "pint" on the migration file
        $this->runPint($migrationFile);

        // Run the migration
        Artisan::call('aura:schema-update', ['migration' => $migrationFile]);
    }

    protected function generateColumn($field)
    {
        $fieldInstance = app($field['type']);
        $columnType = $fieldInstance->tableColumnType;

        return "\$table->{$columnType}('{$field['slug']}')->nullable();\n";
    }

    protected function generateSchema($fields)
    {
        $schema = '';

        $schema .= '$table->id();'."\n";

        foreach ($fields as $field) {
            $schema .= $this->generateColumn($field);
        }

        $schema .= '$table->foreignId("user_id");'."\n";
        $schema .= '$table->foreignId("team_id");'."\n";
        $schema .= '$table->timestamps();'."\n";
        $schema .= '$table->softDeletes();'."\n";

        return $schema;
    }

    protected function getMigrationPath($name)
    {
        $migrationFiles = $this->files->glob(database_path('migrations/*.php'));
        $name = Str::snake($name);

        foreach ($migrationFiles as $file) {
            if (strpos($file, $name) !== false) {
                return $file;
            }
        }
    }

    protected function migrationExists($name)
    {
        $migrationFiles = $this->files->glob(database_path('migrations/*.php'));
        $name = Str::snake($name);

        foreach ($migrationFiles as $file) {
            if (strpos($file, $name) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function runPint($migrationFile)
    {
        $command = [
            (new ExecutableFinder)->find('php', 'php', [
                '/usr/local/bin',
                '/opt/homebrew/bin',
            ]),

            'vendor/bin/pint', $migrationFile,
        ];

        $result = Process::path(base_path())->run($command);
    }
}
```

## ./Notifications/FlowNotification.php
```
<?php

namespace Aura\Base\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FlowNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public $resource, public $message) {}

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'resource' => $this->resource,
            'message' => $this->message,
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }
}
```

## ./Services/ThumbnailGenerator.php
```
<?php

namespace Aura\Base\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThumbnailGenerator
{
    /**
     * Generate a thumbnail for the given image path with specified dimensions
     */
    public function generate(string $path, int $width, ?int $height = null): string
    {
        // Get config values
        $quality = Config::get('aura.media.quality', 80) / 100;
        $restrictDimensions = Config::get('aura.media.restrict_to_dimensions', true);

        // If dimensions are restricted, validate the requested dimensions
        if ($restrictDimensions) {
            $allowedDimensions = Config::get('aura.media.dimensions', []);
            $dimensionsAllowed = false;

            foreach ($allowedDimensions as $dimension) {
                if ($dimension['width'] === $width &&
                    (!$height || $dimension['height'] === $height)) {
                    $dimensionsAllowed = true;
                    break;
                }
            }

            if (!$dimensionsAllowed) {
                throw new NotFoundHttpException('Requested thumbnail dimensions are not allowed.');
            }
        }

        // Parse the path to get the base name
        $pathInfo = pathinfo($path);
        $basename = $pathInfo['basename'];
        $folderPath = Str::beforeLast($path, '/').'/';
        $thumbnailFolder = 'thumbnails/'.$folderPath;

        // Determine thumbnail path based on provided width and/or height
        if ($width && !$height) {
            $thumbnailPath = $thumbnailFolder.$width.'_auto_'.$basename;
        } else {
            $height = $height ?: $width;
            $thumbnailPath = $thumbnailFolder.$width.'_'.$height.'_'.$basename;
        }

        // Skip if thumbnail already exists
        if (Storage::disk('public')->exists($thumbnailPath)) {
            return $thumbnailPath;
        }

        // Check if the original image exists
        if (!Storage::disk('public')->exists($folderPath.$basename)) {
            throw new \Exception('Original image not found: ' . $path);
        }

        // Create thumbnail
        $image = Image::make(storage_path('app/public/'.$path));

        // Get original dimensions
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if ($width && !$height) {
            // When only width is specified, maintain aspect ratio and don't upscale
            if ($width > $originalWidth) {
                // If requested width is larger than original, keep original size
                return $path;
            }

            // Resize image to specified width, maintaining aspect ratio
            $image->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        } else {
            // When both dimensions are specified
            if ($width > $originalWidth && $height > $originalHeight) {
                // If both requested dimensions are larger than original,
                // fit to the smallest possible size while maintaining aspect ratio
                $ratio = min($originalWidth / $width, $originalHeight / $height);
                $targetWidth = (int) ($width * $ratio);
                $targetHeight = (int) ($height * $ratio);
                $image->fit($targetWidth, $targetHeight);
            } else {
                // Otherwise use the requested dimensions
                $image->fit($width, $height);
            }
        }

        // Ensure the thumbnail directory exists
        if (!Storage::disk('public')->exists($thumbnailFolder)) {
            Storage::disk('public')->makeDirectory($thumbnailFolder);
        }

        // Save the thumbnail image with quality from config
        $image->save(storage_path('app/public/'.$thumbnailPath), $quality * 100);

        return $thumbnailPath;
    }
}
```

## ./Widgets/Widgets.php
```
<?php

namespace Aura\Base\Widgets;

use Illuminate\Support\Carbon;
use Livewire\Component;

class Widgets extends Component
{
    public $end;

    public $model;

    public $selected = '30d';

    public $start;

    public $test = 'bajram';

    public $widgets;

    public function mount($widgets, $model)
    {
        $this->widgets = $widgets;
        $this->model = $model;

        $this->selected = $this->model->widgetSettings['default'] ?? 'all';
        $this->updatedSelected();
    }

    public function render()
    {
        return view('aura::components.widgets.index');
    }

    public function updatedSelected()
    {
        if ($this->selected === 'custom') {
            $this->start = Carbon::now()->subDays(30);
            $this->end = Carbon::now();
        } else {
            $this->updateDates();
        }

        $this->dispatch('dateFilterUpdated', $this->start, $this->end);
    }

    protected function updateDates()
    {
        $now = now()->endOfDay();

        [$this->start, $this->end] = match ($this->selected) {
            'all' => [null, null],
            'ytd' => [$now->copy()->startOfYear(), $now->copy()],
            'qtd' => [$now->copy()->startOfQuarter(), $now->copy()],
            'mtd' => [$now->copy()->startOfMonth(), $now->copy()],
            'wtd' => [$now->copy()->startOfWeek(), $now->copy()],
            'last-month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'last-week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'last-quarter' => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            'last-year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            default => [$now->copy()->subDays(intval(preg_replace('/[^0-9]/', '', $this->selected))), $now->copy()],
        };
    }
}
```

## ./Widgets/ValueWidget.php
```
<?php

namespace Aura\Base\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ValueWidget extends Widget
{
    public $end;

    public $method = 'count';

    public $model;

    public $start;

    public $widget;

    protected $listeners = ['dateFilterUpdated' => 'updateDateRange'];

    public function getValue($start, $end)
    {
        $column = optional($this->widget)['column'];

        $posts = $this->model->query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        // Apply the query scope if it's a string
        if (optional($this->widget)['queryScope']) {
            $posts->{$this->widget['queryScope']}();
        }

        if ($column && $this->model->isMetaField($column)) {
            $posts->select('posts.*', DB::raw("CAST(meta.value as SIGNED) as $column"))
                ->leftJoin('meta', function ($join) use ($column) {
                    $join->on('posts.id', '=', 'meta.metable_id')
                        ->where('meta.key', '=', $column)
                        ->where('meta.metable_type', '=', get_class($this->model));
                });
        }

        return match ($this->method) {
            'avg' => $posts->avg($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'sum' => $posts->sum($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'min' => $posts->min($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'max' => $posts->max($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            default => $posts->count(),
        };
    }

    public function getValuesProperty()
    {
        $currentStart = $this->start instanceof Carbon ? $this->start : Carbon::parse($this->start);
        $currentEnd = $this->end instanceof Carbon ? $this->end : Carbon::parse($this->end);

        // Calculate the duration between start and end dates
        $duration = $currentStart->diffInDays($currentEnd);

        // Calculate previousStart and previousEnd based on the duration
        $previousStart = $currentStart->copy()->subDays($duration);
        $previousEnd = $currentStart;

        return cache()->remember($this->cacheKey, $this->cacheDuration, function () use ($currentStart, $currentEnd, $previousStart, $previousEnd) {
            $current = $this->getValue($currentStart, $currentEnd);
            $previous = $this->getValue($previousStart, $previousEnd);

            $change = ($previous != 0) ? (($current - $previous) / $previous) * 100 : 0;

            return [
                'current' => $this->format($current),
                'previous' => $this->format($previous),
                'change' => $this->format($change),
            ];
        });
    }

    public function mount()
    {
        if ($this->widget['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render()
    {
        return view('aura::components.widgets.value');
    }

    public function updateDateRange($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }
}
```

## ./Widgets/SparklineArea.php
```
<?php

namespace Aura\Base\Widgets;

class SparklineArea extends Sparkline
{
    public function render()
    {
        return view('aura::components.widgets.sparkline-area');
    }
}
```

## ./Widgets/SparklineBar.php
```
<?php

namespace Aura\Base\Widgets;

class SparklineBar extends Sparkline
{
    public function render()
    {
        return view('aura::components.widgets.sparkline-bar');
    }
}
```

## ./Widgets/Sparkline.php
```
<?php

namespace Aura\Base\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Sparkline extends Widget
{
    public $end;

    public $method = 'area';

    public $model;

    public $start;

    public $widget;

    protected $listeners = ['dateFilterUpdated' => 'updateDateRange'];

    public function getCarbonDate($date)
    {
        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    public function getValue($start, $end)
    {
        $column = optional($this->widget)['column'];
        $method = $this->method;

        $query = $this->model->query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'));

        if ($column && $this->model->isMetaField($column)) {
            $query->leftJoin('meta', function ($join) use ($column) {
                $join->on('posts.id', '=', 'meta.metable_id')
                    ->where('meta.key', '=', $column)
                    ->where('meta.metable_type', '=', get_class($this->model));
            });

            if (in_array($method, ['avg', 'sum', 'min', 'max'])) {
                $query->addSelect(DB::raw("{$method}(CAST(meta.value as SIGNED)) as count"));
            } else {
                $query->addSelect(DB::raw('COUNT(*) as count'));
            }
        } else {
            $query->addSelect(DB::raw('COUNT(*) as count'));
        }

        $postsByDate = $query->get()->pluck('count', 'date')->toArray();

        // Generate a date range between $start and $end
        $dateRange = [];
        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dateRange[$date->format('Y-m-d')] = 0;
        }

        // Merge date range with the results from the query
        return collect($dateRange)->merge($postsByDate);
    }

    public function getValuesProperty()
    {
        $currentStart = $this->getCarbonDate($this->start)->addDay();
        $currentEnd = $this->getCarbonDate($this->end);
        $diff = round($currentStart->diffInDays($currentEnd));

        $previousStart = $currentStart->copy()->subDays($diff + 1);
        $previousEnd = $currentStart->copy()->subDay();

        return [
            'current' => $this->getValue($currentStart, $currentEnd)->toArray(),
            'previous' => $this->getValue($previousStart, $previousEnd)->toArray(),
        ];
    }

    public function mount()
    {
        // dd('hier', $this->start, $this->end, $this->model, $this->widget);

        if (optional($this->widget)['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render()
    {
        return view('aura::components.widgets.sparkline-area');
    }

    public function updateDateRange($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }
}
```

## ./Widgets/Widget.php
```
<?php

namespace Aura\Base\Widgets;

use Livewire\Component;

class Widget extends Component
{
    public $isCached = false;

    public $loaded = false;

    public function format($value)
    {
        $formatted = number_format($value, 2, '.', "'");

        if (substr($formatted, -3) === '.00') {
            $formatted = substr($formatted, 0, -3);
        }

        return $formatted;
    }

    public function getCacheDurationProperty()
    {
        return $this->widget['cache']['duration'] ?? 60;
    }

    public function getCacheKeyProperty()
    {
        return md5(auth()->user()->current_team_id.$this->widget['slug'].$this->start.$this->end);
    }

    public function loadWidget()
    {
        $this->loaded = true;
    }

    public function mount()
    {
        // Check if the widget is cached
        if (cache()->has($this->cacheKey)) {
            $this->isCached = true;
            $this->loaded = true;
        }
    }
}
```

## ./Widgets/Bar.php
```
<?php

namespace Aura\Base\Widgets;

class Bar extends Sparkline
{
    public function render()
    {
        return view('aura::components.widgets.bar');
    }
}
```

## ./Widgets/Donut.php
```
<?php

namespace Aura\Base\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Donut extends Widget
{
    public $end;

    public $method = 'count';

    public $model;

    public $start;

    public $widget;

    protected $listeners = ['dateFilterUpdated' => 'updateDateRange'];

    public function getValue($start, $end)
    {
        return [
            'tag-1' => rand(10, 50),
            'tag-2' => rand(10, 50),
            'tag-3' => rand(10, 50),
            'tag-4' => rand(10, 50),
        ];

        $column = optional($this->widget)['column'];
        $taxonomy = optional($this->widget)['taxonomy'];

        $posts = $this->model->query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        if ($column && $this->model->isMetaField($column)) {
            $posts->select('posts.*', DB::raw("CAST(meta.value as SIGNED) as $column"))
                ->leftJoin('meta', function ($join) use ($column) {
                    $join->on('posts.id', '=', 'meta.metable_id')
                        ->where('meta.key', '=', $column)
                        ->where('meta.metable_type', '=', get_class($this->model));
                });
        }

        return match ($this->method) {
            'avg' => $posts->avg($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'sum' => $posts->sum($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'min' => $posts->min($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'max' => $posts->max($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            default => $posts->count(),
        };
    }

    public function getValuesProperty()
    {
        $currentStart = $this->start instanceof Carbon ? $this->start : Carbon::parse($this->start);
        $currentEnd = $this->end instanceof Carbon ? $this->end : Carbon::parse($this->end);

        // Calculate the duration between start and end dates
        $duration = $currentStart->diffInDays($currentEnd);

        // Calculate previousStart and previousEnd based on the duration
        $previousStart = $currentStart->copy()->subDays($duration);
        $previousEnd = $currentStart;

        return cache()->remember($this->cacheKey, $this->cacheDuration, function () use ($currentStart, $currentEnd, $previousStart, $previousEnd) {
            $current = $this->getValue($currentStart, $currentEnd);
            $previous = $this->getValue($previousStart, $previousEnd);

            // $change = ($previous != 0) ? (($current - $previous) / $previous) * 100 : 0;

            return [
                'current' => $current,
                'previous' => $previous,
                // 'change' => $change,
            ];
        });
    }

    public function mount()
    {
        if (optional($this->widget)['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render()
    {
        return view('aura::components.widgets.donut');
    }

    public function updateDateRange($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }
}
```

## ./Widgets/Pie.php
```
<?php

namespace Aura\Base\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Pie extends Widget
{
    public $end;

    public $method = 'count';

    public $model;

    public $start;

    public $widget;

    protected $listeners = ['dateFilterUpdated' => 'updateDateRange'];

    public function getValue($start, $end)
    {
        return [
            'tag-1' => rand(10, 50),
            'tag-2' => rand(10, 50),
            'tag-3' => rand(10, 50),
            'tag-4' => rand(10, 50),
        ];

        $column = optional($this->widget)['column'];

        $posts = $this->model->query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        if ($column && $this->model->isMetaField($column)) {
            $posts->select('posts.*', DB::raw("CAST(meta.value as SIGNED) as $column"))
                ->leftJoin('meta', function ($join) use ($column) {
                    $join->on('posts.id', '=', 'meta.metable_id')
                        ->where('meta.key', '=', $column)
                        ->where('meta.metable_type', '=', get_class($this->model));
                });
        }

        return match ($this->method) {
            'avg' => $posts->avg($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'sum' => $posts->sum($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'min' => $posts->min($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            'max' => $posts->max($this->model->isMetaField($column) ? DB::raw('CAST(meta.value as SIGNED)') : $column),
            default => $posts->count(),
        };
    }

    public function getValuesProperty()
    {
        $currentStart = $this->start instanceof Carbon ? $this->start : Carbon::parse($this->start);
        $currentEnd = $this->end instanceof Carbon ? $this->end : Carbon::parse($this->end);

        // Calculate the duration between start and end dates
        $duration = $currentStart->diffInDays($currentEnd);

        // Calculate previousStart and previousEnd based on the duration
        $previousStart = $currentStart->copy()->subDays($duration);
        $previousEnd = $currentStart;

        return cache()->remember($this->cacheKey, $this->cacheDuration, function () use ($currentStart, $currentEnd, $previousStart, $previousEnd) {
            $current = $this->getValue($currentStart, $currentEnd);
            $previous = $this->getValue($previousStart, $previousEnd);

            // $change = ($previous != 0) ? (($current - $previous) / $previous) * 100 : 0;

            return [
                'current' => $current,
                'previous' => $previous,
                // 'change' => $change,
            ];
        });
    }

    public function mount()
    {
        if (optional($this->widget)['method']) {
            $this->method = $this->widget['method'];
        }
    }

    public function render()
    {
        return view('aura::components.widgets.pie');
    }

    public function updateDateRange($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }
}
```

## ./BaseResource.php
```
<?php

namespace Aura\Base;

use Aura\Base\Traits\AuraModelConfig;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\InteractsWithTable;
use Illuminate\Database\Eloquent\Model;

class BaseResource extends Model
{
    use AuraModelConfig;
    use InputFields;
    use InteractsWithTable;
}
```

