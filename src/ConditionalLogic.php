<?php

namespace Aura\Base;

use Illuminate\Support\Facades\Auth;

class ConditionalLogic
{
    private static $shouldDisplayFieldCache = [];

    public static function checkCondition($model, $field, $post = null)
    {
        $conditions = $field['conditional_logic'] ?? null;

        if (! $conditions || ! Auth::check()) {
            return true;
        }

        if ($conditions instanceof \Closure) {
            return self::executeClosure($conditions, $model, $post);
        }

        if (! is_array($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if ($condition instanceof \Closure) {
                if (! self::executeClosure($condition, $model, $post)) {
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
            return $closure($model, $post) !== false;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            return false;
        }
    }

    private static function handleDefaultCondition($model, $condition, $post)
    {
        if (is_array($model) && array_key_exists($condition['field'], $model)) {
            return self::checkFieldCondition($condition, $model[$condition['field']]);
        }

        $fieldValue = method_exists($model, 'getMeta')
            ? $model->getMeta($condition['field'])
            : data_get($model->post['fields'] ?? [], $condition['field']);

        if (! $fieldValue && str_contains($condition['field'], '.')) {
            $fieldValue = is_array($model)
                ? data_get($model, $condition['field'])
                : data_get($model->getMeta()->toArray(), $condition['field']);
        }

        return $fieldValue ? self::checkFieldCondition($condition, $fieldValue) : false;
    }

    private static function handleRoleCondition($condition)
    {
        if (Auth::user()->isSuperAdmin()) {
            return true;
        }

        return self::checkRoleCondition($condition);
    }
}
