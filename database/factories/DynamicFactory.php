<?php

namespace Database\Factories;

use App\Modules\post\Models\post;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DynamicFactory extends Factory
{
    protected $model = post::class;

    public function definition()
    {
        return [
            'user_id' => User::factory()->create()->id,
            'content' => $this->faker->sentence,
            'type' => 'text',
            'status' => 1,
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => function (array $attributes) {
                // 大部分动态从未更新，updated_at = created_at
                // 小部分动态有过更新，updated_at > created_at
                $createdAt = $attributes['created_at'];
                return $this->faker->boolean(20) // 20% 的动态有过更新
                    ? $this->faker->dateTimeBetween($createdAt, 'now')
                    : $createdAt;
            },
        ];
    }
}
