<?php

namespace Aura\Base\Database\Factories;

use Aura\Base\Resources\Post;
use Aura\Base\Resources\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Aura\Base\Resources\Post>
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $name = $this->faker->name(),
            'slug' => str($name)->slug(),
            'type' => 'Role', //$this->faker->randomElement(['Post', 'Project', 'Invoice', 'Page', 'Post', 'Post']),
            'user_id' => 1,
            'team_id' => 1,
            'parent_id' => null,
            'order' => null,
        ];
    }
}
