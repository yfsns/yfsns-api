<?php

namespace Tests\Unit\Modules\Auth\Services;

use App\Exceptions\AuthException;
use App\Http\Services\IpLocationService;
use App\Modules\Auth\Services\AuthService;
use App\Modules\User\Models\User;
use App\Modules\User\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private Mockery\MockInterface|UserService $userServiceMock;

    private Mockery\MockInterface|IpLocationService $ipLocationServiceMock;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建依赖的模拟对象
        $this->userServiceMock = Mockery::mock(UserService::class);
        $this->ipLocationServiceMock = Mockery::mock(IpLocationService::class);

        // 实例化AuthService并注入模拟对象
        $this->authService = new AuthService(
            $this->userServiceMock,
            $this->ipLocationServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 测试使用有效凭据登录成功
     */
    public function test_login_with_valid_credentials(): void
    {
        // 准备测试数据
        $loginData = [
            'username' => 'testuser',
            'password' => 'password123',
        ];

        // 创建模拟用户
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('status')->andReturn(AuthService::STATUS_ACTIVE);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $user->shouldReceive('status')->andReturn(AuthService::STATUS_ACTIVE);
        $user->shouldReceive('update')->withAnyArgs()->once();
        $user->shouldReceive('notify')->withAnyArgs()->once();
        $user->shouldReceive('getKey')->andReturn(1);
        // JWT 已移除，移除 JWT 相关模拟
        // $user->shouldReceive('getJWTIdentifier')->andReturn(1);
        // $user->shouldReceive('getJWTCustomClaims')->andReturn([]);
        $user->shouldReceive('getAttributeValue')->with('id')->andReturn(1);

        // 模拟Auth guard
        $authGuardMock = Mockery::mock('Illuminate\Contracts\Auth\Guard');
        $authGuardMock->shouldReceive('attempt')->with([
            'username' => $loginData['username'],
            'password' => $loginData['password'],
        ])->andReturn('fake-token');
        $authGuardMock->shouldReceive('user')->andReturn($user);
        $authGuardMock->shouldReceive('logout')->never();

        // 模拟所有可能的guard调用
        Auth::shouldReceive('guard')->withAnyArgs()->andReturn($authGuardMock);

        // JWT 已移除，移除 JWT 相关模拟
        // 模拟JWTAuth
        // $jwtMock = Mockery::mock();
        // $jwtMock->shouldReceive('fromUser')->with($user)->andReturn('fake-access-token', 'fake-refresh-token');
        // \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::shouldReceive('customClaims')->andReturn($jwtMock);

        // 模拟IP位置服务
        $this->ipLocationServiceMock->shouldReceive('getClientIp')->withAnyArgs()->andReturn('127.0.0.1');
        $this->ipLocationServiceMock->shouldReceive('getLocation')->with('127.0.0.1')->andReturn(['location' => 'Localhost']);

        // 创建并配置模拟Request
        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('ip')->andReturn('127.0.0.1');
        $requestMock->shouldReceive('userAgent')->andReturn('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        // 执行登录
        $result = $this->authService->login($loginData, $requestMock);

        // 验证结果
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('refresh_expires_in', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('cookies', $result);
    }

    /**
     * 测试使用无效凭据登录失败.
     */
    public function test_login_with_invalid_credentials(): void
    {
        // 准备测试数据
        $loginData = [
            'username' => 'invaliduser',
            'password' => 'wrongpassword',
        ];

        // 模拟Auth guard
        Auth::shouldReceive('guard')->with('api')->andReturnSelf();
        Auth::shouldReceive('attempt')->with([
            'username' => $loginData['username'],
            'password' => $loginData['password'],
        ])->andReturn(false);

        // 验证抛出异常
        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('用户名或密码错误');

        // 执行登录
        $this->authService->login($loginData);
    }

    /**
     * 测试登录禁用用户.
     */
    public function test_login_with_disabled_user(): void
    {
        // 准备测试数据
        $loginData = [
            'username' => 'disableduser',
            'password' => 'password123',
        ];

        // 创建模拟禁用用户
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('status')->andReturn(AuthService::STATUS_DISABLED);
        $user->shouldReceive('status')->andReturn(AuthService::STATUS_DISABLED);

        // 模拟Auth guard
        Auth::shouldReceive('guard')->with('api')->andReturnSelf();
        Auth::shouldReceive('attempt')->with([
            'username' => $loginData['username'],
            'password' => $loginData['password'],
        ])->andReturn('fake-token');
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('logout')->once();

        // 验证抛出异常
        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('账号已被禁用');

        // 执行登录
        $this->authService->login($loginData);
    }

    /**
     * 测试注册新用户.
     */
    public function test_register_new_user(): void
    {
        // 准备测试数据
        $registerData = [
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'nickname' => 'New User',
        ];

        // 模拟用户名检查
        $this->userServiceMock->shouldReceive('usernameExists')->with('newuser')->andReturn(false);

        // 创建模拟用户
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(1);
        // JWT 已移除，移除 JWT 相关模拟
        // $user->shouldReceive('getJWTIdentifier')->andReturn(1);
        // $user->shouldReceive('getJWTCustomClaims')->andReturn([]);

        // 模拟用户创建
        $this->userServiceMock->shouldReceive('create')->with(
            Mockery::on(function ($data) use ($registerData) {
                return $data['email'] === $registerData['email'] &&
                       $data['nickname'] === $registerData['nickname'] &&
                       $data['username'] === 'newuser';
            }),
            Mockery::any()
        )->andReturn($user);

        // 执行注册
        $result = $this->authService->register($registerData);

        // 验证结果
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('user', $result);
    }

    /**
     * 测试注册新用户（用户名已存在）.
     */
    public function test_register_new_user_with_existing_username(): void
    {
        // 准备测试数据
        $registerData = [
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'nickname' => 'New User',
        ];

        // 模拟用户名检查（第一次已存在，第二次不存在）
        $this->userServiceMock->shouldReceive('usernameExists')->with('newuser')->andReturn(true);
        $this->userServiceMock->shouldReceive('usernameExists')->with('newuser1')->andReturn(false);

        // 创建模拟用户
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(1);
        // JWT 已移除，移除 JWT 相关模拟
        // $user->shouldReceive('getJWTIdentifier')->andReturn(1);
        // $user->shouldReceive('getJWTCustomClaims')->andReturn([]);

        // 模拟用户创建
        $this->userServiceMock->shouldReceive('create')->with(
            Mockery::on(function ($data) use ($registerData) {
                return $data['email'] === $registerData['email'] &&
                       $data['username'] === 'newuser1';
            }),
            Mockery::any()
        )->andReturn($user);

        // 执行注册
        $result = $this->authService->register($registerData);

        // 验证结果
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('access_token', $result);
    }

    /**
     * 测试退出登录.
     */
    public function test_logout(): void
    {
        // 创建模拟用户
        $user = Mockery::mock(User::class);

        // 模拟Auth logout
        Auth::shouldReceive('logout')->once();

        // 执行退出登录（不抛出异常即为成功）
        $this->authService->logout($user);

        // 断言成功
        $this->assertTrue(true);
    }

    /**
     * 测试短信验证码登录.
     */
    public function test_sms_login_with_user(): void
    {
        // 创建模拟用户
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(1);
        // JWT 已移除，移除 JWT 相关模拟
        // $user->shouldReceive('getJWTIdentifier')->andReturn(1);
        // $user->shouldReceive('getJWTCustomClaims')->andReturn([]);

        // 执行短信验证码登录
        $result = $this->authService->smsLoginWithUser($user);

        // 验证结果
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('user', $result);
    }

    /**
     * 测试获取当前用户信息.
     */
    public function test_me(): void
    {
        // 创建模拟用户
        $user = Mockery::mock(User::class);

        // 模拟Auth guard
        Auth::shouldReceive('guard')->with('api')->andReturnSelf();
        Auth::shouldReceive('user')->andReturn($user);

        // 执行获取当前用户信息
        $result = $this->authService->me();

        // 验证结果
        $this->assertSame($user, $result);
    }

    /**
     * 测试使用refresh token刷新获取新的access token.
     */
    public function test_refresh_token(): void
    {
        // 创建模拟用户
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(1);
        // JWT 已移除，移除 JWT 相关模拟
        // $user->shouldReceive('getJWTIdentifier')->andReturn(1);
        // $user->shouldReceive('getJWTCustomClaims')->andReturn([]);

        // 模拟JWTAuth生成token
        $jwtMock = Mockery::mock();
        $jwtMock->shouldReceive('fromUser')->with($user)->andReturn('fake-new-access-token', 'fake-new-refresh-token');
        \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::shouldReceive('customClaims')->andReturn($jwtMock);

        // 执行refreshToken方法
        $result = $this->authService->refreshToken($user);

        // 验证结果
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame($user, $result['user']);
        $this->assertSame('fake-new-access-token', $result['access_token']);
        $this->assertSame('fake-new-refresh-token', $result['refresh_token']);
    }
}
