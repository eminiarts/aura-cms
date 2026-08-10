<?php

use Aura\Base\Tests\Resources\KanbanBoard;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

test('resource Kanban switches views, renders real cards, and moves through Livewire sorting', function () {
    $card = KanbanBoard::create([
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
        ->drag(
            '[data-kanban-card="'.$card->getKey().'"]',
            '[data-kanban-column="won"] [wire\\:sort]',
        )
        ->wait(2);

    expect($card->fresh()->status)->toBe('won');

    $page
        ->click('List View')
        ->wait(1)
        ->assertSee('Acme opportunity')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});
