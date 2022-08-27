<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\Command;

class AuraCommand extends Command
{
    public $signature = 'aura';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
