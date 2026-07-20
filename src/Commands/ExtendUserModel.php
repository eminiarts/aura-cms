<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ExtendUserModel extends Command
{
    protected $description = 'Extend the User model with AuraUser';

    protected $signature = 'aura:extend-user-model';

    public function handle(): int
    {
        $filesystem = new Filesystem;
        $userModelPath = app_path('Models/User.php');

        if (! $filesystem->exists($userModelPath)) {
            $this->error('User model not found.');

            return self::FAILURE;
        }

        $content = $filesystem->get($userModelPath);

        if (str_contains($content, 'extends AuraUser')) {
            $this->info('User model already extends AuraUser.');

            return self::SUCCESS;
        }

        if (! str_contains($content, 'extends Authenticatable')) {
            $this->error('User model does not extend Authenticatable and could not be updated safely.');

            return self::FAILURE;
        }

        if (! $this->confirm('Do you want to extend the User model with AuraUser?', true)) {
            $this->info('User model extension cancelled.');

            return self::SUCCESS;
        }

        $content = preg_replace(
            '/^use\s+Aura\\\\Base\\\\Resources\\\\User\s+as\s+AuraUser;\R*/m',
            '',
            $content
        ) ?? $content;
        $content = preg_replace(
            '/^use Illuminate\\\\Foundation\\\\Auth\\\\User as Authenticatable;\R/m',
            '',
            $content
        ) ?? $content;
        $content = str_replace('extends Authenticatable', 'extends AuraUser', $content);
        $content = preg_replace(
            '/^(namespace [^;]+;)\R*/m',
            "$1\n\nuse Aura\\Base\\Resources\\User as AuraUser;\n",
            $content,
            1
        ) ?? $content;

        $filesystem->put($userModelPath, $content);

        $this->info('User model successfully extended with AuraUser.');

        return self::SUCCESS;
    }
}
