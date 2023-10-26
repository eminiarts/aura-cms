<?php

namespace Eminiarts\Aura;

class ConditionalLogic
{
    private static $shouldDisplayFieldCache = [];

    public static function checkCondition($model, $field, $post = null)
    {

        // ray('checkCondition', $field);

        $conditions = $field['conditional_logic'] ?? null;
        if (! $conditions || ! auth()->user()) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! $model || $condition instanceof \Closure) {
                return $condition($model, $post) === false ? false : true;
            }

            if (! is_array($condition)) {
                continue;
            }

            $show = match ($condition['field']) {
                'role' => self::handleRoleCondition($condition),
                default => self::handleDefaultCondition($model, $condition, $post)
            };

            if (! $show) {
                return false;
            }
        }

        return true;
    }

    public static function clearConditionsCache()
    {
        self::$shouldDisplayFieldCache = [];
    }

    public static function fieldIsVisibleTo($field, $user)
    {
        $conditions = $field['conditional_logic'] ?? null;
        if (! $conditions || $user->resource->isSuperAdmin()) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! $field) {
                return false;
            }
            if ($condition['field'] === 'role' && ! self::checkRoleCondition($condition)) {
                return false;
            }
        }

        return true;
    }

    public static function shouldDisplayField($model, $field, $post = null)
    {
        // ray('shouldDisplayField', $model, $field, $post);

        if (! $field) {
            return true;
        }

        if (empty($field['conditional_logic'])) {
            return true;
        }



        $cacheKey = md5(get_class($model).json_encode($field).json_encode($post));

        return self::$shouldDisplayFieldCache[$cacheKey]
            ??= self::checkCondition($model, $field, $post);
    }

    private static function checkFieldCondition($condition, $fieldValue)
    {
        return match ($condition['operator']) {
            '==' => $fieldValue == $condition['value'],
            '!=' => $fieldValue != $condition['value'],
            '<=' => $fieldValue <= $condition['value'],
            '>' => $fieldValue > $condition['value'],
            '<' => $fieldValue < $condition['value'],
            '>=' => $fieldValue >= $condition['value'],
            default => false
        };
    }

    private static function checkRoleCondition($condition)
    {
        return match ($condition['operator']) {
            '==' => auth()->user()->resource->hasRole($condition['value']),
            '!=' => ! auth()->user()->resource->hasRole($condition['value']),
            default => false
        };
    }

    private static function handleDefaultCondition($model, $condition, $post)
    {
        // if($post) {
        //     $model = $post;
        // }

        if (is_array($model) && array_key_exists($condition['field'], $model)) {
            return self::checkFieldCondition($condition, $model[$condition['field']]);
        }

        $fieldValue = method_exists($model, 'getMeta')
            ? $model->getMeta($condition['field'])
            : data_get($model->post['fields'] ?? [], $condition['field']);

        if (! $fieldValue) {
            return false;
        }

        if (str_contains($condition['field'], '.')) {
            $fieldValue = is_array($model)
                ? data_get($model, $condition['field'])
                : data_get($model->getMeta()->toArray(), $condition['field']);
        }

        return self::checkFieldCondition($condition, $fieldValue);
    }

    private static function handleRoleCondition($condition)
    {
        if (auth()->user()->resource->isSuperAdmin()) {
            return true;
        }

        return self::checkRoleCondition($condition);
    }
}
