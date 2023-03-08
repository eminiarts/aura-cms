<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Widgets\PostStats;

class Invoice extends Post
{
    public array $bulkActions = [
        'exportSelected' => 'Export',
        'deleteSelected' => 'Delete',
        'enableSelected' => 'Enable',
    ];

    public static array $fields = [];

    public static $singularName = 'Invoice';

    public static ?string $slug = 'invoice';

    public static ?int $sort = 1;

    public static string $type = 'Invoice';

    public function deleteSelected()
    {
        $this->delete();
    }

    public function enableSelected()
    {
        $this->attributes['fields']['enabled'] = true;

        $this->save();
    }

    public function exportSelected()
    {
        dd('export');
    }

    public static function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Invoice',
                'slug' => 'tab-invoice',
                'global' => true,
            ],
            [
                'name' => 'Debtor',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'panel-debtor',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'User',
                'slug' => 'user_id',
                'type' => 'Eminiarts\\Aura\\Fields\\BelongsTo',
                'model' => 'Eminiarts\\Aura\\Resources\\User',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Infos',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'panel-infos',
                'style' => [
                    'width' => '66',
                ],
            ],
            [
                'name' => 'Invoice Date',
                'slug' => 'date',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'format' => 'd.m.Y',
                'validation' => 'required',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Invoice Number',
                'slug' => 'invoice_number',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Due Date',
                'slug' => 'due_date',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'format' => 'd.m.Y',
                'validation' => 'required',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Reference',
                'slug' => 'reference',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Delivery Date',
                'slug' => 'delivery_date',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'format' => 'd.m.Y',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Bank connection',
                'slug' => 'bank',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Title',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'panel-title',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Invoice Items',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'panel-items',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Invoice Items',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'validation' => '',
                'on_index' => false,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'items-repeater',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Product',
                'slug' => 'product',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'on_index' => false,
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '10',
                ],
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'on_index' => false,
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '40',
                ],
            ],
            [
                'name' => 'Price',
                'slug' => 'item_price',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'suffix' => 'CHF',
                'validation' => '',
                'on_index' => false,
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '10',
                ],
            ],
            [
                'name' => 'Quantity',
                'slug' => 'quantity',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'on_index' => false,
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '10',
                ],
            ],
            [
                'name' => 'Type',
                'slug' => 'Type',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'options' => [
                    'hours' => 'Hours',
                    'days' => 'Days',
                    'pieces' => 'Pieces',
                ],
                'validation' => '',
                'on_index' => false,
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '10',
                ],
            ],
            [
                'name' => 'Tax',
                'slug' => 'tax',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'options' => [
                    '0%',
                    '2.5' => '2.5%',
                    '7.7' => '7.7%',
                ],
                'validation' => '',
                'on_index' => false,
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '10',
                ],
            ],
            [
                'name' => 'Sum',
                'slug' => 'item_sum',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'suffix' => 'CHF',
                'validation' => '',
                'on_index' => false,
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '10',
                ],
            ],
            [
                'name' => 'Notes',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'panel-notes',
                'style' => [
                    'width' => '50',
                    'class' => '!bg-yellow-100',
                ],
            ],
            [
                'name' => 'Public Notes',
                'slug' => 'public_notes',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Foot Notes',
                'slug' => 'foot_notes',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Invoice Sum',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'panel-sum',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Discount',
                'slug' => 'discount',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Discount Type',
                'slug' => 'discount_type',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'options' => [
                    'percent' => 'Percent',
                    'amount' => 'Amount',
                ],
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Invoice Sum',
                'slug' => 'invoice_sum',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Tab',
                'slug' => 'tab-6PW8',
                'global' => true,
            ],
            [
                'name' => 'Sidebar',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'sidebar',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Eminiarts\\Aura\\Fields\\Tags',
                'model' => 'Eminiarts\\Aura\\Taxonomies\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Categories',
                'slug' => 'categories',
                'type' => 'Eminiarts\\Aura\\Fields\\Tags',
                'model' => 'Eminiarts\\Aura\\Taxonomies\\Category',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Panel 2',
                'slug' => 'panel1',
                'on_index' => false,
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [
                ],
                'has_conditional_logic' => false,
            ],
            [
                'name' => 'Text Yy5q',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'text_Yy5q',
            ],
        ];
    }

    // public function rowView()
    // {
    //     return 'invoices.row';
    // }

    // public static function getWidgets(): array
    // {
    //     return [
    //         (new PostStats())->width('1/3'),
    //     ];
    // }

    public function getIcon()
    {
        return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
    }

    public function getTotalAttribute()
    {
        return $this->meta->total.' CHF';
    }

    public function getUser_idField($value)
    {
        return "<a class='font-bold text-primary-500' href='mailto:".$value."'>".$value.'</a>';
    }
}
