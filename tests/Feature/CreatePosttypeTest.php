<?php

namespace Tests\Feature\Livewire;

use Eminiarts\Aura\Http\Livewire\CreatePosttype;
use Livewire\Livewire;
use Eminiarts\Aura\Tests\TestCase;

class CreatePosttypeTest extends TestCase
{
    /** @test */
    public function the_component_can_render()
    {
        $component = Livewire::test(CreatePosttype::class);

        $component->assertStatus(200);
    }
}
