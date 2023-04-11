<?php

namespace Eminiarts\Aura\Traits;

trait DefaultFields
{
    public static function fields($key)
    {
        $fields = collect([
            [
                'name' => 'Created at',
                'slug' => 'created_at',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'validation' => '',
                'enable_time' => true,
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Updated at',
                'slug' => 'updated_at',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'enable_time' => true,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'User',
                'slug' => 'user_id',
                'type' => 'Eminiarts\\Aura\\Fields\\BelongsTo',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ]);

        return $fields->where('slug', $key)->first();
    }
}
