<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

test('post_meta table index test', function () {
    // Check columns
    $columns = Schema::getColumnListing('post_meta');
    expect($columns)->toContain('post_id');
    expect($columns)->toContain('key');
    expect($columns)->toContain('value');

    // Check indexes
    $indexes = DB::select("PRAGMA index_list('post_meta')");
    $indexNames = array_column($indexes, 'name');

    expect($indexNames)->toHaveCount(3);
    expect($indexNames)->toContain('post_meta_post_id_index');
    expect($indexNames)->toContain('post_meta_key_index');

    // Additional checks can be added based on specific index requirements
});

test('posts table index test', function () {
    // Check columns
    $columns = Schema::getColumnListing('posts');

    // Check indexes
    $indexes = DB::select("PRAGMA index_list('posts')");
    $indexNames = array_column($indexes, 'name');

    expect($indexNames)->toHaveCount(4);
    expect($indexNames)->toContain('posts_parent_id_index');
    expect($indexNames)->toContain('posts_user_id_index');
    expect($indexNames)->toContain('posts_slug_index');
    expect($indexNames)->toContain('posts_team_id_type_index');
    // Additional checks can be added based on specific index requirements
});
