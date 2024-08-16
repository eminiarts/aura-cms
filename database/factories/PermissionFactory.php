<?php

namespace Aura\Base\Database\Factories;

use Aura\Base\Resources\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

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
            'group' => $this->faker->name(),
            'description' => $this->faker->paragraph(),
            'user_id' => 1,
            // 'parent_id' => null,
            // 'order' => null,

        ];
    }
}
