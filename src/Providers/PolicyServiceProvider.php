<?php

namespace Eminiarts\Aura\Providers;

use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Models\Post;
use Eminiarts\Policies\PostPolicy;
use Eminiarts\Policies\TeamPolicy;
use Eminiarts\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class PolicyServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Team::class => TeamPolicy::class,
        Resource::class => PostPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function register(): void
    {
        dd('register');
    }

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        dd('boot');

        $this->registerPolicies();

        //
    }
}
