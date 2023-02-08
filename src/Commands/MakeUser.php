<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\Command;
use Eminiarts\Aura\Resources\User;

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
            'password' => $password,
        ]);

        return static::SUCCESS;
    }
}
