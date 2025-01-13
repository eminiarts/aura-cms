<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeUser extends Command
{
    protected $description = 'Creates an Aura Super Admin.';

    protected $signature = 'aura:user
                            {--name= : The name of the user}
                            {--email= : A valid email address}
                            {--password= : The password for the user}';

    public function handle(): int
    {
        $name = $this->option('name') ?? text('What is your name?');
        $email = $this->option('email') ?? text('What is your email?');
        $password = $this->option('password') ?? password('What is your password?');

        /** @var User $user */
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'fields' => [
                'password' => $password,
            ],
        ]);

        Auth::loginUsingId($user->id);

        if (config('aura.teams')) {
            /** @var Team $team */
            $team = app(config('aura.resources.team'))->create([
                'name' => $name,
                'user_id' => $user->id,
            ]);

            $user->forceFill(['current_team_id' => $team->id])->save();
        }

        $this->info('User created successfully.');

        return static::SUCCESS;
    }
}
