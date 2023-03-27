<?php

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// current
uses()->group('current');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

class ConditionalLogicWithClosuresModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'text1',
                'validation' => '',
                'conditional_logic' => [],
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                    function () {
                        return config('aura.teams');
                    },
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

test('Conditional Logic with Closures - config true', function () {
    $model = new ConditionalLogicWithClosuresModel();

    $field = $model->fields()->firstWhere('slug', 'text2');

    dd($field);

    $check = ConditionalLogic::checkCondition($model, 'text2');
});
