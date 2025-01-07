<?php

use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($user = User::factory()->create()));

class SaveRessourceFieldsTestModel extends Resource
{
    public static ?string $slug = 'model';

    public static string $type = 'Model';

    public static function getFields()
    {
        return [
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
            ],
        ];
    }
}

test('save defined fields', function () {
    $this->withoutExceptionHandling();

    $this->assertDatabaseMissing('posts', [
        'type' => 'Model',
    ]);

    $model = SaveRessourceFieldsTestModel::create([
        'title' => 'Test',
        'fields' => [
            'text1' => 'Test 1',
            'text2' => 'Test 2',
        ],
    ]);

    $this->assertDatabaseHas('posts', [
        'type' => 'Model',
    ]);

    $savedModel = SaveRessourceFieldsTestModel::first();

    $this->assertEquals($model->id, $savedModel->id);
    $this->assertEquals($savedModel->title, 'Test');
    $this->assertEquals($savedModel->text1, 'Test 1');
    $this->assertEquals($savedModel->text2, 'Test 2');

    // try {
    //     $this->assertDatabaseMissing('posts', [
    //         'type' => 'Model',
    //     ]);

    //     $model = SaveRessourceFieldsTestModel::create([
    //         'title' => 'Test',
    //         'fields' => [
    //             'text1' => 'Test 1',
    //             'text2' => 'Test 2',
    //         ],
    //     ]);

    //     $this->assertDatabaseHas('posts', [
    //         'type' => 'Model',
    //     ]);

    //     $savedModel = SaveRessourceFieldsTestModel::first();

    //     $savedModel->clearModelCache();

    //     $this->assertEquals($model->id, $savedModel->id);
    //     $this->assertEquals($savedModel->title, 'Test');
    //     $this->assertEquals($savedModel->text1, 'Test 1');
    //     $this->assertEquals($savedModel->text2, 'Test 2');
    // } catch (\Throwable $th) {
    // }
});

test('can not save fields that are not defined', function () {
    $this->assertDatabaseMissing('posts', [
        'type' => 'Model',
    ]);

    $model = SaveRessourceFieldsTestModel::create([
        'title' => 'Test',
        'fields' => [
            'text1' => 'Test 1',
            'text2' => 'Test 2',
            'text3' => 'Test 3',
        ],
    ]);

    $this->assertDatabaseHas('posts', [
        'type' => 'Model',
    ]);

    $savedModel = SaveRessourceFieldsTestModel::first();

    $this->assertEquals($model->id, $savedModel->id);
    $this->assertEquals($savedModel->title, 'Test');
    $this->assertEquals($savedModel->text1, 'Test 1');

    $this->assertEquals($savedModel->text2, 'Test 2');
    $this->assertEquals($savedModel->text3, null);

    $this->assertEquals($savedModel->fields['text1'], 'Test 1');
    $this->assertEquals($savedModel->fields['text2'], 'Test 2');

    // Assert field $savedModel->fields['text3'] is not set
    $this->assertArrayNotHasKey('text3', $savedModel->fields);
});

test('save defined meta fields even if not defined in fields array', function () {
    $this->assertDatabaseMissing('posts', [
        'type' => 'Model',
    ]);

    $model = SaveRessourceFieldsTestModel::create([
        'title' => 'New Test',
        'text1' => 'Test 1',
        'text2' => 'Test 2',
        'text3' => 'Test 3',
        'text4' => 'Test 4',
    ]);

    $this->assertDatabaseHas('posts', [
        'type' => 'Model',
    ]);

    $savedModel = SaveRessourceFieldsTestModel::first();

    $this->assertEquals($model->id, $savedModel->id);
    $this->assertEquals($savedModel->title, 'New Test');
    $this->assertEquals($savedModel->text1, 'Test 1');

    $this->assertEquals($savedModel->text2, 'Test 2');
    $this->assertEquals($savedModel->text3, null);

    $this->assertEquals($savedModel->fields['text1'], 'Test 1');
    $this->assertEquals($savedModel->fields['text2'], 'Test 2');

    // Assert field $savedModel->fields['text3'] is not set
    $this->assertArrayNotHasKey('text3', $savedModel->fields);
    $this->assertArrayNotHasKey('text4', $savedModel->fields);
});
