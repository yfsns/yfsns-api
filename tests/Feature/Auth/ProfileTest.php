<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试获取当前用户信息.
     */
    public function test_can_get_current_user_profile(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1.0/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'email',
                'nickname',
                'avatar',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * 测试未登录用户无法获取个人信息.
     */
    public function test_cannot_get_profile_without_login(): void
    {
        $response = $this->getJson('/api/v1.0/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * 测试更新用户信息.
     */
    public function test_can_update_profile(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1.0/auth/profile', [
            'nickname' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '13800138000',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '个人信息更新成功',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nickname' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '13800138000',
        ]);
    }

    /**
     * 测试更新密码
     */
    public function test_can_update_password(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1.0/auth/password', [
            'old_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '密码更新成功',
            ]);

        // 清除当前认证状态
        auth('api')->logout();

        // 验证新密码可以登录
        static::assertTrue(auth('api')->attempt([
            'username' => $user->username,
            'password' => 'new-password',
        ]));
    }

    /**
     * 测试使用错误旧密码无法更新密码
     */
    public function test_cannot_update_password_with_wrong_old_password(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1.0/auth/password', [
            'old_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => '旧密码错误',
            ]);
    }

    /**
     * 测试使用无效的新密码无法更新密码
     */
    public function test_cannot_update_password_with_invalid_new_password(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1.0/auth/password', [
            'old_password' => 'password',
            'password' => '123', // 太短
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422);
    }
}
