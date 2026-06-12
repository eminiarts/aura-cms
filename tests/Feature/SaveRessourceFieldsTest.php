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

test('can save defined fields through fields array', function () {
    $this->withoutExceptionHandling();

    $this->assertDatabaseMissing('posts', ['type' => 'Model']);

    $model = SaveRessourceFieldsTestModel::create([
        'title' => 'Test',
        'fields' => [
            'text1' => 'Test 1',
            'text2' => 'Test 2',
        ],
    ]);

    $this->assertDatabaseHas('posts', ['type' => 'Model']);

    $savedModel = SaveRessourceFieldsTestModel::first();

    expect($savedModel->id)->toBe($model->id)
        ->and($savedModel->title)->toBe('Test')
        ->and($savedModel->text1)->toBe('Test 1')
        ->and($savedModel->text2)->toBe('Test 2');
});

test('undefined fields are not saved', function () {
    $this->assertDatabaseMissing('posts', ['type' => 'Model']);

    $model = SaveRessourceFieldsTestModel::create([
        'title' => 'Test',
        'fields' => [
            'text1' => 'Test 1',
            'text2' => 'Test 2',
            'text3' => 'Test 3',
        ],
    ]);

    $this->assertDatabaseHas('posts', ['type' => 'Model']);

    $savedModel = SaveRessourceFieldsTestModel::first();

    expect($savedModel->id)->toBe($model->id)
        ->and($savedModel->title)->toBe('Test')
        ->and($savedModel->text1)->toBe('Test 1')
        ->and($savedModel->text2)->toBe('Test 2')
        ->and($savedModel->text3)->toBeNull()
        ->and($savedModel->fields['text1'])->toBe('Test 1')
        ->and($savedModel->fields['text2'])->toBe('Test 2')
        ->and($savedModel->fields)->not->toHaveKey('text3');
});

test('fields passed directly are saved if defined', function () {
    $this->assertDatabaseMissing('posts', ['type' => 'Model']);

    $model = SaveRessourceFieldsTestModel::create([
        'title' => 'New Test',
        'text1' => 'Test 1',
        'text2' => 'Test 2',
        'text3' => 'Test 3',
        'text4' => 'Test 4',
    ]);

    $this->assertDatabaseHas('posts', ['type' => 'Model']);

    $savedModel = SaveRessourceFieldsTestModel::first();

    expect($savedModel->id)->toBe($model->id)
        ->and($savedModel->title)->toBe('New Test')
        ->and($savedModel->text1)->toBe('Test 1')
        ->and($savedModel->text2)->toBe('Test 2')
        ->and($savedModel->text3)->toBeNull()
        ->and($savedModel->fields['text1'])->toBe('Test 1')
        ->and($savedModel->fields['text2'])->toBe('Test 2')
        ->and($savedModel->fields)->not->toHaveKey('text3')
        ->and($savedModel->fields)->not->toHaveKey('text4');
});
