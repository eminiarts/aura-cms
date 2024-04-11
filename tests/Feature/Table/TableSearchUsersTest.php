<?php

use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Post;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    $this->post = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->post2 = User::factory()->create([
        'name' => 'Test User 2',
        'email' => 'user2@example.com',
    ]);
});

test('search user by email', function () {

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['model' => $this->post])
        ->assertSet('search', null);

    ray($component);
    // dd($component);

    $component
        ->assertSeeHtml('Test User')
        ->assertSee('Test User 2')
        ->assertSee('test@example.com')
        ->assertSee('user2@example.com')
        ->set('search', 'user2')
        ->assertSee('user2@example.com')
        ->assertDontSee('test@example.com');

    // $component->sorts should be []
    $this->assertEmpty($component->sorts);

    // expect($component->rowsQuery->toSql())->toBe('select * from "posts" where "posts"."type" = ? and "posts"."team_id" = ? order by "posts"."id" desc limit 10 offset 0');

});
