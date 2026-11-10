<?php

use Aura\Base\Livewire\Resource\View;
use Aura\Base\RecordLayout\RecordViewSections;
use Aura\Base\Resource;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

class FormFramePlainResource extends Resource
{
    public static string $type = 'FormFramePlain';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text', 'style' => ['width' => '50']],
            ['name' => 'Notes', 'slug' => 'notes', 'type' => 'Aura\\Base\\Fields\\Textarea'],
        ];
    }
}

class FormFrameListResource extends Resource
{
    public static string $type = 'FormFrameList';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Users', 'slug' => 'users', 'type' => 'Aura\\Base\\Fields\\HasMany', 'resource' => 'Aura\\Base\\Resources\\User'],
        ];
    }
}

class FormFrameRailResource extends Resource
{
    public static string $type = 'FormFrameRail';

    public static function getFields(): array
    {
        return [
            ['name' => 'Main', 'slug' => 'main', 'type' => 'Aura\\Base\\Fields\\Panel', 'style' => ['width' => '70']],
            ['name' => 'Notes', 'slug' => 'notes', 'type' => 'Aura\\Base\\Fields\\Textarea'],
            ['name' => 'Properties', 'slug' => 'properties', 'type' => 'Aura\\Base\\Fields\\Panel', 'style' => ['width' => '30']],
            ['name' => 'Name', 'slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

test('a record with nothing beside its values takes its form frame', function () {
    $plain = new FormFramePlainResource;
    $frame = RecordViewSections::formFrame($plain->viewFields(), $plain);

    // Plain values: one card at the form's readable width, as on the edit page.
    expect($frame)->not->toBeNull()
        ->and($frame['single'])->toBeTrue()
        ->and($frame['card'])->toBeTrue()
        ->and(array_column($frame['fields'], 'slug'))->toBe(['name', 'notes']);

    // Tabs keep their lists inside the tabs, so a tabbed record mirrors its form too, at full width.
    $frame = RecordViewSections::formFrame($this->user->viewFields(), $this->user);

    expect($frame)->not->toBeNull()
        ->and($frame['single'])->toBeFalse()
        ->and($frame['card'])->toBeFalse();
});

test('a record with related lists or a rail keeps the record layout', function () {
    $list = new FormFrameListResource;
    $rail = new FormFrameRailResource;

    expect(RecordViewSections::formFrame($list->viewFields(), $list))->toBeNull()
        ->and(RecordViewSections::formFrame($rail->viewFields(), $rail))->toBeNull();
});

test('the view page renders a tabbed record in its form frame', function () {
    Livewire::test(View::class, ['id' => $this->user->id, 'slug' => 'user'])
        ->assertSeeHtml('data-record-frame="form"')
        ->assertSeeHtml('aura-card')
        ->assertDontSeeHtml('data-record-section=');
});
