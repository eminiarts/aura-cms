<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
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

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'fields' => [
                'password' => $password,
            ],
        ]);

        if (config('aura.teams')) {
            DB::table('teams')->insert([
                'name' => $name,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $team = Team::first();
            $user->current_team_id = $team->id;
            $user->save();
        }

        auth()->loginUsingId($user->id);

        $roleData = [
            'type' => 'Role',
            'title' => 'Super Admin',
            'slug' => 'super_admin',
            'description' => 'Super Admin can perform everything.',
            'super_admin' => true,
            'permissions' => [],
            'user_id' => $user->id,
        ];

        if (config('aura.teams')) {
            $roleData['team_id'] = $team->id;
        }

        $role = Role::create($roleData);

        $user->update(['roles' => [$role->id]]);

        $this->info('User created successfully.');

        return static::SUCCESS;
    }
}
