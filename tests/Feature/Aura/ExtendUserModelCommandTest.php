<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->userModelPath = app_path('Models/User.php');
    $this->userModelContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // Some content
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

    it('adds use statement after namespace', function () {
        $this->artisan('aura:extend-user-model')
            ->expectsConfirmation('Do you want to extend the User model with AuraUser?', 'yes')
            ->assertSuccessful();

        $updatedContent = File::get($this->userModelPath);

        // Verify the use statement is placed correctly
        expect($updatedContent)->toMatch('/namespace App\\\\Models;\s+use Aura\\\\Base\\\\Resources\\\\User as AuraUser;/');
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
            ->assertSuccessful();
    });
});
