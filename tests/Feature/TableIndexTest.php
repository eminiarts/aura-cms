<?php

use Illuminate\Support\Facades\Schema;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

test('post_meta table index test', function () {
    $columns = Schema::getColumnListing('post_meta');
    expect($columns)->toContain('post_id');
    expect($columns)->toContain('key');
    expect($columns)->toContain('value');

    $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('post_meta');

    expect($indexes)->toHaveCount(4);

    $this->assertTrue($indexes['primary']->isPrimary());
    expect($indexes['primary']->getColumns())->toBe(['id']);

    expect($indexes['post_meta_post_id_index'])->not->toBeNull();
    expect($indexes['post_meta_key_index'])->not->toBeNull();
});

test('posts table index test', function () {
    $columns = Schema::getColumnListing('posts');

    $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('posts');

    // dump($indexes);

    expect($indexes)->toHaveCount(7);
});
