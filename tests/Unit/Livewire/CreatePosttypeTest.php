<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Arr;
use Eminiarts\Aura\Http\Livewire\CreatePosttype;
use Livewire\Testing\TestableLivewire;

it('should only allow super admins to mount the component', function () {
    auth()->shouldReceive('user->resource->isSuperAdmin')->once()->andReturn(false);
    $component = new CreatePosttype();

    $response = TestableLivewire::tester(CreatePosttype::class)
        ->call('mount');

    $response->assertForbidden();
});

it('should validate the input fields', function () {
    auth()->shouldReceive('user->resource->isSuperAdmin')->once()->andReturn(true);
    Artisan::shouldReceive('call')->once();

    TestableLivewire::tester(CreatePosttype::class)
        ->set('post.fields', ['name' => 'Invalid Value'])
        ->call('save')
        ->assertHasErrors(['post.fields.name' => 'required']);

    TestableLivewire::tester(CreatePosttype::class)
        ->set('post.fields', ['name' => 'Valid Value'])
        ->call('save')
        ->assertHasNoErrors();
});

it('should call the artisan command to create new posttype', function () {
    auth()->shouldReceive('user->resource->isSuperAdmin')->once()->andReturn(true);
    Artisan::shouldReceive('call')->once();

    TestableLivewire::tester(CreatePosttype::class)
        ->set('post.fields', ['name' => 'Test posttype'])
        ->call('save');
});
