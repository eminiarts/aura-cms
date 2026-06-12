<?php

use Aura\Base\ConditionalLogic;
use Aura\Base\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

class BasicConditionalLogicOnFieldModel extends Resource
{
    public static ?string $slug = 'page';

    public $text1;

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

describe('basic conditional logic', function () {
    it('shows field when condition is met', function () {
        $model = new BasicConditionalLogicOnFieldModel;
        $model->text1 = 'test';

        $fields = $model->getFields();
        $field = collect($fields)->firstWhere('slug', 'text2');

        $result = ConditionalLogic::checkCondition($model, $field);

        expect($result)->toBeTrue();
    });

    it('hides field when condition is not met', function () {
        $model = new BasicConditionalLogicOnFieldModel;
        $model->text1 = 'different';

        $fields = $model->getFields();
        $field = collect($fields)->firstWhere('slug', 'text2');

        $result = ConditionalLogic::checkCondition($model, $field);

        expect($result)->toBeFalse();
    });

    it('shows field without conditional logic', function () {
        $model = new BasicConditionalLogicOnFieldModel;

        $fields = $model->getFields();
        $field = collect($fields)->firstWhere('slug', 'text1');

        $result = ConditionalLogic::checkCondition($model, $field);

        expect($result)->toBeTrue();
    });
});

describe('operators', function () {
    it('handles equals operator', function () {
        $condition = ['operator' => '==', 'value' => 'test'];
        expect(ConditionalLogic::checkFieldCondition($condition, 'test'))->toBeTrue();
        expect(ConditionalLogic::checkFieldCondition($condition, 'other'))->toBeFalse();
    });

    it('handles not equals operator', function () {
        $condition = ['operator' => '!=', 'value' => 'test'];
        expect(ConditionalLogic::checkFieldCondition($condition, 'other'))->toBeTrue();
        expect(ConditionalLogic::checkFieldCondition($condition, 'test'))->toBeFalse();
    });

    it('handles greater than operator', function () {
        $condition = ['operator' => '>', 'value' => 5];
        expect(ConditionalLogic::checkFieldCondition($condition, 10))->toBeTrue();
        expect(ConditionalLogic::checkFieldCondition($condition, 3))->toBeFalse();
    });

    it('handles less than operator', function () {
        $condition = ['operator' => '<', 'value' => 5];
        expect(ConditionalLogic::checkFieldCondition($condition, 3))->toBeTrue();
        expect(ConditionalLogic::checkFieldCondition($condition, 10))->toBeFalse();
    });

    it('handles greater than or equals operator', function () {
        $condition = ['operator' => '>=', 'value' => 5];
        expect(ConditionalLogic::checkFieldCondition($condition, 5))->toBeTrue();
        expect(ConditionalLogic::checkFieldCondition($condition, 10))->toBeTrue();
        expect(ConditionalLogic::checkFieldCondition($condition, 3))->toBeFalse();
    });

    it('handles less than or equals operator', function () {
        $condition = ['operator' => '<=', 'value' => 5];
        expect(ConditionalLogic::checkFieldCondition($condition, 5))->toBeTrue();
        expect(ConditionalLogic::checkFieldCondition($condition, 3))->toBeTrue();
        expect(ConditionalLogic::checkFieldCondition($condition, 10))->toBeFalse();
    });
});
