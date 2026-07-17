<?php

namespace Aura\Base\Database\Factories;

use Aura\Base\Resources\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Attachment>
     */
    protected $model = Attachment::class;

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
            'type' => 'Attachment',
            'user_id' => 1,
            'team_id' => config('aura.teams') ? 1 : null,
            'parent_id' => null,
            'order' => null,

        ], fn ($value, $key) => $key !== 'team_id' || $value !== null, ARRAY_FILTER_USE_BOTH);
    }
}
