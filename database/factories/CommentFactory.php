<?php

namespace Database\Factories;

use App\Modules\Comment\Models\Comment;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition()
    {
        return [
            'target_id' => $this->faker->numberBetween(1, 100),
            'target_type' => 'post',
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'content_type' => 'text',
            'like_count' => $this->faker->numberBetween(0, 1000),
            'reply_count' => $this->faker->numberBetween(0, 100),
            'status' => Comment::STATUS_NORMAL,
            'created_at' => $this->faker->dateTimeThisMonth(),
            'updated_at' => $this->faker->dateTimeThisMonth(),
        ];
    }

    /**
     * 设置为回复评论.
     */
    public function asReply()
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => Comment::factory(),
            ];
        });
    }

    /**
     * 设置为待审核状态
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Comment::STATUS_PENDING,
            ];
        });
    }
}
