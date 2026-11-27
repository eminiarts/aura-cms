<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Role;

require_once __DIR__.'/Support/helpers.php';

test('a registered dotted permission survives the Alpine and Livewire role form round trip', function () {
    browserSuperAdmin();

    Aura::registerPermissions([
        'box.evals.view' => ['name' => 'View evals', 'group' => 'Evals'],
    ]);

    $role = Role::factory()->create([
        'name' => 'Eval Reviewer',
        'slug' => 'eval_reviewer',
        'super_admin' => false,
        'permissions' => ['box.evals.view' => false],
    ]);

    $page = visit('/admin/role/'.$role->id.'/edit');

    $page->assertSee('Features')
        ->assertSee('View evals')
        ->check('[id="permissions_box.evals.view"]')
        ->press('Save')
        ->wait(3);

    $role->refresh();

    expect($role)->not->toBeNull()
        ->and($role->permissions['box.evals.view'] ?? null)->toBeTrue()
        ->and($role->permissions)->not->toHaveKey('box')
        ->and(json_decode($role->getRawOriginal('permissions'), true)['box.evals.view'])->toBeTrue();
});
