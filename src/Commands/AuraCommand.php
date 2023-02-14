<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\Command;

class AuraCommand extends Command
{
    public $description = 'My command';

    public $signature = 'aura';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
