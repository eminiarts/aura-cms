<?php

namespace Aura\Base\Tests;

use Aura\Base\AuraServiceProvider;
use Aura\Base\Providers\AuthServiceProvider;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Fixtures\FilterComponentServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Laravel\ServiceProvider as ImageServiceProvider;
use Lab404\Impersonate\ImpersonateServiceProvider;
use Laravel\Fortify\FortifyServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionObject;
use RuntimeException;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    use InteractsWithViews;
    use LazilyRefreshDatabase;

    // use WithWorkbench;

    /**
     * Browser tests serve the committed Vite builds to a real browser and
     * therefore need the Vite facade to stay real. Everything else runs
     * without Vite.
     */
    protected bool $enableVite = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->enableVite) {
            $this->withoutVite();
        }

        // Race-safe: parallel test processes can create this directory between the
        // is_dir() check and mkdir(), which would raise a "File exists" warning
        // (an ErrorException under Pest) on Linux CI. @ swallows that lost race.
        if (! is_dir(app_path('Aura/Resources'))) {
            @mkdir(app_path('Aura/Resources'), 0755, true);
        }

        // Mock file uploads are handled by the mock file in tests/Mocks/
        // Laravel's real-time facade system will automatically use the mock class

        // Add this before the Factory setup
        config()->set('app.env', 'testing');
        config()->set('filesystems.default', 'local');
        $this->app['cache']->store('aura-media-security')->clear();
        DB::connection('media-security-testing')->table('media_security_cache_locks')->delete();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Aura\\Base\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    public function getEnvironmentSetUp($app)
    {
        $this->useIsolatedFilesystemPaths($app);

        config()->set('database.default', 'testing');

        // Add these lines
        config()->set('app.env', 'testing');
        config()->set('livewire.temporary_file_upload.disk', 'local');

        // load the aura config
        // config()->set('aura', require __DIR__.'/../config/aura.php');

        // without exception handling
        // $this->withoutExceptionHandling();

        $migration = require __DIR__.'/../database/migrations/create_aura_tables.php.stub';
        $migration->up();

        $mediaSecuritySchema = Schema::connection('media-security-testing');

        if (! $mediaSecuritySchema->hasTable('media_security_cache')) {
            $mediaSecuritySchema->create('media_security_cache', function ($table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! $mediaSecuritySchema->hasTable('media_security_cache_locks')) {
            $mediaSecuritySchema->create('media_security_cache_locks', function ($table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        if (! $mediaSecuritySchema->hasTable('ordinary_default_cache')) {
            $mediaSecuritySchema->create('ordinary_default_cache', function ($table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! $mediaSecuritySchema->hasTable('ordinary_default_locks')) {
            $mediaSecuritySchema->create('ordinary_default_locks', function ($table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }
    }

    protected function defineEnvironment($app)
    {
        $this->useIsolatedFilesystemPaths($app);

        $mediaSecurityDirectory = $app->storagePath('framework/cache');

        if (! is_dir($mediaSecurityDirectory)) {
            mkdir($mediaSecurityDirectory, 0755, true);
        }

        $mediaSecurityDirectory = realpath($mediaSecurityDirectory);

        if ($mediaSecurityDirectory === false) {
            throw new RuntimeException('Unable to resolve the media security test cache directory.');
        }

        $mediaSecurityDatabase = $mediaSecurityDirectory.'/media-security.sqlite';

        if (! is_file($mediaSecurityDatabase)) {
            touch($mediaSecurityDatabase);
        }

        $app['config']->set('auth.providers.users.model', User::class);

        // Prevent actual file upload handling
        $app['config']->set('livewire.temporary_file_upload.disk', 'local');
        $app['config']->set('livewire.temporary_file_upload.middleware', null);
        $app['config']->set('cache.stores.aura-media-security', [
            'driver' => 'database',
            'connection' => 'media-security-testing',
            'table' => 'media_security_cache',
            'lock_connection' => 'media-security-testing',
            'lock_table' => 'media_security_cache_locks',
        ]);
        $app['config']->set('database.connections.media-security-testing', [
            'driver' => 'sqlite',
            'database' => $mediaSecurityDatabase,
            'prefix' => '',
        ]);
        $app['config']->set('cache.serializable_classes', false);
        $app['config']->set('aura.media.security.cache_store', 'aura-media-security');
    }

    // protected function tearDown(): void
    // {
    //     $refl = new ReflectionObject($this);
    //     foreach ($refl->getProperties() as $prop) {
    //         if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
    //             $prop->setAccessible(true);
    //             $prop->setValue($this, null);
    //         }
    //     }

    //     parent::tearDown();

    // }

    protected function getPackageProviders($app)
    {
        $this->useIsolatedFilesystemPaths($app);

        // Disable file uploads in Livewire before loading the provider
        $app['config']->set('livewire.temporary_file_upload.directory', null);
        $app['config']->set('livewire.temporary_file_upload.middleware', null);
        $app['config']->set('livewire.temporary_file_upload.upload_url', null);
        $app['config']->set('livewire.temporary_file_upload.rules', null);

        return [
            LivewireServiceProvider::class,
            FortifyServiceProvider::class,
            AuthServiceProvider::class,
            AuraServiceProvider::class,
            ImpersonateServiceProvider::class,
            RayServiceProvider::class,
            ImageServiceProvider::class,
            FilterComponentServiceProvider::class,
        ];
    }

    private function useIsolatedFilesystemPaths($app): void
    {
        $basePath = sys_get_temp_dir().'/aura-cms-testbench-'.getmypid();

        $paths = [
            'app/Aura/Resources',
            'bootstrap',
            'bootstrap/cache',
            'config',
            'database/migrations',
            'public',
            'storage',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/testing',
            'storage/framework/views',
            'storage/logs',
        ];

        foreach ($paths as $path) {
            if (! is_dir($basePath.'/'.$path)) {
                @mkdir($basePath.'/'.$path, 0755, true);
            }
        }

        // The app path must be isolated too: resource-generator tests create and
        // delete files under app_path('Aura/Resources'), which otherwise lives in
        // the shared testbench skeleton and races across parallel processes.
        $app->useAppPath($basePath.'/app');

        // Application::getNamespace() detects the namespace by matching app_path()
        // against the skeleton composer.json's PSR-4 entries; the relocated app
        // path can never match, so pin the namespace it would have detected.
        (function () {
            $this->namespace = 'App\\';
        })->call($app);

        $app->useBootstrapPath($basePath.'/bootstrap');
        $app->useConfigPath($basePath.'/config');
        $app->useDatabasePath($basePath.'/database');
        $app->usePublicPath($basePath.'/public');
        $app->useStoragePath($basePath.'/storage');
    }
}
