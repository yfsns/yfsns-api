<?php

namespace Database\Factories\Modules\Auth\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Auth\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => $this->faker->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'phone' => $this->faker->phoneNumber(),
            'phone_verified_at' => now(),
            'nickname' => $this->faker->name(),
            'avatar' => $this->faker->imageUrl(),
            'wechat_openid' => Str::random(28),
            'wechat_unionid' => Str::random(29),
            'remember_token' => Str::random(10),
        ];
    }
}
