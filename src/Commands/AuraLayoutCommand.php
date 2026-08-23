<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuraLayoutCommand extends Command
{
    protected $description = 'Copy Aura layout file to the project for customization';

    protected $signature = 'aura:layout';

    public function handle()
    {
        // Resolved from the package itself so the command works no matter where
        // composer installed it, or which directory artisan is run from.
        $sourcePath = dirname(__DIR__, 2).'/resources/views/components/layout/app.blade.php';
        $destinationPath = resource_path('views/vendor/aura/components/layout/app.blade.php');

        if (! File::exists($sourcePath)) {
            $this->error('Aura layout file not found. Make sure the Aura package is installed.');

            return 1;
        }

        File::ensureDirectoryExists(dirname($destinationPath));

        try {
            File::copy($sourcePath, $destinationPath);
            $this->info('Aura layout file copied successfully.');
            $this->info("You can now customize the layout at: $destinationPath");
        } catch (\Exception $e) {
            $this->error('Failed to copy Aura layout file: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
