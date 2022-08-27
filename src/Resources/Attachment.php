<?php

namespace Eminiarts\Aura\Resources;

use App\Models\Post;

class Attachment extends Post
{
    public static string $type = 'Attachment';

    public static ?string $name = 'Media';

    public static ?string $slug = 'attachment';

    public static function getWidgets(): array
    {
        return [];
    }

    public function icon()
    {
        return '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>';
    }

    public static function getFields()
    {
        return [
            'title' => [
                'label' => 'Title',
                'name' => 'Title',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'title',
                'style' => [
                    'width' => '100',
                ],
            ],
            'user' => [
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
            'file' => [
                'name' => 'File',
                'slug' => 'file',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
            ],
             'created-at' => [
                'name' => 'Created at',
                'slug' => 'created_at',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
            ],
        ];
    }
}
