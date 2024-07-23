<?php

namespace Aura\Base;

use Illuminate\Support\Facades\Cache;

class ConditionalLogic
{
    private static $shouldDisplayFieldCache = [];

    public static function checkCondition($model, $field, $post = null)
    {
        $conditions = $field['conditional_logic'] ?? null;

        // Check if conditions are set and if the user is authenticated
        if (! $conditions || ! auth()->check()) {
            return true;
        }

        // Ensure $conditions is an array or a closure
        if (! is_array($conditions) && ! ($conditions instanceof \Closure)) {
            return true;
        }

        // If $conditions is a closure, execute it
        if ($conditions instanceof \Closure) {
            try {
                return $conditions($model, $post) !== false;
            } catch (\Exception $e) {
                // Log the exception or handle it as needed
                // Log::error($e->getMessage());
                return false;
            }
        }

        foreach ($conditions as $condition) {
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
        if (! $conditions || $user->isSuperAdmin()) {
            return true;
        }

        foreach ($conditions as $condition) {
            if ($condition['field'] === 'role' && ! self::checkRoleCondition($condition)) {
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

        if (! $post) {
            $post = $model->getAttributes();
        }

        $cacheKey = md5(get_class($model).json_encode($field).json_encode($post).auth()->id());

        // ray($cacheKey, json_encode($post) );

        return self::checkCondition($model, $field, $post);

        return Cache::remember('conditions_'.$cacheKey, now()->addMinutes(15), function () use ($model, $field, $post) {
            return self::checkCondition($model, $field, $post);
        });
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
            '==' => auth()->user()->hasRole($condition['value']),
            '!=' => ! auth()->user()->hasRole($condition['value']),
            default => false
        };
    }

    private static function handleDefaultCondition($model, $condition, $post)
    {
        if (is_array($model)) {
            $fieldValue = data_get($model, $condition['field']);
        } else {
            $fieldValue = method_exists($model, 'getMeta')
                ? $model->getMeta($condition['field'])
                : data_get($model->post['fields'] ?? [], $condition['field']);

            if (str_contains($condition['field'], '.')) {
                $fieldValue = data_get($model, $condition['field']);
            }
        }

        return self::checkFieldCondition($condition, $fieldValue);
    }

    private static function handleRoleCondition($condition)
    {
        return auth()->user()->isSuperAdmin() || self::checkRoleCondition($condition);
    }
}
