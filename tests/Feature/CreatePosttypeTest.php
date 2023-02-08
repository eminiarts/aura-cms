<?php

namespace Tests\Feature\Livewire;

use App\Http\Livewire\CreatePosttype;
use Livewire\Livewire;
use Tests\TestCase;

class CreatePosttypeTest extends TestCase
{
    /** @test */
    public function the_component_can_render()
    {
        $component = Livewire::test(CreatePosttype::class);

        $component->assertStatus(200);
    }
}
