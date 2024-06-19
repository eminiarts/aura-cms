<?php

namespace Aura\Base;

class ConditionalLogic
{
    private static $shouldDisplayFieldCache = [];

    public static function checkCondition($model, $field, $post = null)
{
    $conditions = $field['conditional_logic'] ?? null;

    // Check if conditions are set and if the user is authenticated
    if (! $conditions || ! auth()->user()) {
        return true;
    }

    ray('he', auth()->user()?->id, $field, $conditions)->blue();

    // Ensure $conditions is an array or a closure
    if (!is_array($conditions) && !($conditions instanceof \Closure)) {
        return true;
    }

    // If $conditions is a closure, execute it
    if ($conditions instanceof \Closure) {
        try {
            return $conditions($model, $post) !== false;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            ray($e->getMessage())->red();
            return false;
        }
    }

    foreach ($conditions as $condition) {
        ray($condition)->green();

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
        if (! $conditions || $user->isSuperAdmin()) {
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
        if (! $field) {
            return true;
        }

        if (empty($field['conditional_logic'])) {
            return true;
        }

        $cacheKey = md5(get_class($model).json_encode($field['slug']).json_encode($post ? $post['id'] : null) . auth()->user()?->id );

        ray($cacheKey, $field['slug'], auth()->user()?->id, self::$shouldDisplayFieldCache[$cacheKey]
            ?? null)->purple();

            // return self::checkCondition($model, $field, $post);

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
            '==' => auth()->user()->hasRole($condition['value']),
            '!=' => ! auth()->user()->hasRole($condition['value']),
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
        if (auth()->user()->isSuperAdmin()) {
            return true;
        }

        return self::checkRoleCondition($condition);
    }
}
