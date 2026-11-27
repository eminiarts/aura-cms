<?php

use Aura\Base\RecordLayout\RecordViewSections;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Gate;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

class VisibilityHeaderResource extends Resource
{
    public static string $type = 'VisibilityHeader';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Amount', 'slug' => 'amount', 'type' => 'Aura\\Base\\Fields\\Number', 'conditional_logic' => fn () => false],
            ['name' => 'Stage', 'slug' => 'stage', 'type' => 'Aura\\Base\\Fields\\Status', 'options' => [['key' => 'open', 'value' => 'Open', 'color' => 'blue']]],
        ];
    }
}

class VisibilityCardTarget extends Resource
{
    public static string $type = 'VisibilityCardTarget';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Phone', 'slug' => 'phone', 'type' => 'Aura\\Base\\Fields\\Phone'],
        ];
    }
}

class VisibilityCardOwner extends Resource
{
    public static string $type = 'VisibilityCardOwner';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Company', 'slug' => 'company', 'type' => 'Aura\\Base\\Fields\\BelongsTo', 'resource' => VisibilityCardTarget::class],
        ];
    }
}

test('header facts leave out a field its conditional logic hides', function () {
    $model = new VisibilityHeaderResource;

    expect(array_column(RecordViewSections::header($model->viewFields(), $model), 'slug'))->toBe(['stage']);
});

test('a related-record card needs the view right on the related record', function () {
    $target = VisibilityCardTarget::create(['name' => 'Atlas', 'phone' => '+41 44 000 00 00']);
    $owner = new VisibilityCardOwner;
    $owner->company = $target->id;

    expect(RecordViewSections::cards($owner->viewFields(), $owner))->toHaveCount(1);

    Gate::before(fn ($user, $ability, $arguments) => $ability === 'view' && ($arguments[0] ?? null) instanceof VisibilityCardTarget ? false : null);

    expect(RecordViewSections::cards($owner->viewFields(), $owner))->toBe([]);
});
