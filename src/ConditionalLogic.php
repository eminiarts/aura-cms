<?php

namespace Eminiarts\Aura;

class ConditionalLogic
{
    public static function checkCondition($model, $field)
    {
        $conditions = optional($field)['conditional_logic'];

        // ray($conditions);

        if (! $conditions) {
            return true;
        }

        // If this runs in a job, there is no user
        if (! auth()->user()) {
            return true;
        }

        $show = true;

        foreach ($conditions as $condition) {
            if (! $model) {
                // dd('break here', $model, $field);
                $show = false;
                break;
            }

            // if $condition is not an array, break
            if (! is_array($condition)) {
                // $show = false;
                break;
            }

            switch ($condition['field']) {
                case 'role':
                    // Super Admins can do everything
                    if (auth()->user()->resource->isSuperAdmin()) {
                        break;
                    }

                    $show = ConditionalLogic::checkRoleCondition($condition);
                    break;
                default:

                    // if $model->fields is set, use that, otherwise, use $model['fields']

                    if (isset($model->fields)) {
                        $fieldValue = $model->fields[$condition['field']];
                    } else {
                        $fieldValue = optional($model['fields'])[$condition['field']];

                        if (! $fieldValue) {
                            $show = false;
                            break;
                        }
                    }

                    // If the $condition['field'] has a dot, undot array
                    if (str_contains($condition['field'], '.')) {
                        $fieldValue = data_get($model['fields'], $condition['field']);
                        $show = ConditionalLogic::checkFieldCondition($condition, $fieldValue);

                        break;
                    }

                    if (! array_key_exists($condition['field'], $model->fields->toArray())) {
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

        if ($field['slug'] == 'primarySelect') {
            ray($field, $model, $show);

            dd('hier');
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

                    // Super Admins can do everything
                    if (auth()->user()->resource->isSuperAdmin()) {
                        break;
                    }

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
