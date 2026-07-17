<?php

namespace Aura\Base\Tests\Factories;

use Aura\Base\Tests\Resources\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return array_filter([
            'title' => $name = $this->faker->name(),
            'slug' => str($name)->slug(),
            'content' => $this->faker->paragraph(),
            'type' => 'Post',
            'user_id' => 1,
            'team_id' => config('aura.teams') ? 1 : null,
            'parent_id' => null,
            'order' => null,

        ], fn ($value, $key) => $key !== 'team_id' || $value !== null, ARRAY_FILTER_USE_BOTH);
    }
}
