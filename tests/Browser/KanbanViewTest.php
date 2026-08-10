<?php

use Aura\Base\Tests\Resources\KanbanBoard;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

test('resource Kanban switches views and renders real cards without browser errors', function () {
    KanbanBoard::create([
        'title' => 'Acme opportunity',
        'content' => 'Real browser card',
        'status' => 'lead',
    ]);

    $page = visit('/admin/kanban-board');

    $page
        ->assertSee('Acme opportunity')
        ->click('Kanban View')
        ->wait(1)
        ->assertSee('Lead')
        ->assertSee('Won')
        ->assertSee('Lost')
        ->assertSee('Real browser card')
        ->click('List View')
        ->wait(1)
        ->assertSee('Acme opportunity')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});
