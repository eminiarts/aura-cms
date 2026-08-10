<?php

use Aura\Base\AuraServiceProvider;
use Aura\Base\Providers\AuraEloquentUserProvider;
use Aura\Base\Resources\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UnrelatedHostAuthUser extends Authenticatable {}

class AlternativeAuraAuthUser extends User {}

// beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

// Test Post Create Pages
test('check auth settings', function () {
    expect(config('aura.auth.registration'))->toBeTrue();
    expect(config('aura.auth.redirect'))->toBe('/admin');
    expect(config('aura.auth.2fa'))->toBeTrue();
    expect(config('aura.auth.user_invitations'))->toBeTrue();
    expect(config('aura.auth.invitation_expiry'))->toBe(7);
    expect(config('aura.auth.create_teams'))->toBeTrue();
});

test('Aura auth provider replacement leaves unrelated host providers unchanged', function () {
    config()->set('fortify.guard', 'aura');
    config()->set('auth.guards.aura', [
        'driver' => 'session',
        'provider' => 'aura_users',
    ]);
    config()->set('auth.guards.alternative_aura', [
        'driver' => 'session',
        'provider' => 'alternative_aura_users',
    ]);
    config()->set('auth.guards.external', [
        'driver' => 'session',
        'provider' => 'external_users',
    ]);
    config()->set('auth.providers.aura_users', [
        'driver' => 'eloquent',
        'model' => User::class,
    ]);
    config()->set('auth.providers.alternative_aura_users', [
        'driver' => 'eloquent',
        'model' => AlternativeAuraAuthUser::class,
    ]);
    config()->set('auth.providers.external_users', [
        'driver' => 'eloquent',
        'model' => UnrelatedHostAuthUser::class,
    ]);
    config()->set('auth.providers.database_users', [
        'driver' => 'database',
        'table' => 'users',
    ]);

    (new AuraServiceProvider(app()))->packageRegistered();

    expect(config('auth.providers.aura_users.driver'))->toBe('aura-eloquent')
        ->and(config('auth.providers.alternative_aura_users.driver'))->toBe('aura-eloquent')
        ->and(config('auth.providers.external_users.driver'))->toBe('eloquent')
        ->and(config('auth.providers.database_users.driver'))->toBe('database')
        ->and(app('auth')->createUserProvider('aura_users'))->toBeInstanceOf(AuraEloquentUserProvider::class)
        ->and(app('auth')->createUserProvider('alternative_aura_users'))->toBeInstanceOf(AuraEloquentUserProvider::class)
        ->and(app('auth')->createUserProvider('external_users'))->toBeInstanceOf(EloquentUserProvider::class)
        ->and(app('auth')->guard('aura')->getProvider())->toBeInstanceOf(AuraEloquentUserProvider::class)
        ->and(app('auth')->guard('alternative_aura')->getProvider())->toBeInstanceOf(AuraEloquentUserProvider::class)
        ->and(app('auth')->guard('external')->getProvider())->toBeInstanceOf(EloquentUserProvider::class);
});
