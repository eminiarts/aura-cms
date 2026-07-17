<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Embed;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class EmbedFieldModel extends Resource
{
    public static $singularName = 'Embed Model';

    public static ?string $slug = 'embedmodel';

    public static string $type = 'EmbedModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Embed for Test',
                'type' => 'Aura\\Base\\Fields\\Embed',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'embed',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Embed Field Configuration', function () {
    test('uses the same view for edit and view', function () {
        $field = new Embed;

        expect($field->edit)->toBe('aura::fields.embed')
            ->and($field->view)->toBe('aura::fields.embed')
            ->and($field->edit())->toBe('aura::fields.embed')
            ->and($field->view())->toBe('aura::fields.embed');
    });
});

describe('Embed Field Value Handling', function () {
    test('get and value return the value unchanged', function () {
        $field = new Embed;
        $url = 'https://youtube.com/watch?v=abc';

        expect($field->get(null, $url))->toBe($url)
            ->and($field->value($url))->toBe($url);
    });
});

describe('Embed Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new EmbedFieldModel);
    });

    test('saves embed value round-trip', function () {
        $url = 'https://youtube.com/watch?v=abc';

        Livewire::test(Create::class, ['slug' => 'embedmodel'])
            ->set('form.fields.embed', $url)
            ->call('save')
            ->assertHasNoErrors(['form.fields.embed']);

        $model = EmbedFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['embed'])->toBe($url);
    });
});
