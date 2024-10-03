<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuraLayoutCommand extends Command
{
    protected $signature = 'aura:layout';

    protected $description = 'Copy Aura layout file to the project for customization';

    public function handle()
    {
        $sourcePath = 'vendor/eminiarts/aura/resources/views/components/layout/app.blade.php';
        $destinationPath = 'resources/views/vendor/aura/components/layout/app.blade.php';

        if (!File::exists($sourcePath)) {
            $this->error('Aura layout file not found. Make sure the Aura package is installed.');
            return 1;
        }

        File::ensureDirectoryExists(dirname($destinationPath));

        try {
            File::copy($sourcePath, $destinationPath);
            $this->info('Aura layout file copied successfully.');
            $this->info("You can now customize the layout at: $destinationPath");
        } catch (\Exception $e) {
            $this->error('Failed to copy Aura layout file: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}