<?php

namespace Eminiarts\Aura\Http\Livewire;

use Exception;
use Livewire\Component;
use Spatie\Packagist\PackagistClient;
use Spatie\Packagist\PackagistUrlGenerator;
use Symfony\Component\Process\Process;

class PluginsPage extends Component
{
    public $installedPackages = [];

    public $latestVersions = [];

    public $loading = [];

    public $output;

    // getInstalledPackages
    public function getInstalledPackages()
    {
        $composerJsonPath = base_path('composer.json');
        $composerJson = json_decode(file_get_contents($composerJsonPath), true);

        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

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

            // Use $packageName and $packageVersion as needed.
        }

        $dependencies = array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? []
        );

        $installedPackages = [];
        foreach ($dependencies as $package => $version) {
            $packageJsonPath = base_path().'/vendor/'.$package.'/composer.json';

            if (! file_exists($packageJsonPath)) {
                continue;
            }
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);

            if (str_starts_with($lockPackages[$package]['version'], 'v') || str_starts_with($lockPackages[$package]['version'], 'V')) {
                $version = substr($lockPackages[$package]['version'], 1);
            } else {
                $version = $lockPackages[$package]['version'];
            }

            $installedPackages[$package] = [
                'keywords' => $packageJson['keywords'] ?? [],
                'version' => $version ?? '',
            ];
        }

        return $installedPackages;
    }

    public function getPackageUpdates($name)
    {
        $this->loading[$name] = true;
        // refresh livewire component
        $this->dispatchBrowserEvent('refresh');
        $client = new \GuzzleHttp\Client();
        $generator = new PackagistUrlGenerator();

        $packagist = new PackagistClient($client, $generator);

        $packageVersions = $packagist->getPackageMetadata($name)['packages'][$name];

        // get all keys from $packageVersions
        $versions = array_keys($packageVersions);
        // loop through all versions and if it starts with "dev-" remove it and if it starts with "v" remove the v
        foreach ($versions as $key => $version) {
            if (str_contains($version, 'dev') || str_contains($version, 'alpha') || str_contains($version, 'beta') || str_contains($version, 'BETA') || str_contains($version, 'rc') || str_contains($version, 'RC') || str_contains($version, 'x')) {
                unset($versions[$key]);
            } elseif (str_starts_with($version, 'v')) {
                $versions[$key] = substr($version, 1);
            } elseif (str_starts_with($version, 'V')) {
                $versions[$key] = substr($version, 1);
            }
        }

        // sort the versions and get the highest version 2.20 is higher than 2.9
        usort($versions, function ($a, $b) {
            return version_compare($a, $b);
        });

        $latestVersion = end($versions);

        $this->latestVersions[$name] = $latestVersion;
        $this->loading[$name] = false;
    }

    // mount
    public function mount()
    {
        $this->installedPackages = $this->getInstalledPackages();
    }

    public function render()
    {
        return view('aura::livewire.plugins-page');
    }

    public function runComposerUpdate()
    {
        // exec('composer update 2>&1', $output);
        exec('cd .. && /opt/homebrew/bin/php /usr/local/bin/composer update 2>&1', $output);

        // The output of the command will be stored in the $output array
        $this->output = implode("\n", $output);
    }

    public function updatePackage($name, $version)
    {
        $cmd = 'composer require '.$name.':'.$version.' --update-with-dependencies';

        $process = Process::fromShellCommandline($cmd);

        $processOutput = '';

        $captureOutput = function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        };

        $process->setTimeout(null)
            ->run($captureOutput);

        if ($process->getExitCode()) {
            $this->notify('Update failed.'.$cmd.' - '.$processOutput);

            $exception = new Exception($cmd.' - '.$processOutput);
            report($exception);

            throw $exception;
        }

        return $processOutput;
    }
}
