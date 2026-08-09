<?php

namespace Aura\Base;

use Closure;
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

        if ($conditions instanceof Closure) {
            $result = self::executeClosure($conditions, $model, $post);

            return $result;
        }

        if (! is_array($conditions)) {

            return true;
        }

        foreach ($conditions as $index => $condition) {

            if ($condition instanceof Closure) {
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

    public static function clearConditionsCache(): void
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

        if (! is_array($field) || ! self::canCacheDecision($model, $field, $post)) {
            return self::checkCondition($model, $field, $post);
        }

        $cacheKey = hash('sha256', serialize([
            'model' => $model,
            'field' => $field,
            'post' => $post,
            'user_id' => Auth::id(),
        ]));

        return self::$shouldDisplayFieldCache[$cacheKey]
            ??= self::checkCondition($model, $field, $post);
    }

    /**
     * Resource conditions may read mutable model/meta state, provider-captured
     * closures may close over a team or user, and role membership can change
     * without the authenticated user's ID changing. Evaluate those decisions
     * directly rather than collapsing them into an unsafe process-static key.
     */
    private static function canCacheDecision($model, array $field, $post): bool
    {
        if (! is_array($model) || ! is_array($post)) {
            return false;
        }

        return self::isCacheableValue($model)
            && self::isCacheableValue($field)
            && self::isCacheableValue($post)
            && ! self::containsRoleCondition($field['conditional_logic']);
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

    private static function containsRoleCondition(mixed $conditions): bool
    {
        if (! is_array($conditions)) {
            return false;
        }

        if (($conditions['field'] ?? null) === 'role') {
            return true;
        }

        foreach ($conditions as $condition) {
            if (self::containsRoleCondition($condition)) {
                return true;
            }
        }

        return false;
    }

    private static function executeClosure(Closure $closure, $model, $post = null)
    {
        try {
            // If $post is null, create a fields array from the model
            if ($post === null) {
                $fields = $model->getFieldsWithoutConditionalLogic();
                $post = ['fields' => $fields];
            }

            return $closure($model, $post);
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
        $user = Auth::user();

        // Super admins should have access to all role-based fields
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($condition['operator'] === '==' && $condition['value'] === 'super_admin') {
            return $user->isSuperAdmin();
        }

        return self::checkRoleCondition($condition);
    }

    private static function isCacheableValue(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if ((! is_int($key) && ! is_string($key)) || ! self::isCacheableValue($item)) {
                    return false;
                }
            }

            return true;
        }

        return is_scalar($value) || $value === null;
    }
}
