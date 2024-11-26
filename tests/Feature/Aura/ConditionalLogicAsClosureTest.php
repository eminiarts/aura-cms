<?php

use Aura\Base\ConditionalLogic;
use Aura\Base\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

class ConditionalLogicAsClosureModel extends Resource
{
    public static ?string $slug = 'page';

    public $text1;

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'text1',
                'validation' => '',
                'conditional_logic' => [],
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => function ($model, $post) {
                    //ray($model, $post);
                    dump($post['fields']['text1'])->red();

                    return $post['fields']['text1'] === 'test';
                },
                'slug' => 'text2',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

test('Field with a conditional logic closure is accessible', function () {
    $model = ConditionalLogicAsClosureModel::create(
        [
            'text1' => 'test',
            'text2' => 'secret'
        ]
    );

    expect($model->text2)->toEqual('secret');
});
