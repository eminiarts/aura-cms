<?php

namespace Eminiarts\Aura;

class ConditionalLogic
{
    private static $shouldDisplayFieldCache = [];

    public static function checkCondition($model, $field)
    {
        $conditions = optional($field)['conditional_logic'];


        // if(optional($field)['slug'] == 'primary-25') {
        //     dd('hier', $field, $conditions, $model);
        // }


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

                    // dd('hier', method_exists($model, 'getMeta'), $model->post['fields'], $condition);

                    // Task for GPT: here, $fieldvalue is the value of the field that is being checked, $model->post['fields'][$condition['field']] is the value of the field that is being checked against
                    // but the $model does not have a getMeta method, so it is not a post model
                    // I need to allow for other models than posts, so I need to check if the model has post['fields]

                    if (method_exists($model, 'getMeta')) {
                        $fieldValue = $model->getMeta($condition['field']);
                        $fieldValue = $model->getMeta($condition['field']);

                        if (! $fieldValue) {
                            $show = false;
                            break;
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

                    // For Livewire components
                    if (isset($model->post['fields'])) {
                        $fieldValue = data_get($model->post['fields'], $condition['field']);

                        if ($fieldValue === null) {
                            $show = false;
                            break;
                        }

                        if (str_contains($condition['field'], '.')) {
                            $fieldValue = data_get($model->post['fields'], $condition['field']);
                            $show = ConditionalLogic::checkFieldCondition($condition, $fieldValue);
                            break;
                        }

                        if (! array_key_exists($condition['field'], $model->post['fields'])) {
                            $show = false; // The model does not have the field, so it does not match the condition
                            break;
                        }

                        $show = ConditionalLogic::checkFieldCondition($condition, $fieldValue);
                        break;
                    }


            }

            if (! $show) {
                break;
            }
        }

        return $show;
    }

    public static function clearConditionsCache()
    {
        self::$shouldDisplayFieldCache = [];
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

    public static function shouldDisplayField($model, $field)
    {
        // return true;
        // ray()->count();
        if (! $field) {
            return true;
        }

        // Generate a unique cache key based on the model's class name, ID, and the field key.
        if (is_string($field)) {
            $cacheKey = md5(json_encode($field));
        } else {
            $cacheKey = md5(json_encode($field));
        }

        // If the result is already in the cache, return it.
        if (array_key_exists($cacheKey, self::$shouldDisplayFieldCache)) {
            return self::$shouldDisplayFieldCache[$cacheKey];
        }

        //ray()->count();

        // Check Conditional Logic if the field should be displayed.
        $result = self::checkCondition($model, $field);

        // Before returning the result, store it in the cache.
        self::$shouldDisplayFieldCache[$cacheKey] = $result;

        return $result;
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
