<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Resource;
use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Widgets\PostChart;
use Eminiarts\Aura\Widgets\TotalPosts;
use Eminiarts\Aura\Widgets\AvgPostsNumber;
use Eminiarts\Aura\Widgets\SumPostsNumber;
use Eminiarts\Aura\Models\Post as ModelsPost;

class Post extends Resource
{
    public array $bulkActions = [
        'deleteSelected' => 'Delete',
    ];

    public static $fields = [];

    public static ?string $slug = 'post';

    public static ?int $sort = 0;

    public static string $type = 'Post';

    protected static ?string $group = 'Posts';

    protected static array $searchable = [
        'title',
        'content',
    ];

    public function callFlow($flowId)
    {
        $flow = Flow::find($flowId);
        // dd('callManualFlow', $flow->name);
        $operation = $flow->operation;

        // Create a Flow Log
        $flowLog = $flow->logs()->create([
            'post_id' => $this->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Run the Operation
        $operation->run($this, $flowLog->id);
    }

    public function deleteSelected()
    {
        $this->delete();
    }

    public function getBulkActions()
    {
        // get all flows with type "manual"

        $flows = Flow::where('trigger', 'manual')
            ->where('options->resource', $this->getType())
            ->get();

        foreach ($flows as $flow) {
            $this->bulkActions['callFlow.'.$flow->id] = $flow->name;
        }

        // dd($this->bulkActions);
        return $this->bulkActions;
    }

     public static function getFields()
     {
         return [
             [
                 'name' => 'Tab',
                 'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                 'validation' => '',
                 'on_index' => true,
                 'global' => true,
                 'has_conditional_logic' => false,
                 'conditional_logic' => [
                 ],
                 'slug' => 'tab1',
             ],
             [
                 'name' => 'Panel',
                 'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                 'validation' => '',
                 'on_index' => true,
                 'has_conditional_logic' => false,
                 'conditional_logic' => [
                 ],
                 'slug' => 'panel1',
                 'style' => [
                     'width' => '70',
                 ],
             ],
             [
                 'name' => 'Text',
                 'slug' => 'text',
                 'type' => 'Eminiarts\\Aura\\Fields\\Text',
                 'validation' => '',
                 'conditional_logic' => [],
                 'has_conditional_logic' => false,
                 'wrapper' => '',
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             [
                 'name' => 'Slug for Test',
                 'type' => 'Eminiarts\\Aura\\Fields\\Slug',
                 'validation' => 'required|alpha_dash',
                 'conditional_logic' => [
                 ],
                 'slug' => 'slug2',
                 'based_on' => 'text',
             ],
             [
                 'name' => 'Bild',
                 'type' => 'Eminiarts\\Aura\\Fields\\Image',
                 'validation' => '',
                 'conditional_logic' => [
                 ],
                 'slug' => 'image',
             ],
             [
                 'name' => 'Password for Test',
                 'type' => 'Eminiarts\\Aura\\Fields\\Password',
                 'validation' => 'nullable|min:8',
                 'conditional_logic' => [
                 ],
                 'slug' => 'password',
                 'on_index' => false,
                 'on_forms' => true,
                 'on_view' => false,
             ],
             [
                 'name' => 'Number',
                 'type' => 'Eminiarts\\Aura\\Fields\\Number',
                 'validation' => '',
                 'conditional_logic' => [
                 ],
                 'slug' => 'number',
                 'on_view' => true,
                 'on_forms' => true,
                 'on_index' => true,
             ],
             [
                 'name' => 'Date',
                 'type' => 'Eminiarts\\Aura\\Fields\\Date',
                 'validation' => '',
                 'conditional_logic' => [
                 ],
                 'slug' => 'date',
                 'format' => 'y-m-d',
             ],
             [
                 'name' => 'Description',
                 'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                 'validation' => '',
                 'conditional_logic' => [
                 ],
                 'slug' => 'description',
                 'style' => [
                     'width' => '100',
                 ],
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             [
                 'name' => 'Color',
                 'type' => 'Eminiarts\\Aura\\Fields\\Color',
                 'validation' => '',
                 'conditional_logic' => [
                 ],
                 'slug' => 'color',
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
                 'format' => 'hex',
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
                     'width' => '30',
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
                 'wrapper' => '',
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
                 'wrapper' => '',
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             //  [
             //      'name' => 'Team',
             //      'slug' => 'team_id',
             //      'type' => 'Eminiarts\\Aura\\Fields\\BelongsTo',
             //      'resource' => 'Eminiarts\\Aura\\Resources\\Team',
             //      'validation' => '',
             //      'conditional_logic' => [
             //          [
             //              'field' => 'role',
             //              'operator' => '==',
             //              'value' => 'super_admin',
             //          ],
             //      ],
             //      'has_conditional_logic' => false,
             //      'wrapper' => '',
             //      'on_index' => true,
             //      'on_forms' => true,
             //      'on_view' => true,
             //  ],
             [
                 'name' => 'User',
                 'slug' => 'user_id',
                 'type' => 'Eminiarts\\Aura\\Fields\\BelongsTo',
                 'resource' => 'Eminiarts\\Aura\\Resources\\User',
                 'validation' => '',
                 'conditional_logic' => [],
                 'has_conditional_logic' => false,
                 'wrapper' => '',
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             [
                 'name' => 'Attachments',
                 'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                 'validation' => '',
                 'on_index' => true,
                 'global' => true,
                 'has_conditional_logic' => false,
                 'conditional_logic' => [
                 ],
                 'slug' => 'tab2',
             ],
             [
                 'name' => 'Attachments',
                 'slug' => 'attachments',
                 'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                 'resource' => 'Eminiarts\\Aura\\Resources\\Attachment',
                 'validation' => '',
                 'conditional_logic' => [],
                 'has_conditional_logic' => false,
                 'wrapper' => '',
                 'on_index' => false,
                 'on_forms' => true,
                 'on_view' => true,
                 'style' => [
                     'width' => '100',
                 ],
             ],
         ];
     }

    public function getIcon()
    {
        return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>';
    }

    public static function getWidgets(): array
    {
        return [
            // new TotalPosts(['width' => 'w-full md:w-1/3']),
            // new SumPostsNumber(['width' => 'w-full md:w-1/3']),
            // new AvgPostsNumber(['width' => 'w-full md:w-1/3']),
            new PostChart(['width' => 'w-full md:w-1/3']),
        ];
    }

    public function title()
    {
        return $this->title." (Post #{$this->id})";
    }
}
