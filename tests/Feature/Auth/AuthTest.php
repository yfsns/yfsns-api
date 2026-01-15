<?php

namespace Tests\Feature\Auth;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * 测试用户注册功能.
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1.0/auth/register/username', [
            'username' => 'testuser',
            'password' => 'password',
            'password_confirmation' => 'password',
            'nickname' => 'Test User',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
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
    }

    /**
     * 测试用户登录功能.
     */
    public function test_user_can_login(): void
    {
        // 先创建一个用户
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1.0/auth/login/username', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
            ]);
    }

    /**
     * 测试用户登出功能.
     */
    public function test_user_can_logout(): void
    {
        // 先创建一个用户并登录
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
        ]);

        $token = $this->postJson('/api/v1.0/auth/login/username', [
            'username' => 'testuser',
            'password' => 'password',
        ])->json('token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1.0/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => '登出成功',
            ]);
    }

    /**
     * 测试获取当前用户信息.
     */
    public function test_user_can_get_profile(): void
    {
        // 先创建一个用户并登录
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
        ]);

        $token = $this->postJson('/api/v1.0/auth/login/username', [
            'username' => 'testuser',
            'password' => 'password',
        ])->json('token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1.0/user/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'username',
                'nickname',
            ]);
    }

    /**
     * 测试手机号注册功能.
     */
    public function test_user_can_register_by_phone(): void
    {
        $response = $this->postJson('/api/v1.0/auth/register/phone', [
            'phone' => '13800138000',
            'code' => '123456', // 假设验证码是123456
            'password' => 'password',
            'password_confirmation' => 'password',
            'nickname' => 'Phone User',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
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
     * 测试微信登录功能.
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
     * 测试发送邮箱验证码
     */
    public function test_can_send_email_verification_code(): void
    {
        $email = $this->faker->safeEmail;

        $response = $this->postJson('/api/v1.0/auth/send-code/email', [
            'target' => $email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '验证码已发送',
            ]);
    }

    /**
     * 测试发送手机验证码
     */
    public function test_can_send_phone_verification_code(): void
    {
        $response = $this->postJson('/api/v1.0/auth/send-code/phone', [
            'target' => '13800138000',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '验证码已发送',
            ]);
    }

    /**
     * 测试微信绑定功能.
     */
    public function test_can_bind_wechat(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
        ]);

        $token = $this->postJson('/api/v1.0/auth/login/username', [
            'username' => 'testuser',
            'password' => 'password',
        ])->json('token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1.0/auth/wechat/bind', [
            'code' => 'test_wechat_code',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '微信绑定成功',
            ]);

        static::assertNotNull($user->fresh()->wechat_openid);
    }

    /**
     * 测试微信解绑功能.
     */
    public function test_can_unbind_wechat(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'wechat_openid' => 'test_openid',
        ]);

        $token = $this->postJson('/api/v1.0/auth/login/username', [
            'username' => 'testuser',
            'password' => 'password',
        ])->json('token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1.0/auth/wechat/unbind');

        $response->assertStatus(200)
            ->assertJson([
                'message' => '微信解绑成功',
            ]);

        static::assertNull($user->fresh()->wechat_openid);
    }
}
