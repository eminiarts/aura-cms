<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuraLayoutCommand extends Command
{
    protected $description = 'Copy Aura layout file to the project for customization';

    protected $signature = 'aura:layout';

    public function handle(): int
    {
        $sourcePath = $this->sourcePath();
        $destinationPath = $this->destinationPath();

        if (! File::exists($sourcePath)) {
            $this->error("Aura layout file not found at [{$sourcePath}]. Make sure the Aura package installation is complete.");

            return 1;
        }

        File::ensureDirectoryExists(dirname($destinationPath));

        try {
            File::copy($sourcePath, $destinationPath);
            $this->info('Aura layout file copied successfully.');
            $this->info("You can now customize the layout at: {$destinationPath}");
        } catch (\Exception $e) {
            $this->error('Failed to copy Aura layout file: '.$e->getMessage());

            return 1;
        }

        return 0;
    }

    protected function destinationPath(): string
    {
        return resource_path('views/vendor/aura/components/layout/app.blade.php');
    }

    protected function sourcePath(): string
    {
        return dirname(__DIR__, 2).'/resources/views/components/layout/app.blade.php';
    }
}
