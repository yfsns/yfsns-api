<?php

namespace Database\Factories;

use App\Modules\Admin\Models\UserAudit;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserAuditFactory extends Factory
{
    protected $model = UserAudit::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'status' => UserAudit::STATUS_PENDING,
            'remark' => null,
            'reason' => null,
            'audit_time' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => UserAudit::STATUS_PENDING,
            ];
        });
    }

    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => UserAudit::STATUS_APPROVED,
                'remark' => $this->faker->sentence,
                'audit_time' => now(),
            ];
        });
    }

    public function rejected()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => UserAudit::STATUS_REJECTED,
                'reason' => $this->faker->sentence,
                'audit_time' => now(),
            ];
        });
    }
}
