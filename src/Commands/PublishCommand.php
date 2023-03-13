<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all of the Aura resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('vendor:publish', [
            '--tag' => 'aura-assets',
            '--force' => true,
        ]);
    }
}
