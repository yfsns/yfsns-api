<?php

namespace Database\Factories;

use App\Modules\File\Models\File;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word . '.' . $this->faker->fileExtension,
            'path' => 'uploads/' . $this->faker->word . '/' . $this->faker->word . '.' . $this->faker->fileExtension,
            'url' => 'https://example.com/uploads/' . $this->faker->word . '/' . $this->faker->word . '.' . $this->faker->fileExtension,
            'mime_type' => $this->faker->mimeType,
            'size' => $this->faker->numberBetween(1000, 1000000),
            'type' => 'other',
            'storage' => 'local',
            'user_id' => User::factory(),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function image()
    {
        return $this->state(function (array $attributes) {
            return [
                'mime_type' => 'image/jpeg',
                'name' => $this->faker->word . '.jpg',
                'path' => 'uploads/images/' . $this->faker->word . '.jpg',
            ];
        });
    }

    public function video()
    {
        return $this->state(function (array $attributes) {
            return [
                'mime_type' => 'video/mp4',
                'name' => $this->faker->word . '.mp4',
                'path' => 'uploads/videos/' . $this->faker->word . '.mp4',
            ];
        });
    }
}
