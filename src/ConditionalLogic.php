<?php

namespace Eminiarts\Aura;

class ConditionalLogic
{
    private static $shouldDisplayFieldCache = [];

    public static function clearConditionsCache()
    {
        self::$shouldDisplayFieldCache = [];
    }

    public static function shouldDisplayField($model, $field)
    {
        // Generate a unique cache key based on the model's class name, ID, and the field key.
        $cacheKey = md5(json_encode($model) . '-' . json_encode($field));

        // If the result is already in the cache, return it.
        if (array_key_exists($cacheKey, self::$shouldDisplayFieldCache)) {
            return self::$shouldDisplayFieldCache[$cacheKey];
        }

        // ray()->count();

        // Check Conditional Logic if the field should be displayed.
        $result = self::checkCondition($model, $field);

        // Before returning the result, store it in the cache.
        self::$shouldDisplayFieldCache[$cacheKey] = $result;

        return $result;
    }

    public static function checkCondition($model, $field)
    {
        $conditions = optional($field)['conditional_logic'];

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

            // if condition is a closure, run it
            if ($condition instanceof \Closure) {
                $show = $condition($model);

                if ($show === false) {
                    break;
                }
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

                    if (optional($model)->getMeta()) {
                        $fieldValue = $model->getMeta($condition['field']);

                        if (! $fieldValue) {
                            $show = false;
                            break;
                        }
                    }

                    // If the $condition['field'] has a dot, undot array
                    if (str_contains($condition['field'], '.')) {
                        $fieldValue = data_get($model->getMeta()->toArray(), $condition['field']);
                        $show = ConditionalLogic::checkFieldCondition($condition, $fieldValue);

                        break;
                    }

                    if (optional($model)->getMeta() && ! array_key_exists($condition['field'], $model->getMeta()->toArray())) {
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
        // ray()->red('checkRoleCondition', $condition);

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
