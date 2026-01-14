<?php

namespace Database\Factories;

use App\Modules\User\Models\User;
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
            'username' => $this->faker->userName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'nickname' => $this->faker->name,
            'avatar' => $this->faker->imageUrl(),
            'phone' => '138' . $this->faker->numberBetween(10000000, 99999999),
            'wechat_openid' => null,
            'wechat_unionid' => null,
            'gender' => $this->faker->randomElement([0, 1, 2]), // 0=other, 1=male, 2=female
            'birthday' => $this->faker->date(),
            'bio' => $this->faker->text(200),
            'settings' => json_encode(['theme' => 'light']),
            'is_admin' => false,
            'role_id' => 2,
            'status' => 1,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
                'phone_verified_at' => null,
            ];
        });
    }

    /**
     * Indicate that the model's phone should be unverified.
     */
    public function phoneUnverified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'phone_verified_at' => null,
            ];
        });
    }

    /**
     * Indicate that the model's status should be disabled.
     */
    public function disabled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 0,
            ];
        });
    }

    /**
     * Indicate that the model is an admin.
     */
    public function admin(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_admin' => true,
                'role_id' => 1,
            ];
        });
    }

    /**
     * Indicate that the model is inactive.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 0,
            ];
        });
    }
}
