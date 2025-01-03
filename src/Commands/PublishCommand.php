<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all of the Aura resources';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:publish';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $assetPath = public_path('vendor/aura/assets');

        if (File::exists($assetPath)) {
            File::deleteDirectory($assetPath);
        }

        $this->call('vendor:publish', [
            '--tag' => 'aura-assets',
            '--force' => true,
        ]);
    }
}
