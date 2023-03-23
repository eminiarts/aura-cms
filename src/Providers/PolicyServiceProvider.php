<?php

namespace Eminiarts\Aura\Providers;

use Eminiarts\Aura\Policies\ResourcePolicy;
use Eminiarts\Aura\Policies\TeamPolicy;
use Eminiarts\Aura\Policies\UserPolicy;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class PolicyServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Team::class => TeamPolicy::class,
        Resource::class => ResourcePolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        //$this->registerPolicies();

        // Gate::before(function ($user, $ability) {
        //     dd('before');

        //     return true;
        //     if ($user->resource->isSuperAdmin()) {
        //         return true;
        //     }
        // });
    }

    public function register(): void
    {
    }
}
