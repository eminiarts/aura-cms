<?php

use Aura\Base\Resources\Permission;

test('permission names and groups display in sentence case', function (string $stored, string $shown) {
    expect(Permission::displayLabel($stored))->toBe($shown);
})->with([
    ['Create Media Library', 'Create media library'],
    ['View Any Media Library', 'View all media library'],
    ['Media-Folders', 'Media folders'],
    ['Force Delete Post', 'Permanently delete post'],
    ['Scope Posts', 'Limit posts to own records'],
    ['Scope API Tokens', 'Limit API tokens to own records'],
    ['View API Tokens', 'View API tokens'],
    ['', ''],
]);

test('the permissions list shows the sentence case name and keeps the stored one', function () {
    $permission = Permission::create(['name' => 'View Any Media Library', 'slug' => 'viewAny-media-x', 'group' => 'Media-Folders']);

    expect($permission->display('name'))->toBe('View all media library')
        ->and($permission->display('group'))->toContain('Media folders')
        ->and($permission->fresh()->name)->toBe('View Any Media Library');
});
