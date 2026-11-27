<?php

use Aura\Base\Resources\Option;

test('an option key reads as a label with its scope', function (string $key, string $label, ?string $scope) {
    $option = new Option(['name' => $key]);

    expect($option->displayLabel())->toBe($label)
        ->and($option->displayScope())->toBe($scope)
        ->and($option->name)->toBe($key);
})->with([
    ['user.1.sidebarToggled', 'Sidebar toggled', 'User'],
    ['user.1.table_view.Attachment', 'Table view · Attachment', 'User'],
    ['user.12.bookmarks', 'Bookmarks', 'User'],
    ['team.1.settings', 'Team settings', 'Team'],
    ['team.3.columns.Contact', 'Columns · Contact', 'Team'],
    ['settings', 'Settings', null],
    ['aura.version', 'Aura · version', null],
]);
