<?php

namespace Aura\Base\Commands;

use Aura\Base\Support\PublishedAssets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

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
     */
    public function handle(): int
    {
        $packageRoot = dirname(__DIR__, 2);
        $target = public_path('vendor/aura');
        $token = bin2hex(random_bytes(4));
        $staging = public_path('vendor/aura-staging-'.$token);
        $backup = public_path('vendor/aura-backup-'.$token);

        try {
            File::ensureDirectoryExists(dirname($staging));

            if (File::exists($staging)) {
                File::deleteDirectory($staging);
            }

            File::makeDirectory($staging, 0755, true);

            File::copyDirectory($packageRoot.'/resources/dist', $staging);

            if (File::isDirectory($packageRoot.'/resources/libs')) {
                File::copyDirectory($packageRoot.'/resources/libs', $staging.'/libs');
            }

            if (File::isDirectory($packageRoot.'/resources/public')) {
                File::copyDirectory($packageRoot.'/resources/public', $staging.'/public');
            }

            if (! PublishedAssets::verify($staging)) {
                $this->error('Aura assets failed integrity verification after staging. Previous publish left intact.');
                File::deleteDirectory($staging);

                return self::FAILURE;
            }

            if (File::exists($target)) {
                File::move($target, $backup);
            }

            File::move($staging, $target);

            if (File::exists($backup)) {
                File::deleteDirectory($backup);
            }

            $this->info('Aura assets published.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if (File::exists($staging)) {
                File::deleteDirectory($staging);
            }

            if (File::exists($backup) && ! File::exists($target)) {
                File::move($backup, $target);
            } elseif (File::exists($backup)) {
                File::deleteDirectory($backup);
            }

            $this->error('Aura publish failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
