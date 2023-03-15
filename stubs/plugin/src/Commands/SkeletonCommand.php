<?php

namespace VendorName\Skeleton\Commands;

use Illuminate\Console\Command;

class SkeletonCommand extends Command
{
    public $description = 'My command';

    public $signature = 'skeleton';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
