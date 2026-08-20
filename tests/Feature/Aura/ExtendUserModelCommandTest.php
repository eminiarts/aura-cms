<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->userModelPath = app_path('Models/User.php');
    $this->userModelContent = <<<'PHP'
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
}
PHP;

    // Ensure the Models directory exists
    if (! File::exists(app_path('Models'))) {
        File::makeDirectory(app_path('Models'), 0755, true);
    }

    // Create the test User model file
    File::put($this->userModelPath, $this->userModelContent);
});

afterEach(function () {
    // Clean up the test User model file
    if (File::exists($this->userModelPath)) {
        File::delete($this->userModelPath);
    }
});

describe('model extension', function () {
    it('extends user model with AuraUser', function () {
        $this->artisan('aura:extend-user-model')
            ->expectsConfirmation('Do you want to extend the User model with AuraUser?', 'yes')
            ->assertSuccessful();

        $updatedContent = File::get($this->userModelPath);

        expect($updatedContent)
            ->toContain('extends AuraUser')
            ->toContain('use Aura\Base\Resources\User as AuraUser');
    });

    it('keeps a fresh Laravel user model formatted after extension', function () {
        $this->artisan('aura:extend-user-model')
            ->expectsConfirmation('Do you want to extend the User model with AuraUser?', 'yes')
            ->assertSuccessful();

        $updatedContent = File::get($this->userModelPath);

        expect($updatedContent)
            ->toContain("namespace App\\Models;\n\nuse Aura\\Base\\Resources\\User as AuraUser;\n")
            ->not->toContain('use Illuminate\\Foundation\\Auth\\User as Authenticatable;')
            ->not->toContain("namespace App\\Models;\nuse ");
    });

    it('shows message when model already extends AuraUser', function () {
        // First extend the model
        $this->artisan('aura:extend-user-model')
            ->expectsConfirmation('Do you want to extend the User model with AuraUser?', 'yes')
            ->assertSuccessful();

        // Try to extend again
        $this->artisan('aura:extend-user-model')
            ->expectsOutput('User model already extends AuraUser.')
            ->assertSuccessful();
    });
});

describe('cancellation', function () {
    it('can cancel user model extension', function () {
        $this->artisan('aura:extend-user-model')
            ->expectsConfirmation('Do you want to extend the User model with AuraUser?', 'no')
            ->expectsOutput('User model extension cancelled.')
            ->assertSuccessful();

        $updatedContent = File::get($this->userModelPath);
        expect($updatedContent)->not->toContain('extends AuraUser');
    });
});

describe('error handling', function () {
    it('shows error when user model does not exist', function () {
        // Delete the User model
        File::delete($this->userModelPath);

        $this->artisan('aura:extend-user-model')
            ->expectsOutput('User model not found.')
            ->assertFailed();
    });
});
