<?php

use Aura\Base\Livewire\CreateResource;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Pest\Livewire\livewire;

//uses()->group('current');

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// make sure only superadmins can access this component
test('config features test', function () {

    expect(config('aura.features'))->toMatchArray([
        'global_search' => true,
        'notifications' => true,
        'bookmarks' => true,
        'plugins' => true,
        'flows' => false,
        'forms' => true,
        'resource_editor' => config('app.env') == 'production' ? false : true,
        'theme_options' => true,
        'global_config' => true,
        'user_profile' => true,
        'create_resource' => true,
        'resource_view' => true,
        'resource_edit' => true,
        'register' => true,
    ]);
});