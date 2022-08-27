<?php

namespace Eminiarts\Aura\Resources;

use App\Aura\Widgets\PostStats;
use App\Models\Post;

class Invoice extends Post
{
    public static string $type = 'Invoice';

    public static ?string $slug = 'invoice';

    public static $fields = [];

    public array $bulkActions = [
        'exportSelected' => 'Export',
        'deleteSelected' => 'Delete',
        'enableSelected' => 'Enable',
    ];

    public function exportSelected()
    {
        dd('export');
    }

    public function deleteSelected()
    {
        $this->delete();
    }

    public function enableSelected()
    {
        $this->attributes['fields']['enabled'] = true;

        $this->save();
    }

    public function rowView()
    {
        return 'invoices.row';
    }

    // public static function getWidgets(): array
    // {
    //     return [
    //         (new PostStats())->width('1/3'),
    //     ];
    // }

    public function icon()
    {
        return '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
    }

    public function getTotalAttribute()
    {
        return $this->meta->total.' CHF';
    }

    public static function getFields()
    {
        return [
            'tab1' => [
                'name' => 'Tab 1',
                'slug' => 'tab1',
                'type' => 'App\\Aura\\Fields\\Tab',
                'validation' => '',
                'conditional_logic' => [
                ],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],
            'tab1.boolean' => [
                'name' => 'Enabled',
                'slug' => 'enabled',
                'type' => 'App\\Aura\\Fields\\Boolean',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'wrapper' => '',
                'style' => [
                    'width' => '50',
                ],
                'instructions' => 'Shows if it is enabled',
            ],
            'tab1.total' => [
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [
                ],
                'slug' => 'total',
            ],
            'repeater' => [
                'name' => 'Repeater',
                'slug' => 'repeater',
                'type' => 'App\\Aura\\Fields\\Repeater',
                'validation' => '',
                'on_index' => false,
                'conditional_logic' => [
                ],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],
            'repeater.description' => [
                'label' => 'Text',
                'name' => 'Beschreibung',
                'type' => 'App\\Aura\\Fields\\Text',
                'conditional_logic' => [
                ],
                'slug' => 'description',
                'style' => [
                    'width' => '50',
                ],
            ],
            'repeater.number' => [
                'label' => 'Number',
                'name' => 'Number',
                'on_index' => false,
                'type' => 'App\\Aura\\Fields\\Number',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'slug' => 'number',
                'style' => [
                    'width' => '50',
                ],
            ],
            'panel1' => [
                'name' => 'Panel 2',
                'slug' => 'panel1',
                'on_index' => false,
                'type' => 'App\\Aura\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [
                ],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],
            'panel1.description3' => [
                'label' => 'Text',
                'name' => 'Beschreibung',
                'on_index' => false,
                'type' => 'App\\Aura\\Fields\\Text',
                'conditional_logic' => [
                ],
                'slug' => 'description3',
            ],
            'panel1.bild' => [
                'label' => 'Image',
                'name' => 'Bild',
                'on_index' => false,
                'type' => 'App\\Aura\\Fields\\Image',
                'conditional_logic' => [
                ],
                'validation' => 'nullable',
                'wrapper' => [
                    'width' => '',
                    'class' => 'custom-image',
                    'id' => '',
                ],
                'slug' => 'bild',
            ],

            'panel1.file' => [
                'label' => 'Image',
                'name' => 'file',
                'on_index' => false,
                'type' => 'App\\Aura\\Fields\\File',
                'conditional_logic' => [
                ],
                'validation' => 'nullable',
                'wrapper' => [
                    'width' => '',
                    'class' => 'custom-image',
                    'id' => '',
                ],
                'slug' => 'file',
            ],
        ];
    }
}
