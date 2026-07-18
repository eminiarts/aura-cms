<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeUser extends Command
{
    protected $description = 'Creates an Aura Admin.';

    protected $signature = 'aura:user
                            {--name= : The name of the user}
                            {--email= : A valid email address}
                            {--password= : The password for the user}
                            {--global-admin : Grant the user instance-level Global Admin status}';

    public function handle(): int
    {
        $name = $this->option('name') ?? text('What is your name?');
        $email = $this->option('email') ?? text('What is your email?');
        $password = $this->option('password') ?? password('What is your password?');

        $globalAdmin = (bool) $this->option('global-admin');

        // Offer the Global Admin choice on any interactive run that did not
        // already pass --global-admin — including partial-option runs like
        // `aura:user --name=X`. Non-interactive runs (scripts, CI, --no-interaction)
        // keep the flag off unless --global-admin was passed, so automation is
        // never blocked on a prompt.
        if (! $globalAdmin && $this->input->isInteractive()) {
            $globalAdmin = confirm(label: 'Should this user be a Global Admin?', default: false);
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
                'name' => $name,
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
}
