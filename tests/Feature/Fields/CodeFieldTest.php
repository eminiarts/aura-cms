<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Code;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class CodeFieldModel extends Resource
{
    public static $singularName = 'Code Model';

    public static ?string $slug = 'codemodel';

    public static string $type = 'CodeModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Code for Test',
                'type' => 'Aura\\Base\\Fields\\Code',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'code',
                'language' => 'php',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Code Field Configuration', function () {
    test('has correct option group and edit property', function () {
        $field = new Code;

        expect($field->optionGroup)->toBe('JS Fields')
            ->and($field->edit)->toBe('aura::fields.code')
            ->and($field->edit())->toBe('aura::fields.code');
    });

    test('has language, line_numbers and min_height configuration fields', function () {
        $fields = collect((new Code)->getFields());

        expect($fields->firstWhere('slug', 'language'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'line_numbers'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'min_height'))->not->toBeNull();

        $language = $fields->firstWhere('slug', 'language');
        expect($language['options'])->toHaveKeys(['html', 'css', 'javascript', 'php', 'json']);
    });
});

describe('Code Field Value Handling', function () {
    test('get pretty-prints valid JSON', function () {
        $field = new Code;

        $result = $field->get(null, '{"a":1,"b":2}');

        expect($result)->toBe(json_encode(['a' => 1, 'b' => 2], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
            ->and($result)->toContain("\n");
    });

    test('get returns non-JSON strings unchanged', function () {
        $field = new Code;

        // A bare PHP snippet is not valid JSON, so it is returned verbatim.
        expect($field->get(null, '<?php echo "hi";'))->toBe('<?php echo "hi";');
    });

    test('set returns value unchanged', function () {
        expect((new Code)->set(null, [], 'echo 1;'))->toBe('echo 1;');
    });
});

describe('Code Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new CodeFieldModel);
    });

    test('saves code value round-trip', function () {
        Livewire::test(Create::class, ['slug' => 'codemodel'])
            ->set('form.fields.code', '<?php echo "hi";')
            ->call('save')
            ->assertHasNoErrors(['form.fields.code']);

        $model = CodeFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['code'])->toBe('<?php echo "hi";');
    });
});
