I am working in Laravel. My request is taking > 10 seconds. I think I know where the problem is, but don’t know how to solve it yet. My Eloquent model has fields and a function which checks if the field should be displayd. the problem is that that function relies on the fields itself and gets in a loop.

here’s the code of the functions in my eloquent model:

```php
 public function getFieldsAttribute()
    {
        if ($this->usesMeta() && optional($this)->meta) {
            $meta = $this->meta->pluck('value', 'key');

            $meta = $meta->map(function ($meta, $key) {
                $class = $this->fieldClassBySlug($key);

                if ($class && method_exists($class, 'get')) {
                    return $class->get($class, $meta);
                }

                return $meta;
            });
        }

        $defaultValues = $this->getFieldSlugs()->mapWithKeys(fn ($value, $key) => [$value => null])->map(fn ($value, $key) => $meta[$key] ?? $value)->map(function ($value, $key) {
            if (in_array($key, $this->hidden)) {
                return;
            }

            $method = 'get'.Str::studly($key).'Field';

            if (method_exists($this, $method)) {
                return $this->{$method}();
            }

            $class = $this->fieldClassBySlug($key);

            if ($class && isset($this->{$key}) && method_exists($class, 'get')) {
                return $class->get($class, $this->{$key});
            }

            if (isset($this->{$key})) {
                return $this->{$key};
            }

            if (isset($this->attributes[$key])) {
                return $this->attributes[$key];
            }
        });

        return $defaultValues->merge($meta ?? [])->filter(function ($value, $key) {

            // this gets called too much
            if (! in_array($key, $this->getAccessibleFieldKeys())) {
                return false;
            }

            return true;

        });
    }

    public function getAccessibleFieldKeys()
    {
        // Apply Conditional Logic of Parent Fields
        $fields = $this->sendThroughPipeline($this->fieldsCollection(), [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
        ]);

        // Get all input fields
        return $fields
            ->filter(function ($field) {
                return $field['field']->isInputField();
            })
            ->pluck('slug')
            ->filter(function ($field) {
                return $this->shouldDisplayField($field);
            })->toArray();
    }

    public function shouldDisplayField($key)
    {
        // Check Conditional Logic if the field should be displayed. This itself relies on the fields, so it gets in a loop
        return ConditionalLogic::checkCondition($this, $this->fieldBySlug($key));
    }
```

ConditionalLogic.php
```php

class ConditionalLogic
{
    public static function checkCondition($model, $field)
    {
        $conditions = optional($field)['conditional_logic'];

        if (! $conditions) {
            return true;
        }

        if (! auth()->user()) {
            return true;
        }

        $show = true;

        foreach ($conditions as $condition) {
            if (! $model) {
                $show = false;
                break;
            }

            if ($condition instanceof \Closure) {
                $show = $condition($model);

                if ($show === false) {
                    break;
                }
            }

            if (! is_array($condition)) {
                break;
            }

            switch ($condition['field']) {
                case 'role':
                    if (auth()->user()->resource->isSuperAdmin()) {
                        break;
                    }

                    $show = ConditionalLogic::checkRoleCondition($condition);
                    break;
                default:
                    if (optional($model)->fields) {
                        $fieldValue = $model->fields[$condition['field']];
                    } else {
                        $fieldValue = optional($model['fields'])[$condition['field']];

                        if (! $fieldValue) {
                            $show = false;
                            break;
                        }
                    }

                    if (str_contains($condition['field'], '.')) {
                        $fieldValue = data_get($model['fields'], $condition['field']);
                        $show = ConditionalLogic::checkFieldCondition($condition, $fieldValue);

                        break;
                    }

                    if (optional($model)->fields && ! array_key_exists($condition['field'], $model->fields->toArray())) {
                        $show = false; // The model does not have the field, so it does not match the condition
                        break;
                    }

                    $show = ConditionalLogic::checkFieldCondition($condition, $fieldValue);
                    break;
            }

            if (! $show) {
                break;
            }
        }

        return $show;
    }

    public static function fieldIsVisibleTo($field, $user)
    {
        $conditions = optional($field)['conditional_logic'];

        if (! $conditions) {
            return true;
        }

        // Super Admins can view everything
        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        $show = true;

        foreach ($conditions as $condition) {
            if (! $field) {
                $show = false;
                break;
            }

            switch ($condition['field']) {
                case 'role':
                    $show = ConditionalLogic::checkRoleCondition($condition);
                    break;
                default:

                    break;
            }

            if (! $show) {
                break;
            }
        }

        return $show;
    }

    private static function checkFieldCondition($condition, $fieldValue)
    {
        switch ($condition['operator']) {
            case '==':
                return $fieldValue == $condition['value'];
            case '!=':
                return $fieldValue != $condition['value'];
            case '<=':
                return $fieldValue <= $condition['value'];
            case '>':
                return $fieldValue > $condition['value'];
            case '<':
                return $fieldValue < $condition['value'];
            case '>=':
                return $fieldValue >= $condition['value'];
            default:
                return false;
        }
    }

    private static function checkRoleCondition($condition)
    {
        switch ($condition['operator']) {
            case '==':
                return auth()->user()->resource->hasRole($condition['value']);
            case '!=':
                return ! auth()->user()->resource->hasRole($condition['value']);
            default:
                return false;
        }
    }
}

```