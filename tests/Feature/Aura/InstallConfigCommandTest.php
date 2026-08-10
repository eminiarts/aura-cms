<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->originalBasePath = $this->app->basePath();
    $this->originalConfigPath = $this->app->configPath();
    $this->originalEnvironmentPath = $this->app->environmentPath();
    $this->installationBasePath = sys_get_temp_dir().'/aura-install-config-'.bin2hex(random_bytes(8));

    File::makeDirectory($this->installationBasePath.'/config', 0755, true);
    File::makeDirectory($this->installationBasePath.'/bootstrap/cache', 0755, true);
    File::put($this->installationBasePath.'/.env', "APP_ENV=testing\n");

    $this->app->setBasePath($this->installationBasePath);
    $this->app->useConfigPath($this->installationBasePath.'/config');
    $this->app->useEnvironmentPath($this->installationBasePath);
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
    $this->app->useConfigPath($this->originalConfigPath);
    $this->app->useEnvironmentPath($this->originalEnvironmentPath);
    File::deleteDirectory($this->installationBasePath);
});

function core24WriteInstallationConfig(string $basePath): array
{
    $configuration = [
        'teams' => false,
        'features' => [],
        'theme' => [
            'font' => [
                'family' => ['ui-sans-serif', 'system-ui', 'sans-serif'],
                'stylesheet' => false,
            ],
            'colors' => [
                'light' => [
                    'primary' => 'var(--primary-600)',
                    'success' => '22 163 74',
                    'danger' => '220 38 38',
                ],
                'dark' => [
                    'primary' => 'var(--primary-600)',
                    'success' => '74 222 128',
                    'danger' => '248 113 113',
                ],
            ],
        ],
    ];

    File::put($basePath.'/config/aura.php', '<?php return '.var_export($configuration, true).';');

    return $configuration;
}

describe('config installation command', function (): void {
    it('command is registered', function (): void {
        expect(Artisan::all())->toHaveKey('aura:install-config');
    });

    it('preserves nested font and color configuration when theme modification is accepted', function (): void {
        $configuration = core24WriteInstallationConfig($this->installationBasePath);

        $this->artisan('aura:install-config')
            ->expectsConfirmation('Do you want to use teams?', 'no')
            ->expectsConfirmation('Do you want to modify the default features?', 'no')
            ->expectsConfirmation('Do you want to allow registration?', 'no')
            ->expectsConfirmation('Do you want to modify the default theme?', 'yes')
            ->assertSuccessful();

        $written = include $this->installationBasePath.'/config/aura.php';

        expect($written['theme']['font'])->toBe($configuration['theme']['font'])
            ->and($written['theme']['colors'])->toBe($configuration['theme']['colors']);
    });

    it('preserves nested theme configuration when theme modification is declined', function (): void {
        $configuration = core24WriteInstallationConfig($this->installationBasePath);

        $this->artisan('aura:install-config')
            ->expectsConfirmation('Do you want to use teams?', 'no')
            ->expectsConfirmation('Do you want to modify the default features?', 'no')
            ->expectsConfirmation('Do you want to allow registration?', 'no')
            ->expectsConfirmation('Do you want to modify the default theme?', 'no')
            ->assertSuccessful();

        expect((include $this->installationBasePath.'/config/aura.php')['theme'])
            ->toBe($configuration['theme']);
    });

    it('does not prompt or corrupt nested theme configuration in non-interactive mode', function (): void {
        $configuration = core24WriteInstallationConfig($this->installationBasePath);

        expect(Artisan::call('aura:install-config', ['--no-interaction' => true]))->toBe(0);

        expect((include $this->installationBasePath.'/config/aura.php')['theme'])
            ->toBe($configuration['theme']);
    });
});
