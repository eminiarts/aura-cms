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
        return [
            'title' => $name = $this->faker->name(),
            'slug' => str($name)->slug(),
            'content' => $this->faker->paragraph(),
            'type' => 'Attachment',
            'user_id' => 1,
            'team_id' => 1,
            'parent_id' => null,
            'order' => null,

        ];
    }
}
