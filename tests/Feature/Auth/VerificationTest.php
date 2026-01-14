<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试发送邮箱验证码
     */
    public function test_can_send_email_code(): void
    {
        $response = $this->postJson('/api/v1.0/auth/email/send-code', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '验证码发送成功',
            ]);
    }

    /**
     * 测试发送手机验证码
     */
    public function test_can_send_phone_code(): void
    {
        $response = $this->postJson('/api/v1.0/auth/phone/send-code', [
            'phone' => '13800138000',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '验证码发送成功',
            ]);
    }

    /**
     * 测试邮箱验证码发送频率限制.
     */
    public function test_cannot_send_email_code_too_frequently(): void
    {
        // 第一次发送
        $this->postJson('/api/v1.0/auth/email/send-code', [
            'email' => 'test@example.com',
        ]);

        // 立即再次发送
        $response = $this->postJson('/api/v1.0/auth/email/send-code', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'message' => '发送太频繁，请稍后再试',
            ]);
    }

    /**
     * 测试手机验证码发送频率限制.
     */
    public function test_cannot_send_phone_code_too_frequently(): void
    {
        // 第一次发送
        $this->postJson('/api/v1.0/auth/phone/send-code', [
            'phone' => '13800138000',
        ]);

        // 立即再次发送
        $response = $this->postJson('/api/v1.0/auth/phone/send-code', [
            'phone' => '13800138000',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'message' => '发送太频繁，请稍后再试',
            ]);
    }

    /**
     * 测试邮箱格式验证
     */
    public function test_email_validation(): void
    {
        $response = $this->postJson('/api/v1.0/auth/email/send-code', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * 测试手机号格式验证
     */
    public function test_phone_validation(): void
    {
        $response = $this->postJson('/api/v1.0/auth/phone/send-code', [
            'phone' => '12345', // 无效的手机号
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /**
     * 测试验证码验证
     */
    public function test_verify_code(): void
    {
        // 先发送验证码
        $this->postJson('/api/v1.0/auth/email/send-code', [
            'email' => 'test@example.com',
        ]);

        // 验证验证码
        $response = $this->postJson('/api/v1.0/auth/email/verify-code', [
            'email' => 'test@example.com',
            'code' => '123456', // 假设验证码是123456
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '验证成功',
            ]);
    }

    /**
     * 测试验证码错误.
     */
    public function test_verify_wrong_code(): void
    {
        // 先发送验证码
        $this->postJson('/api/v1.0/auth/email/send-code', [
            'email' => 'test@example.com',
        ]);

        // 验证错误的验证码
        $response = $this->postJson('/api/v1.0/auth/email/verify-code', [
            'email' => 'test@example.com',
            'code' => '000000', // 错误的验证码
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => '验证码错误',
            ]);
    }
}
