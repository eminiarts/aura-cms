<?php

use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Taxonomies\Taxonomy;

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);

    $this->taxonomyData = [
        'name' => 'Test Category',
        'slug' => 'test-category',
        'taxonomy' => 'category',
        'description' => 'A test category',
        'parent' => 0,
        'count' => 0,
    ];
});

test('it can create a taxonomy instance', function () {
    $taxonomy = Taxonomy::create($this->taxonomyData);

    $this->assertDatabaseHas('taxonomies', [
        'name' => $this->taxonomyData['name'],
        'slug' => $this->taxonomyData['slug'],
    ]);
});

test('it can update a taxonomy instance', function () {
    $taxonomy = Taxonomy::create($this->taxonomyData);

    $updatedName = 'Updated Test Category';
    $updatedSlug = 'updated-test-category';

    $taxonomy->update([
        'name' => $updatedName,
        'slug' => $updatedSlug,
    ]);

    $this->assertDatabaseHas('taxonomies', [
        'name' => $updatedName,
        'slug' => $updatedSlug,
    ]);
});

test('it can delete a taxonomy instance', function () {
    $taxonomy = Taxonomy::create($this->taxonomyData);

    $taxonomy->delete();

    $this->assertDatabaseMissing('taxonomies', [
        'name' => $this->taxonomyData['name'],
        'slug' => $this->taxonomyData['slug'],
    ]);
});

test('it returns correct singular and plural names', function () {
    $taxonomy = new Taxonomy();

    $taxonomy::$singularName = 'Category';
    $taxonomy::$pluralName = 'Categories';

    $this->assertEquals('Category', $taxonomy->singularName());
    $this->assertEquals('Categories', $taxonomy->pluralName());
});

test('it returns the correct title attribute', function () {
    $taxonomy = new Taxonomy();

    $this->assertEquals('Taxonomy', $taxonomy->title);
});

test('it sets default values when saving a taxonomy instance', function () {
    $taxonomyData = [
        'name' => 'Test Category',
    ];

    $taxonomy = Taxonomy::create($taxonomyData);

    $this->assertNotNull($taxonomy->slug);
    $this->assertEquals('', $taxonomy->description);
    $this->assertEquals(0, $taxonomy->parent);
    $this->assertEquals(0, $taxonomy->count);
    $this->assertNotNull($taxonomy->team_id);
});

test('it returns the correct type', function () {
    $taxonomy = new Taxonomy();
    $taxonomy::$type = 'test-type';

    $this->assertEquals('test-type', $taxonomy->getType());
});