<?php

use Aura\Base\Facades\Aura;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

test('without navigation config the sidebar keeps the declared groups', function () {
    $nav = Aura::navigation();

    expect(collect($nav->flatten(1))->firstWhere('slug', 'option')['group'])->toBe('Aura');
});

test('navigation config moves, renames, re-icons and hides items', function () {
    config(['aura.navigation.items' => [
        'user' => ['group' => 'Admin', 'sort' => 5, 'dropdown' => false, 'name' => 'People', 'icon' => 'key'],
        'option' => ['hidden' => true],
    ]]);

    $nav = Aura::navigation();
    $items = collect($nav->flatten(1));
    $user = collect($nav['Admin'])->firstWhere('slug', 'user');

    expect($user)->not->toBeNull()
        ->and($user['name'])->toBe('People')
        ->and($user['sort'])->toBe(5)
        ->and($user['dropdown'])->toBeFalse()
        ->and($user['icon'])->toContain('<svg')
        ->and($items->pluck('slug'))->not->toContain('option');

    $this->get(route('aura.dashboard'))->assertSee('People');
});

test('navigation config orders groups and keeps unlisted groups after them', function () {
    config(['aura.navigation.items' => [
        'option' => ['group' => 'Zeta'],
        'user' => ['group' => 'Alpha', 'dropdown' => false],
    ]]);

    expect(Aura::navigation()->keys()->all())->toBe(['Alpha', 'Aura', 'Zeta']);

    config(['aura.navigation.groups' => ['Zeta', 'Alpha']]);

    expect(Aura::navigation()->keys()->all())->toBe(['Zeta', 'Alpha', 'Aura']);
});
