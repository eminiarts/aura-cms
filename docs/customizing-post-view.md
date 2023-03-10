# Customizing Post Views

## Customize Post - Index

## Customize Post - View

You can customize the Post view by either changing Fields or you can change the Post View alltogether.

### Custom Fields

To only view fields on a Post - View page you can set the following properties:

```php
<?php

namespace App\Aura\Resources;

use Eminiarts\Aura\Models\Post;

class CustomResource extends Post
{
    //...

    public static function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'User details',
                'slug' => 'tab-user',
                'global' => true,
                'on_view' => true,
                'on_forms' => false,
                'on_index' => false,
            ],
            [
                'name' => 'Custom View',
                'type' => 'Eminiarts\\Aura\\Fields\\View',
                'validation' => 'required',
                'view' => 'aura.CustomResource.view'
                'slug' => 'custom-view',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => '2FA',
                'type' => 'Eminiarts\\Aura\\Fields\\LivewireComponent',
                'component' => 'custom-resource-view',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'custom-livewire-view',
            ],
        ];
    }
}

```

Because of `'on_forms' => false,` the Tab is only shown on the View page. The Fields below are going to be grouped under the Global tab and inherit the properties.

### Custom View

Sometimes you may want to change the view alltogether and not use Aura Resources with Fields. 

In Laravel, the order of route registration matters. Routes that are registered first take precedence over routes that are registered later. Therefore, to ensure that your app routes are loaded first, you can register the app routes first before the Aura CMS routes.

In order to override the Routes, you have to change your `RouteServiceProvider` and register the Routes in the `register()` Method instead of the `boot()` Method.

```php
    public function register()
    {
        parent::register();

        Route::middleware('web')->group(base_path('routes/web.php'));
    }
```

Then in your `routes/web.php` you can register a custom Route like this:

```php
Route::get('/admin/User/{id}', function () {
    dd('custom user route view in app not working');
})->name('admin.user.view');
```

Adjust the URL according to your Aura CMS prefix in the config.


Complete RouteServiceProvider:

```php
<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        });
    }

    public function register()
    {
        parent::register();

        Route::middleware('web')->group(base_path('routes/web.php'));
    }
    

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
```
