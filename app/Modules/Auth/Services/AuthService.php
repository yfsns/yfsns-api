<?php

namespace App\Modules\Auth\Services;

use App\Exceptions\AuthException;
use App\Http\Services\IpLocationService;
use App\Http\Traits\IpRecordTrait;
use App\Modules\Notification\Events\UserLoggedIn;
use App\Modules\User\Models\User;
use App\Modules\User\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * 认证服务 - 使用 Laravel Sanctum
 */
class AuthService
{
    use IpRecordTrait;
    protected UserService $userService;
    protected IpLocationService $ipLocationService;

    public function __construct(UserService $userService, IpLocationService $ipLocationService)
    {
        $this->userService = $userService;
        $this->ipLocationService = $ipLocationService;
    }

    /**
     * 用户登录 - 统一返回，消除平台判断冗余
     */
    public function login(array $data, ?Request $request = null): array
    {
        // 直接查询用户，支持邮箱、手机号、用户名
        $user = $this->findUserByAccount($data['account']);

        if (!$user || !\Hash::check($data['password'], $user->password)) {
            throw AuthException::invalidCredentials();
        }

        // 检查用户状态
        if (!$user->isEnabled()) {
            throw AuthException::userDisabled();
        }

        // 手动登录用户到session
        Auth::login($user);

        // 记录登录信息
        $user->update(['last_login_at' => now()]);

        // 始终创建token，让AuthResource根据平台决定是否返回
        $token = $user->createToken('api-token')->plainTextToken;

        // 统一返回完整数据，消除平台判断冗余
        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1小时
            'refresh_expires_in' => 1209600, // 14天
        ];
    }

    /**
     * 通过账号查找用户：支持邮箱、手机号、用户名
     */
    private function findUserByAccount(string $account): ?\App\Modules\User\Models\User
    {
        return \App\Modules\User\Models\User::where(function ($query) use ($account) {
            $query->where('email', $account)
                  ->orWhere('phone', $account)
                  ->orWhere('username', $account);
        })->first();
    }


    /**
     * 用户注册
     */
    public function register(array $data, ?Request $request = null): array
    {
        $registerType = $data['register_type'] ?? 'email';

        // 生成或使用提供的用户名（如果未提供则随机生成）
        $username = $data['username'] ?? $this->generateRandomUsername();

        // 生成或使用提供的昵称（如果未提供则随机生成）
        $nickname = $data['nickname'] ?? $this->generateRandomNickname();

        // 准备用户数据
        $userData = [
            'password' => bcrypt($data['password']),
            'nickname' => $nickname,
            'username' => $username,
        ];

        // 根据注册类型添加额外字段
        if ($registerType === 'email' && isset($data['email'])) {
            $userData['email'] = $data['email'];
        } elseif ($registerType === 'phone' && isset($data['phone'])) {
            $userData['phone'] = $data['phone'];
        }

        // 创建用户
        $user = $this->userService->create($userData, $request);

        // 注册成功后自动登录
        Auth::login($user);

        return [
            'user' => $user,
        ];
    }



    /**
     * 获取当前用户信息
     */
    public function me(): ?User
    {
        // Laravel Sanctum 会自动检测认证方式（session或token）
        return Auth::user();
    }



    /**
     * 退出登录 - 智能清理认证状态
     */
    public function logout(User $user, $request = null): void
    {
        // 智能检测认证类型并处理
        if (Auth::guard('web')->check()) {
            // Web session认证：完整清除session
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();
        }

        // Token认证：安全删除当前token（避免TransientToken错误）
        if ($request && $token = $request->bearerToken()) {
            // 通过Bearer token删除
            $user->tokens()->where('token', hash('sha256', $token))->delete();
        } elseif ($currentToken = $user->currentAccessToken()) {
            // 只删除真正的PersonalAccessToken，避免TransientToken错误
            if ($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $currentToken->delete();
            }
        }
    }

    // ============ 私有方法 ============

    /**
     * 生成随机唯一用户名
     */
    protected function generateRandomUsername(): string
    {
        $adjectives = [
            'Happy', 'Smart', 'Brave', 'Kind', 'Active', 'Gentle',
            'Hardworking', 'Honest', 'Cute', 'Cool', 'Beautiful', 'Excellent',
            'Joyful', 'Sunny', 'Artistic', 'Fashionable', 'Cool', 'Adorable',
            'Swift', 'Bright', 'Creative', 'Elegant', 'Friendly', 'Graceful'
        ];

        $nouns = [
            'Cat', 'Dog', 'Rabbit', 'Bear', 'Lion', 'Tiger',
            'Deer', 'Horse', 'Sheep', 'Pig', 'Chicken', 'Duck',
            'Fish', 'Bird', 'Bee', 'Butterfly', 'Star', 'Moon',
            'Tree', 'Flower', 'Mountain', 'River', 'Sun', 'Cloud'
        ];

        $maxAttempts = 10;
        $attempt = 0;

        do {
            $adjective = $adjectives[array_rand($adjectives)];
            $noun = $nouns[array_rand($nouns)];
            $number = rand(100, 999);
            $username = strtolower($adjective . $noun . $number);
            $attempt++;
        } while ($this->userService->usernameExists($username) && $attempt < $maxAttempts);

        // 如果仍然重复，添加时间戳后缀
        if ($this->userService->usernameExists($username)) {
            $username .= '_' . time();
        }

        return $username;
    }

    /**
     * 生成随机昵称
     */
    protected function generateRandomNickname(): string
    {
        $adjectives = [
            '快乐的', '聪明的', '勇敢的', '善良的', '活泼的', '温柔的',
            '勤劳的', '诚实的', '可爱的', '帅气的', '美丽的', '优秀的',
            '开心的', '阳光的', '文艺的', '时尚的', '酷酷的', '萌萌的'
        ];

        $nouns = [
            '小猫', '小狗', '小兔', '小熊', '小狮', '小虎',
            '小鹿', '小马', '小羊', '小猪', '小鸡', '小鸭',
            '小鱼', '小鸟', '小蜜蜂', '小蝴蝶', '小星星', '小月亮'
        ];

        $maxAttempts = 10;
        $attempt = 0;

        do {
            $adjective = $adjectives[array_rand($adjectives)];
            $noun = $nouns[array_rand($nouns)];
            $nickname = $adjective . $noun;
            $attempt++;
        } while ($this->nicknameExists($nickname) && $attempt < $maxAttempts);

        // 如果仍然重复，添加随机数字后缀
        if ($this->nicknameExists($nickname)) {
            $nickname .= rand(100, 999);
        }

        return $nickname;
    }

    /**
     * 检查昵称是否已存在
     */
    protected function nicknameExists(string $nickname): bool
    {
        return \App\Modules\User\Models\User::where('nickname', $nickname)->exists();
    }

    /**
     * 更新用户登录信息（优化版：只记录IP，不获取地理位置）
     */
    protected function updateUserLoginInfo(User $user, ?Request $request): void
    {
        $updateData = ['last_login_at' => now()];

        if ($request) {
            // 只记录IP地址，不获取地理位置信息
            $updateData['last_login_ip'] = $request->ip();
        }

        $user->update($updateData);
    }
}
