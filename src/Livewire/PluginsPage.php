<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class PluginsPage extends Component
{
    public const CORE_PACKAGE = 'eminiarts/aura-cms';

    public $installedPackages = [];

    public function getInstalledPackages()
    {
        $basePath = base_path();

        if (! file_exists($basePath.'/composer.json') || ! file_exists($basePath.'/composer.lock')) {
            $basePath = dirname(__DIR__, 2);
        }

        $composerJsonPath = $basePath.'/composer.json';
        $composerLockPath = $basePath.'/composer.lock';
        $composerJson = file_exists($composerJsonPath)
            ? json_decode(file_get_contents($composerJsonPath), true)
            : [];

        $composerLock = file_exists($composerLockPath)
            ? json_decode(file_get_contents($composerLockPath), true)
            : [];

        $composerLockPackages = array_merge(
            $composerLock['packages'] ?? [],
            $composerLock['packages-dev'] ?? []
        );
        $lockPackages = [];
        foreach ($composerLockPackages as $package) {
            $packageName = $package['name'];
            $packageVersion = $package['version'];

            $lockPackages[$packageName] = [
                'version' => $packageVersion,
            ];
        }

        $dependencies = array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? []
        );

        $installedPackages = [];
        foreach ($dependencies as $package => $version) {
            $packageJsonPath = $basePath.'/vendor/'.$package.'/composer.json';

            if (! file_exists($packageJsonPath)) {
                continue;
            }
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            $lockedVersion = $lockPackages[$package]['version'] ?? $version;

            if (str_starts_with($lockedVersion, 'v') || str_starts_with($lockedVersion, 'V')) {
                $version = substr($lockedVersion, 1);
            } else {
                $version = $lockedVersion;
            }

            $installedPackages[$package] = [
                'description' => $packageJson['description'] ?? '',
                'keywords' => $packageJson['keywords'] ?? [],
                'version' => $version ?? '',
                'type' => $packageJson['type'] ?? 'library',
                // Required only in require-dev: a development tool, not part of the running app.
                'dev' => array_key_exists($package, $composerJson['require-dev'] ?? [])
                    && ! array_key_exists($package, $composerJson['require'] ?? []),
            ];
        }

        return $installedPackages;
    }

    /**
     * Aura's own packages and plugins (eminiarts/aura-*, aura/*, or an aura-* package type), so the
     * page can list them apart from the other Composer packages.
     */
    public static function isAuraPackage(string $name, ?string $type = null): bool
    {
        return str_starts_with($name, 'eminiarts/aura')
            || str_starts_with($name, 'aura/')
            || str_starts_with((string) $type, 'aura');
    }

    public function mount()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->installedPackages = $this->getInstalledPackages();
    }

    public function render()
    {
        // Every installed package lands in exactly one place. The core package is the CMS itself, not a
        // plugin: it shows as the page meta ("Aura CMS 1.x-dev") instead of a row.
        $packages = collect($this->installedPackages);
        $core = $packages->pull(static::CORE_PACKAGE);

        [$aura, $other] = $packages
            ->partition(fn ($package, $name) => static::isAuraPackage($name, $package['type'] ?? null));

        return view('aura::livewire.plugins-page', [
            'coreVersion' => $core['version'] ?? null,
            // The page title counts Aura plugins; other Composer packages count in their own section.
            'pluginCount' => $aura->count(),
            'plugins' => $aura->all(),
            'otherPackages' => $other->all(),
        ])->layout('aura::components.layout.app');
    }
}
