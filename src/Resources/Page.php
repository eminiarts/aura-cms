<?php

namespace Eminiarts\Aura\Aura\Resources;

use Eminiarts\Aura\Models\Post;

class Page extends Post
{
    public static ?string $slug = 'page';

    public static ?int $sort = 1;

    public static string $type = 'Page';

    protected static bool $title = false;

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'global' => true,
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'tab-1',
                'style' => [
                ],
            ],
            [
                'name' => 'Panel 1',
                'type' => 'App\\Aura\\Fields\\Panel',
                'slug' => 'panel-1',
                'style' => [
                    'width' => '70',
                ],
            ],
            [
                'name' => 'Text',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'text',
            ],
            [
                'name' => 'Slug',
                'type' => 'App\\Aura\\Fields\\Slug',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'slug',
                'based_on' => 'text',
            ],
            [
                'name' => 'Panel Tab 1',
                'type' => 'App\\Aura\\Fields\\Tab',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'panel-tab-1',
            ],
            [
                'name' => 'Boolean',
                'type' => 'App\\Aura\\Fields\\Boolean',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'boolean',
            ],
            [
                'name' => 'Color',
                'type' => 'App\\Aura\\Fields\\Color',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'color',
            ],
            [
                'name' => 'Date',
                'type' => 'App\\Aura\\Fields\\Date',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'date',
                'maxDate' => '25',
                'enable_time' => 'true',
                'format' => 'd-m-Y H:i',
            ],
            [
                'name' => 'Textarea',
                'type' => 'App\\Aura\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'textarea_1',
            ],
            [
                'name' => 'Phone',
                'type' => 'App\\Aura\\Fields\\Phone',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'text_2ZzE',
            ],
            [
                'name' => 'Email',
                'type' => 'App\\Aura\\Fields\\Email',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'text_1mx1_j8Ce_cDoX',
            ],
            [
                'name' => 'Datetime',
                'type' => 'App\\Aura\\Fields\\Datetime',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'datetime',
            ],
            [
                'name' => 'Time',
                'type' => 'App\\Aura\\Fields\\Time',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'time',
            ],
            [
                'name' => 'Number',
                'type' => 'App\\Aura\\Fields\\Number',
                'validation' => '',
                'suffix' => '%',
                'conditional_logic' => [
                ],
                'slug' => 'text_1mx1_j8Ce',
            ],
            [
                'name' => 'Text aHoK',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'text_aHoK',
            ],
            [
                'name' => 'Panel Tab 2',
                'type' => 'App\\Aura\\Fields\\Tab',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'panel-tab-2',
            ],
            [
                'name' => 'Wysiwyg',
                'type' => 'App\\Aura\\Fields\\Wysiwyg',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'Wysiwyg',
            ],
            [
                'name' => 'Code',
                'type' => 'App\\Aura\\Fields\\Code',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'code',
                'language' => 'php',
            ],
            [
                'name' => 'Sidebar',
                'type' => 'App\\Aura\\Fields\\Panel',
                'slug' => 'sidebar1',
                'style' => [
                    'width' => '30',
                ],
            ],
            [
                'name' => 'SelectMany',
                'type' => 'App\\Aura\\Fields\\SelectMany',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'select-many',
                'instructions' => 'SelectMany',
                'posttype' => 'App\\Aura\\Resources\\Invoice',
            ],
            [
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [
                ],
            ],
            [
                'name' => 'Panel 3',
                'type' => 'App\\Aura\\Fields\\Panel',
                'slug' => 'panel-3',
                'style' => [
                ],
            ],
            [
                'name' => 'Text 1HcFr2',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'text_HcFr2',
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 9a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }
}
