<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\Permissions;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resources\Permission;

use function Pest\Livewire\livewire;

describe('Permissions Field Configuration', function () {
    test('has correct edit and view properties', function () {
        $field = new Permissions;

        expect($field->edit)->toBe('aura::fields.permissions')
            ->and($field->view)->toBe('aura::fields.permissions-view');
    });

    test('has resource configuration field', function () {
        $fields = collect((new Permissions)->getFields());

        expect($fields->firstWhere('slug', 'resource'))->not->toBeNull();
    });
});

describe('Permissions Field Value Handling', function () {
    test('get decodes JSON strings into arrays', function () {
        $field = new Permissions;

        expect($field->get(null, '{"view-post":true}'))->toBe(['view-post' => true]);
    });

    test('get returns arrays unchanged', function () {
        $field = new Permissions;

        expect($field->get(null, ['view-post' => true]))->toBe(['view-post' => true]);
    });

    test('set encodes value as JSON', function () {
        $field = new Permissions;

        expect($field->set(null, [], ['view-post' => true]))->toBe('{"view-post":true}');
    });

    test('round-trips permissions through set then get', function () {
        $field = new Permissions;
        $value = ['view-post' => true, 'delete-post' => false];

        expect($field->get(null, $field->set(null, [], $value)))->toBe($value);
    });
});

describe('Permissions Field Matrix', function () {
    test('puts generated abilities in their column and custom permissions in other', function () {
        $rows = Permissions::matrix([
            ['name' => 'View Posts', 'description' => null, 'slug' => 'view-post', 'group' => 'Posts'],
            ['name' => 'View Any Posts', 'description' => null, 'slug' => 'viewAny-post', 'group' => 'Posts'],
            ['name' => 'Scope Posts', 'description' => null, 'slug' => 'scope-post', 'group' => 'Posts'],
            ['name' => 'Export Posts', 'description' => null, 'slug' => 'export-post', 'group' => 'Posts'],
        ]);

        expect(array_keys($rows))->toBe(['Posts'])
            ->and($rows['Posts']['cells']['view']['slug'])->toBe('view-post')
            ->and($rows['Posts']['cells']['viewAny']['slug'])->toBe('viewAny-post')
            ->and($rows['Posts']['cells']['scope']['slug'])->toBe('scope-post')
            ->and($rows['Posts']['cells']['delete'])->toBeNull()
            ->and(array_column($rows['Posts']['other'], 'slug'))->toBe(['export-post']);
    });

    test('role form keeps custom permissions assignable next to the matrix', function () {
        $this->actingAs(createSuperAdmin());

        Permission::create(['name' => 'View Posts', 'slug' => 'view-post', 'group' => 'Posts']);
        Permission::create(['name' => 'Export Posts', 'slug' => 'export-post', 'group' => 'Posts']);

        livewire(Create::class, ['slug' => 'role'])
            ->assertSeeHtml('id="permissions_view-post"')
            ->assertSeeHtml('id="permissions_export-post"');
    });
});
