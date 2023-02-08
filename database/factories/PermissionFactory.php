<?php

namespace Eminiarts\Aura\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $name = $this->faker->name(),
            'name' => str($name)->slug(),
            'content' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['Post', 'Project', 'Invoice', 'Post', 'Post']),
            'user_id' => 1,
            'parent_id' => null,
            'order' => null,
            'fields' => [
                'group' => $this->faker->randomElement(['Post', 'Project', 'Invoice', 'Post']),
            ],
        ];
    }
}
