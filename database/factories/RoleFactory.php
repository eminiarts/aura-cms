<?php

namespace Aura\Base\Database\Factories;

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
            'name' => $name = $this->faker->name(),
            'slug' => str($name)->slug(),
            'user_id' => 1,
            'team_id' => 1,
            // 'order' => null,
        ];
    }
}
