<?php

use Aura\Base\Resources\User;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('user create form hides current_team_id field', function () {
    $user = new User;

    $createFields = $user->createFields();

    $allSlugs = collectSlugs($createFields);

    expect($allSlugs)->not->toContain('current_team_id');
});

test('user create form hides avatar field', function () {
    $user = new User;

    $createFields = $user->createFields();

    $allSlugs = collectSlugs($createFields);

    expect($allSlugs)->not->toContain('avatar');
});

test('user create form shows essential fields', function () {
    $user = new User;

    $createFields = $user->createFields();

    $allSlugs = collectSlugs($createFields);

    expect($allSlugs)->toContain('name');
    expect($allSlugs)->toContain('email');
    expect($allSlugs)->toContain('roles');
    expect($allSlugs)->toContain('password');
});

test('user edit form shows avatar field', function () {
    $user = new User;

    $editFields = $user->editFields();

    $allSlugs = collectSlugs($editFields);

    expect($allSlugs)->toContain('avatar');
});

test('user edit form hides current_team_id field', function () {
    $user = new User;

    $editFields = $user->editFields();

    $allSlugs = collectSlugs($editFields);

    expect($allSlugs)->not->toContain('current_team_id');
});

test('user name and email fields have 50% width', function () {
    $fields = collect(User::getFields());

    $name = $fields->firstWhere('slug', 'name');
    $email = $fields->firstWhere('slug', 'email');

    expect($name['style']['width'])->toBe('50');
    expect($email['style']['width'])->toBe('50');
});

test('user roles and password fields have 50% width', function () {
    $fields = collect(User::getFields());

    $roles = $fields->firstWhere('slug', 'roles');
    $password = $fields->firstWhere('slug', 'password');

    expect($roles['style']['width'])->toBe('50');
    expect($password['style']['width'])->toBe('50');
});

test('current_team_id field has on_forms set to false', function () {
    $fields = collect(User::getFields());

    $currentTeam = $fields->firstWhere('slug', 'current_team_id');

    expect($currentTeam)->not->toBeNull();
    expect($currentTeam['on_forms'])->toBeFalse();
});

test('avatar field has on_create set to false', function () {
    $fields = collect(User::getFields());

    $avatar = $fields->firstWhere('slug', 'avatar');

    expect($avatar)->not->toBeNull();
    expect($avatar['on_create'])->toBeFalse();
});

test('roles field is visible on index', function () {
    $fields = collect(User::getFields());

    $roles = $fields->firstWhere('slug', 'roles');

    expect($roles['on_index'])->toBeTrue();
});

test('roles field displays role names in index', function () {
    $user = $this->user;

    // The display method should show role names, not IDs
    $displayed = $user->display('roles');

    expect($displayed)->toContain('Admin');
});

test('user index headers include roles column', function () {
    $user = new User;

    $headers = $user->getHeaders();

    expect($headers)->toHaveKey('roles');
    expect($headers['roles'])->toBe('Role');
});

test('roles field is single select', function () {
    $fields = collect(User::getFields());

    $roles = $fields->firstWhere('slug', 'roles');

    expect($roles['multiple'])->toBeFalse();
});

/**
 * Recursively collect all slugs from a nested field structure.
 */
function collectSlugs(array $items): array
{
    $slugs = [];
    foreach ($items as $item) {
        if (is_array($item)) {
            if (isset($item['slug'])) {
                $slugs[] = $item['slug'];
            }
            if (isset($item['fields'])) {
                $slugs = array_merge($slugs, collectSlugs($item['fields']));
            }
        }
    }

    return $slugs;
}
