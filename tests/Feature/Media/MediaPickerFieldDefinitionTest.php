<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Livewire\Livewire;

class PickerClosureFieldResource extends Resource
{
    public static ?string $slug = 'picker-closure-field';

    public static string $type = 'PickerClosureField';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Cover',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'cover',
                // Documented closure signature: it receives the form.
                'disabled' => fn ($form) => is_array($form),
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

test('an image field with a closure disabled renders its form', function () {
    Aura::fake();
    Aura::setModel(new PickerClosureFieldResource);

    Livewire::test(Create::class, ['slug' => 'picker-closure-field'])
        ->assertOk();
});

test('the picker drops closures from the embedded field definition', function () {
    Livewire::test(MediaUploader::class, [
        'table' => true,
        'model' => app(Attachment::class),
        'field' => [
            'name' => 'Hero',
            'slug' => 'hero',
            'type' => 'Aura\\Base\\Fields\\Image',
            'set' => fn ($model, $field, $value) => $value,
            'display' => fn ($value) => $value,
        ],
    ])
        ->assertSee('Select Hero')
        ->assertSet('field.set', null);
});

test('the picker heading falls back to the slug for an unnamed field', function () {
    Livewire::test(MediaUploader::class, [
        'table' => true,
        'model' => app(Attachment::class),
        'field' => ['slug' => 'cover', 'type' => 'Aura\\Base\\Fields\\Image'],
    ])
        ->assertSee('Select cover');
});
