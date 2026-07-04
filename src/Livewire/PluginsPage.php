<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class PluginsPage extends Component
{
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
            ];
        }

        return $installedPackages;
    }

    public function mount()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->installedPackages = $this->getInstalledPackages();
    }

    public function render()
    {
        return view('aura::livewire.plugins-page')->layout('aura::components.layout.app');
    }
}
