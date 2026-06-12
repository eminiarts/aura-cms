<?php

use Aura\Base\Livewire\Settings;
use Aura\Base\Resources\Option;
use Livewire\Livewire;

beforeEach(function () {
    config(['aura.teams' => false]);

    $this->artisan('migrate:fresh');

    $migration = require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
    $migration->up();

    $this->actingAs($this->user = createSuperAdminWithoutTeam());
});

afterEach(function () {
    config(['aura.teams' => true]);
});

describe('settings component without teams', function () {
    it('renders the settings page', function () {
        $this->withoutExceptionHandling();

        $this->get(route('aura.settings'))
            ->assertOk();
    });

    it('renders the settings livewire component', function () {
        Livewire::test(Settings::class)
            ->assertSee('Settings')
            ->assertStatus(200);
    });
});

describe('default settings without teams', function () {
    it('creates settings option record on first access', function () {
        Livewire::test(Settings::class);

        $this->assertDatabaseCount('options', 1);

        $option = Option::first();
        expect($option->name)->toBe('settings')
            ->and($option->value)->toBeArray();
    });

    it('has correct default theme values', function () {
        Livewire::test(Settings::class)
            ->assertSet('form.fields.darkmode-type', 'auto')
            ->assertSet('form.fields.sidebar-type', 'primary')
            ->assertSet('form.fields.color-palette', 'aura')
            ->assertSet('form.fields.gray-color-palette', 'slate');
    });

    it('displays primary sidebar option on page', function () {
        Livewire::test(Settings::class)
            ->assertSee('primary');
    });

    it('stores settings without team_id when teams disabled', function () {
        Livewire::test(Settings::class);

        $option = Option::first();

        expect($option->name)->toBe('settings')
            ->and($option->team_id)->toBeNull();
    });
});

describe('saving settings without teams', function () {
    it('saves darkmode settings', function () {
        Livewire::test(Settings::class)
            ->set('form.fields.darkmode-type', 'light')
            ->call('save');

        $option = Option::first();
        expect($option->value['darkmode-type'])->toBe('light');
    });

    it('saves sidebar settings', function () {
        Livewire::test(Settings::class)
            ->set('form.fields.sidebar-type', 'dark')
            ->call('save');

        $option = Option::first();
        expect($option->value['sidebar-type'])->toBe('dark');
    });

    it('saves color palette settings', function () {
        Livewire::test(Settings::class)
            ->set('form.fields.color-palette', 'red')
            ->set('form.fields.gray-color-palette', 'zinc')
            ->call('save');

        $option = Option::first();
        expect($option->value['color-palette'])->toBe('red')
            ->and($option->value['gray-color-palette'])->toBe('zinc');
    });

    it('saves all settings together', function () {
        Livewire::test(Settings::class)
            ->set('form.fields.darkmode-type', 'dark')
            ->set('form.fields.sidebar-type', 'light')
            ->set('form.fields.color-palette', 'blue')
            ->set('form.fields.gray-color-palette', 'neutral')
            ->call('save');

        $this->assertDatabaseCount('options', 1);

        $option = Option::first();
        expect($option->name)->toBe('settings')
            ->and($option->value)->toBeArray()
            ->and($option->value['darkmode-type'])->toBe('dark')
            ->and($option->value['sidebar-type'])->toBe('light')
            ->and($option->value['color-palette'])->toBe('blue')
            ->and($option->value['gray-color-palette'])->toBe('neutral');
    });

    it('updates existing settings instead of creating new', function () {
        // First save
        Livewire::test(Settings::class)
            ->set('form.fields.color-palette', 'red')
            ->call('save');

        $this->assertDatabaseCount('options', 1);

        // Second save
        Livewire::test(Settings::class)
            ->set('form.fields.color-palette', 'blue')
            ->call('save');

        $this->assertDatabaseCount('options', 1);

        $option = Option::first();
        expect($option->value['color-palette'])->toBe('blue');
    });
});

describe('settings authorization without teams', function () {
    it('requires super admin to access settings', function () {
        expect(auth()->user()->isSuperAdmin())->toBeTrue();
    });

    it('confirms teams are disabled', function () {
        expect(config('aura.teams'))->toBeFalse();
    });
});
