<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试用户名登录.
     */
    public function test_user_can_login_by_username(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1.0/auth/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'username',
                ],
            ]);
    }

    /**
     * 测试邮箱登录.
     */
    public function test_user_can_login_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1.0/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'email',
                ],
            ]);
    }

    /**
     * 测试手机号登录.
     */
    public function test_user_can_login_by_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1.0/auth/login', [
            'phone' => '13800138000',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'phone',
                ],
            ]);
    }

    /**
     * 测试微信登录.
     */
    public function test_user_can_login_by_wechat(): void
    {
        $response = $this->postJson('/api/v1.0/auth/wechat/login', [
            'code' => 'test_code', // 假设微信授权码是test_code
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'nickname',
                    'avatar',
                ],
            ]);
    }

    /**
     * 测试登录失败 - 密码错误.
     */
    public function test_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1.0/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => '密码错误',
            ]);
    }

    /**
     * 测试登录失败 - 用户不存在.
     */
    public function test_cannot_login_with_nonexistent_user(): void
    {
        $response = $this->postJson('/api/v1.0/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => '用户不存在',
            ]);
    }

    /**
     * 测试登出功能.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $this->postJson('/api/v1.0/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1.0/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => '退出成功',
            ]);
    }
}
