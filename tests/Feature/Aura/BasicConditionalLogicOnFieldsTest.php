<?php

use Aura\Base\Models\User;
use Aura\Base\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

class BasicConditionalLogicOnFieldModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'text1',
                'validation' => '',
                'conditional_logic' => [],
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'text1',
                        'operator' => '==',
                        'value' => 'test',
                    ],
                ],
                'slug' => 'text2',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

test('Field X gets shown when value of Y is true', function () {
    //    $model = new BasicConditionalLogicOnFieldModel();
    //
    //    $model->save();
    //
    //    $model->update([
    //        'fields' => [
    //            'text1' => 'test',
    //        ],
    //    ]);

    // $this->assertCount(1, $fields);
})->todo();
