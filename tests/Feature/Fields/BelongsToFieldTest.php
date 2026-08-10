<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\BelongsTo;
use Aura\Base\Resources\User;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('BelongsTo Field Configuration', function () {
    test('has correct properties', function () {
        $field = new BelongsTo;

        expect($field->optionGroup)->toBe('Relationship Fields')
            ->and($field->edit)->toBe('aura::fields.belongsto')
            ->and($field->view)->toBe('aura::fields.view-value')
            ->and($field->tableColumnType)->toBe('bigInteger')
            ->and($field->type)->toBe('input')
            ->and($field->group)->toBeFalse();
    });

    test('has a resource configuration field', function () {
        $fields = collect((new BelongsTo)->getFields());

        expect($fields->firstWhere('slug', 'resource'))->not->toBeNull();
    });
});

describe('BelongsTo Field Value Handling', function () {
    test('set returns the related id unchanged', function () {
        $field = new BelongsTo;

        expect($field->set(null, [], 5))->toBe(5)
            ->and($field->set(null, [], null))->toBeNull();
    });
});

describe('BelongsTo Field Display', function () {
    test('renders a link to the related resource edit page', function () {
        $related = User::factory()->create(['name' => 'Related User']);

        $field = new BelongsTo;
        $definition = ['resource' => User::class];

        $html = $field->display($definition, $related->id, $this->user);

        // display() resolves the resource slug and builds an anchor to its edit route.
        expect((string) $html)->toContain('<a')
            ->and((string) $html)->toContain(route('aura.user.edit', $related->id));
    });

    test('returns the raw value when no resource is configured', function () {
        $field = new BelongsTo;

        expect($field->display(['resource' => null], 5, $this->user))->toBe(5);
    });

    test('returns the raw value when value is empty', function () {
        $field = new BelongsTo;

        expect($field->display(['resource' => User::class], null, $this->user))->toBeNull();
    });

    test('custom display views return sanitized trusted html', function () {
        $directory = storage_path('framework/testing/core10-belongs-to-view');
        File::ensureDirectoryExists($directory);
        File::put(
            $directory.'/relation.blade.php',
            '<span class="related">{!! $value !!}</span><script>alert(2)</script>',
        );
        View::addNamespace('core10-belongs-to', $directory);

        try {
            $result = (new BelongsTo)->display([
                'display_view' => 'core10-belongs-to::relation',
            ], '<img src=x onerror=alert(1)>Related', $this->user);

            expect($result)->toBeInstanceOf(Htmlable::class)
                ->and((string) $result)->toContain('<span')
                ->toContain('Related')
                ->not->toContain('<script')
                ->not->toContain('onerror');
        } finally {
            File::deleteDirectory($directory);
        }
    });
});

describe('BelongsTo Field Resolve', function () {
    test('valuesForApi returns id/title pairs for the resource', function () {
        User::factory()->count(2)->create();

        $field = new BelongsTo;
        $results = $field->valuesForApi(User::class, null);

        expect($results)->toBeArray()
            ->and($results)->not->toBeEmpty()
            ->and($results[0])->toHaveKeys(['id', 'title']);
    });
});
