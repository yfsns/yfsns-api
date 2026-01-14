<?php

namespace Tests\Feature\Auth;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试用户名注册.
     */
    public function test_user_can_register_by_username(): void
    {
        $response = $this->postJson('/api/v1/auth/register?type=username', [
            'username' => 'testuser',
            'password' => 'password',
            'password_confirmation' => 'password',
            'nickname' => 'Test User',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'username',
                    'nickname',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'nickname' => 'Test User',
        ]);

        // 测试注册成功后发送通知
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\Modules\User\Models\User',
            'notifiable_id' => $response->json('user.id'),
            'type' => 'App\Modules\Notification\Notifications\UserRegisteredNotification',
        ]);
    }

    /**
     * 测试邮箱注册.
     */
    public function test_user_can_register_by_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register?type=email', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'code' => '123456', // 假设验证码是123456
            'nickname' => 'Test User',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'email',
                    'nickname',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'nickname' => 'Test User',
        ]);
    }

    /**
     * 测试手机号注册.
     */
    public function test_user_can_register_by_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/register?type=phone', [
            'phone' => '13800138000',
            'password' => 'password',
            'password_confirmation' => 'password',
            'code' => '123456', // 假设验证码是123456
            'nickname' => 'Phone User',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'phone',
                    'nickname',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => '13800138000',
            'nickname' => 'Phone User',
        ]);
    }

    /**
     * 测试注册参数验证
     */
    public function test_register_validation(): void
    {
        $response = $this->postJson('/api/v1/auth/register?type=email', [
            'email' => 'invalid-email',
            'password' => '123', // 密码太短
            'password_confirmation' => '123',
            'code' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'code']);
    }

    /**
     * 测试重复注册.
     */
    public function test_cannot_register_with_existing_email(): void
    {
        // 先创建一个用户
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register?type=email', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'code' => '123456',
            'nickname' => 'Test User',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
