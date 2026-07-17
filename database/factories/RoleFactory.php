<?php

namespace Aura\Base\Database\Factories;

use Aura\Base\Resources\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Role>
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return array_filter([
            'name' => $name = $this->faker->name(),
            'slug' => str($name)->slug(),
            'user_id' => 1,
            'team_id' => config('aura.teams') ? 1 : null,
            // 'order' => null,
        ], fn ($value, $key) => $key !== 'team_id' || $value !== null, ARRAY_FILTER_USE_BOTH);
    }
}
