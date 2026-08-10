<?php

use Aura\Base\Tests\Resources\Core21OrderedList;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

test('list rows can be dragged through the declared ordering capability', function () {
    $first = Core21OrderedList::create([
        'title' => 'First ordered item',
        'content' => 'Starts first',
        'status' => 'publish',
        'created_at' => now()->subMinute(),
    ]);
    $second = Core21OrderedList::create([
        'title' => 'Second ordered item',
        'content' => 'Starts second',
        'status' => 'publish',
        'created_at' => now(),
    ]);

    $page = visit('/admin/core21-ordered-list');
    $initialTable = $page->script('document.querySelector("tbody")?.innerText');

    expect($initialTable)->not->toBeNull()
        ->and(strpos($initialTable, 'First ordered item'))->toBeLessThan(strpos($initialTable, 'Second ordered item'));

    $page->drag(
        'tr[data-id="'.$first->id.'"] button[aria-label="Reorder row"]',
        'tr[data-id="'.$second->id.'"]',
    )
        ->wait(1)
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    $reorderedTable = $page->script('document.querySelector("tbody")?.innerText');

    expect($reorderedTable)->not->toBeNull()
        ->and(strpos($reorderedTable, 'Second ordered item'))->toBeLessThan(strpos($reorderedTable, 'First ordered item'));

    expect(Core21OrderedList::query()->orderBy('created_at')->pluck('id')->all())
        ->toBe([$second->id, $first->id]);
});
