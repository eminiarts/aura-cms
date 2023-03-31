<?php

namespace Eminiarts\Aura\Commands;

use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MakeUser extends Command
{
    protected $description = 'Creates an Aura Super Admin.';

    protected $signature = 'aura:user
                            {--name= : The name of the user}
                            {--email= : A valid email address}
                            {--password= : The password for the user}';

    public function handle(): int
    {
        $name = $this->ask('What is your name?');

        $email = $this->ask('What is your email?');

        $password = $this->secret('What is your password?');

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

        // Create Role
        $roleData = [
            'type' => 'Role',
            'title' => 'Super Admin',
            'slug' => 'super_admin',
            'name' => 'Super Admin',
            'description' => 'Super Admin has can perform everything.',
            'super_admin' => true,
            'permissions' => [],
            'user_id' => $user->id,
        ];

        if (config('aura.teams')) {
            $roleData['team_id'] = $team->id;
        }

        $role = Role::create($roleData);

        $user->update(['fields' => ['roles' => [$role->id]]]);

        // Attach the user to the team
        //$team->users()->attach($user->id, ['role' => $role->id]);

        $this->info('User created successfully.');

        return static::SUCCESS;
    }
}
