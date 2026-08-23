<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeUser extends Command
{
    protected $description = 'Creates an Aura Admin.';

    protected $signature = 'aura:user
                            {--name= : The name of the user}
                            {--email= : A valid email address}
                            {--password= : The password for the user, at least 8 characters}
                            {--team-name= : The name of the first team, defaults to the name of the user}
                            {--global-admin : Grant the user instance-level Global Admin status}
                            {--no-global-admin : Do not grant the user instance-level Global Admin status}';

    public function handle(): int
    {
        $name = $this->option('name') ?? text('What is your name?');
        $email = $this->option('email') ?? text('What is your email?');
        $password = $this->option('password') ?? password('What is your password?');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', Rule::unique($this->usersTable(), 'email')],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return static::FAILURE;
        }

        $globalAdmin = ! $this->option('no-global-admin');

        // The first administrator is an instance operator by default. Interactive
        // runs can decline the default, while automation can opt out explicitly.
        if ($this->input->isInteractive()
            && ! $this->option('global-admin')
            && ! $this->option('no-global-admin')) {
            $globalAdmin = confirm(label: 'Should this user be a Global Admin?', default: true);
        }

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
                'name' => $this->option('team-name') ?: $name,
                'user_id' => $user->id,
            ]);

            $user->forceFill(['current_team_id' => $team->id])->save();
        } else {
            // Reuse the seeded admin Global Role, self-healing it from the shared
            // catalog defaults if the catalog was not seeded.
            $role = Role::firstOrCreateCatalogRole('admin');

            // This bootstrap command creates the first administrator, so attach
            // the role directly instead of going through delegated role editing.
            $user->roles()->sync([$role->id]);
        }

        // The CLI is a trusted bootstrap path, so it writes the flag directly.
        // saveQuietly bypasses the field pipeline's Global Admin escalation guard
        // (which would otherwise refuse, as the actor is not yet a Global Admin).
        if ($globalAdmin) {
            $user->forceFill(['global_admin' => true])->saveQuietly();
        }

        $this->info('User created successfully.');

        return static::SUCCESS;
    }

    /**
     * The table of the configured user resource, so the uniqueness check keeps
     * working for applications that swap out Aura's User resource.
     */
    protected function usersTable(): string
    {
        return app(config('aura.resources.user', User::class))->getTable();
    }
}
