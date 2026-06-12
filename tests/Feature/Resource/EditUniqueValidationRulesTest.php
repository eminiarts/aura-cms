<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Livewire\Livewire;

class EditUniqueValidationRulesTestModel extends Resource
{
    public static ?string $slug = 'edit-unique-validation';

    public static string $type = 'EditUniqueValidation';

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'title',
            ],
            [
                'name' => 'Slug',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|unique:posts,slug',
                'slug' => 'slug',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new EditUniqueValidationRulesTestModel);
});

test('edit ignores the current record for string based unique rules', function () {
    $post = EditUniqueValidationRulesTestModel::create([
        'title' => 'Initial title',
        'slug' => 'initial-title',
    ]);

    Livewire::test(Edit::class, ['slug' => 'edit-unique-validation', 'id' => $post->id])
        ->set('form.fields.title', 'Updated title')
        ->call('save')
        ->assertHasNoErrors(['form.fields.slug']);

    expect($post->fresh()->title)->toBe('Updated title')
        ->and($post->fresh()->slug)->toBe('initial-title');
});
