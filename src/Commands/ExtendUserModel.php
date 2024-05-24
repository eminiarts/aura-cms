<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ExtendUserModel extends Command
{
    protected $description = 'Extend the User model with AuraUser';

    protected $signature = 'aura:extend-user-model';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $filesystem = new Filesystem();
        $userModelPath = app_path('Models/User.php');

        if ($filesystem->exists($userModelPath)) {
            $content = $filesystem->get($userModelPath);

            if (strpos($content, 'extends AuraUser') === false) {
                if ($this->confirm('Do you want to extend the User model with AuraUser?', true)) {
                    // Remove any incorrect `use` statements and add the correct one
                    $content = preg_replace('/use .+Aura\\\\Base\\\\Models\\\\User as AuraUser;/m', '', $content);
                    $content = str_replace('extends Authenticatable', 'extends AuraUser', $content);
                    $content = preg_replace('/^namespace [^;]+;/m', "$0\nuse Aura\\Base\\Models\\User as AuraUser;", $content);

                    $filesystem->put($userModelPath, $content);

                    $this->info('User model successfully extended with AuraUser.');
                } else {
                    $this->info('User model extension cancelled.');
                }
            } else {
                $this->info('User model already extends AuraUser.');
            }
        } else {
            $this->error('User model not found.');
        }
    }
}
