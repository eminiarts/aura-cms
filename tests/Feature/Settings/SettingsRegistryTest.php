<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Settings;
use Aura\Base\Resources\Option;
use Aura\Base\Settings\SettingsPage;
use Aura\Base\Settings\SettingsRegistry;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('plugins register ordered settings pages through Aura', function () {
    Aura::registerSettingsPages('acme/seo', [
        new SettingsPage(
            slug: 'seo',
            title: 'SEO',
            fields: [[
                'name' => 'Site Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'seo-site-name',
            ]],
            icon: 'search',
            order: 25,
        ),
    ]);

    $registry = app(SettingsRegistry::class);

    expect($registry->pages())
        ->sequence(
            fn ($page) => $page->slug->toBe('general'),
            fn ($page) => $page->slug->toBe('seo'),
            fn ($page) => $page->slug->toBe('ai'),
        );

    Livewire::test(Settings::class)
        ->assertSee('SEO')
        ->assertSee('Site Name')
        ->set('form.fields.seo-site-name', 'Aura Site')
        ->call('save');

    expect(Option::first()->value['seo-site-name'])->toBe('Aura Site');
});

test('the registry rejects page and field collisions', function () {
    $registry = new SettingsRegistry;
    $page = new SettingsPage('one', 'One', [[
        'name' => 'Shared',
        'type' => 'Aura\\Base\\Fields\\Text',
        'slug' => 'shared-setting',
    ]]);
    $registry->register('acme/one', [$page]);
    $registry->register('acme/one', [$page]);

    expect($registry->pages())->toHaveCount(1);

    expect(fn () => $registry->register('acme/two', [
        new SettingsPage('two', 'Two', [[
            'name' => 'Shared again',
            'type' => 'Aura\\Base\\Fields\\Text',
            'slug' => 'shared-setting',
        ]]),
    ]))->toThrow(InvalidArgumentException::class, 'already registered by page [one]')
        ->and(fn () => $registry->register('acme/two', [
            new SettingsPage('one', 'Duplicate page', [[
                'name' => 'Different field',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'different-setting',
            ]]),
        ]))->toThrow(InvalidArgumentException::class, 'already registered');
});

test('secret settings are encrypted write only and preserved by blank submissions', function () {
    $component = Livewire::test(Settings::class)
        ->assertSet('form.fields.ai-api-key', '')
        ->set('form.fields.ai-api-key', 'super-secret-key')
        ->call('save')
        ->assertSet('form.fields.ai-api-key', '')
        ->assertSet('secretConfigured.ai-api-key', true);

    $option = Option::first();
    $encrypted = $option->value['ai-api-key'];

    expect($encrypted)->toStartWith('encrypted:')
        ->not->toContain('super-secret-key')
        ->and(Crypt::decryptString(str($encrypted)->after('encrypted:')->toString()))->toBe('super-secret-key')
        ->and(Aura::setting('ai-api-key'))->toBe('super-secret-key');

    $component
        ->set('form.fields.color-palette', 'blue')
        ->call('save');

    $option->refresh();

    expect($option->value['ai-api-key'])->toBe($encrypted)
        ->and($option->value['color-palette'])->toBe('blue');

    Livewire::test(Settings::class)
        ->assertSet('form.fields.ai-api-key', '')
        ->assertSet('secretConfigured.ai-api-key', true);
});
