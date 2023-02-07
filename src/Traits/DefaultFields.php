<?php

namespace App\Aura\Traits;

trait DefaultFields
{
    public static function fields($key)
    {
        $fields = collect([
            [
                'name' => 'Created at',
                'slug' => 'created_at',
                'type' => 'App\\Aura\\Fields\\Date',
                'validation' => '',
                'enable_time' => true,
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
            ],
            [
                'name' => 'Updated at',
                'slug' => 'updated_at',
                'type' => 'App\\Aura\\Fields\\Date',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'enable_time' => true,
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
            ],
            [
                'name' => 'User',
                'slug' => 'user_id',
                'type' => 'App\\Aura\\Fields\\BelongsTo',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
            ],
        ]);

        return $fields->where('slug', $key)->first();
    }
}
