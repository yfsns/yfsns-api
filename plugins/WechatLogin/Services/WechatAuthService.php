<?php

namespace Plugins\WechatLogin\Services;

use App\Modules\Auth\Services\AuthService as CoreAuthService;
use App\Modules\User\Models\User;
use EasyWeChat\OfficialAccount\Application;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Plugins\WechatLogin\Models\UserWechat;
use Plugins\WechatLogin\Models\WechatConfig;
use RuntimeException;

class WechatAuthService
{
    protected ?WechatConfig $mpConfig = null;

    protected ?WechatConfig $openConfig = null;

    protected ?WechatConfig $miniConfig = null;

    protected ?Application $mpApp = null;

    protected ?Application $openApp = null;

    public function __construct()
    {
        $this->loadConfigs();
        $this->initApps();
    }

    public function getAuthUrl(string $redirectUri, string $scope = 'snsapi_userinfo', string $state = ''): string
    {
        if (! $this->mpApp) {
            throw new RuntimeException('公众号配置未初始化，请先配置 type=mp 的微信公众号');
        }

        $state = $state ?: Str::random(16);

        return $this->mpApp->getOAuth()
            ->scopes([$scope])
            ->redirect($redirectUri, $state);
    }

    public function getQrCodeLoginUrl(string $redirectUri, string $state = ''): string
    {
        if (! $this->openApp) {
            throw new RuntimeException('开放平台配置未初始化，请先配置 type=open 的微信开放平台应用');
        }

        $state = $state ?: Str::random(16);

        return $this->openApp->getOAuth()
            ->scopes(['snsapi_login'])
            ->redirect($redirectUri, $state);
    }

    public function getUserInfoByCode(string $code): ?array
    {
        if (! $this->mpApp) {
            Log::error('公众号应用未初始化');

            return null;
        }

        try {
            $oauth = $this->mpApp->getOAuth();
            $user = $oauth->userFromCode($code);

            if ($user) {
                return [
                    'openid' => $user->getId(),
                    'nickname' => $user->getNickname(),
                    'avatar' => $user->getAvatar(),
                    'unionid' => $user->getRaw()['unionid'] ?? null,
                    'province' => $user->getRaw()['province'] ?? '',
                    'city' => $user->getRaw()['city'] ?? '',
                    'country' => $user->getRaw()['country'] ?? '',
                    'sex' => $user->getRaw()['sex'] ?? 0,
                ];
            }
        } catch (Exception $e) {
            Log::error('通过授权码获取用户信息失败（公众号）', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function getUserInfoByQrCode(string $code): ?array
    {
        if (! $this->openApp) {
            Log::error('开放平台应用未初始化', [
                'has_open_config' => $this->openConfig ? 'yes' : 'no',
            ]);

            return null;
        }

        try {
            $oauth = $this->openApp->getOAuth();
            $user = $oauth->userFromCode($code);

            if ($user) {
                $userInfo = [
                    'openid' => $user->getId(),
                    'nickname' => $user->getNickname(),
                    'avatar' => $user->getAvatar(),
                    'unionid' => $user->getRaw()['unionid'] ?? null,
                    'province' => $user->getRaw()['province'] ?? '',
                    'city' => $user->getRaw()['city'] ?? '',
                    'country' => $user->getRaw()['country'] ?? '',
                    'sex' => $user->getRaw()['sex'] ?? 0,
                ];

                Log::info('微信API返回用户信息成功', [
                    'openid' => substr($userInfo['openid'], 0, 10) . '...',
                    'nickname' => $userInfo['nickname'],
                ]);

                return $userInfo;
            }

            Log::error('微信API返回的用户对象为空');
        } catch (Exception $e) {
            Log::error('扫码登录获取用户信息失败（开放平台）', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function createOrUpdateUser(array $userInfo): ?User
    {
        try {
            Log::info('开始创建或更新用户', [
                'openid' => substr($userInfo['openid'] ?? '', 0, 10) . '...',
                'unionid' => $userInfo['unionid'] ?? 'null',
            ]);

            $wechatUser = null;

            if (! empty($userInfo['unionid'])) {
                $wechatUser = UserWechat::where('unionid', $userInfo['unionid'])->first();
                if ($wechatUser) {
                    Log::info('通过 unionid 找到已存在用户', ['user_id' => $wechatUser->user_id]);
                }
            }

            if (! $wechatUser) {
                $wechatUser = UserWechat::where('openid', $userInfo['openid'])->first();
                if ($wechatUser) {
                    Log::info('通过 openid 找到已存在用户', ['user_id' => $wechatUser->user_id]);
                }
            }

            if ($wechatUser) {
                $wechatUser->update([
                    'openid' => $userInfo['openid'],
                    'unionid' => $userInfo['unionid'] ?? $wechatUser->unionid,
                    'nickname' => $userInfo['nickname'],
                    'avatar' => $userInfo['avatar'],
                    'province' => $userInfo['province'] ?? '',
                    'city' => $userInfo['city'] ?? '',
                    'country' => $userInfo['country'] ?? '',
                    'sex' => $userInfo['sex'] ?? 0,
                ]);

                Log::info('更新用户信息成功', ['user_id' => $wechatUser->user->id]);

                return $wechatUser->user;
            }

            Log::info('创建新微信用户');

            $user = User::create([
                'username' => 'wx_' . Str::random(8),
                'password' => null,
                'nickname' => $userInfo['nickname'],
                'avatar' => $userInfo['avatar'],
                'status' => User::STATUS_ENABLED,
                'role_id' => 2,
            ]);

            Log::info('创建 User 成功', ['user_id' => $user->id]);

            UserWechat::create([
                'user_id' => $user->id,
                'openid' => $userInfo['openid'],
                'unionid' => $userInfo['unionid'] ?? null,
                'nickname' => $userInfo['nickname'],
                'avatar' => $userInfo['avatar'],
                'province' => $userInfo['province'] ?? '',
                'city' => $userInfo['city'] ?? '',
                'country' => $userInfo['country'] ?? '',
                'sex' => $userInfo['sex'] ?? 0,
            ]);

            Log::info('创建 UserWechat 关联成功', ['user_id' => $user->id]);

            return $user;
        } catch (Exception $e) {
            Log::error('创建或更新微信用户失败', [
                'userInfo' => $userInfo,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function qrCodeLogin(string $code): ?array
    {
        try {
            $userInfo = $this->getUserInfoByQrCode($code);
            if (! $userInfo) {
                Log::error('扫码登录失败：获取微信用户信息失败', ['code' => $code]);

                return null;
            }

            $user = $this->createOrUpdateUser($userInfo);
            if (! $user) {
                Log::error('扫码登录失败：创建或更新用户失败', ['userInfo' => $userInfo]);

                return null;
            }

            /** @var CoreAuthService $authService */
            $authService = app(CoreAuthService::class);
            // JWT 已移除，暂时返回空 token 数据
            // TODO: 实现 Sanctum token 生成
            $tokenData = ['token' => 'placeholder_token'];

            return [
                'token_data' => $tokenData,
                'wechat_info' => $userInfo,
            ];
        } catch (Exception $e) {
            Log::error('扫码登录异常', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function oauthLogin(string $code): ?array
    {
        try {
            $userInfo = $this->getUserInfoByCode($code);
            if (! $userInfo) {
                return null;
            }

            $user = $this->createOrUpdateUser($userInfo);
            if (! $user) {
                return null;
            }

            /** @var CoreAuthService $authService */
            $authService = app(CoreAuthService::class);
            // JWT 已移除，暂时返回空 token 数据
            // TODO: 实现 Sanctum token 生成
            $tokenData = ['token' => 'placeholder_token'];

            return [
                'token_data' => $tokenData,
                'wechat_info' => $userInfo,
            ];
        } catch (Exception $e) {
            Log::error('OAuth 登录失败', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function bindWechat(int $userId, string $code): bool
    {
        try {
            $userInfo = $this->getUserInfoByCode($code);
            if (! $userInfo) {
                return false;
            }

            $existingUser = UserWechat::where('openid', $userInfo['openid'])->first();
            if ($existingUser && $existingUser->user_id !== $userId) {
                return false;
            }

            UserWechat::updateOrCreate(
                ['user_id' => $userId],
                [
                    'openid' => $userInfo['openid'],
                    'unionid' => $userInfo['unionid'] ?? null,
                    'nickname' => $userInfo['nickname'],
                    'avatar' => $userInfo['avatar'],
                    'province' => $userInfo['province'] ?? '',
                    'city' => $userInfo['city'] ?? '',
                    'country' => $userInfo['country'] ?? '',
                    'sex' => $userInfo['sex'] ?? 0,
                ]
            );

            return true;
        } catch (Exception $e) {
            Log::error('绑定微信账号失败', [
                'userId' => $userId,
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function unbindWechat(int $userId): bool
    {
        try {
            UserWechat::where('user_id', $userId)->delete();

            return true;
        } catch (Exception $e) {
            Log::error('解绑微信账号失败', [
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getJsapiConfig(string $url): array
    {
        if (! $this->mpApp) {
            return [];
        }

        try {
            return $this->mpApp->getJssdk()->buildConfig(['scanQRCode'], false, false, false, $url);
        } catch (Exception $e) {
            Log::error('获取 JSAPI 配置失败', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getAccessToken(): ?string
    {
        if (! $this->mpApp) {
            return null;
        }

        try {
            return $this->mpApp->getAccessToken()->getToken();
        } catch (Exception $e) {
            Log::error('获取 access_token 失败', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function loadConfigs(): void
    {
        $this->mpConfig = WechatConfig::where('type', 'mp')->where('is_active', true)->first();
        $this->openConfig = WechatConfig::where('type', 'open')->where('is_active', true)->first();
        $this->miniConfig = WechatConfig::where('type', 'mini')->where('is_active', true)->first();
    }

    protected function initApps(): void
    {
        if ($this->mpConfig) {
            $this->mpApp = new Application([
                'app_id' => $this->mpConfig->app_id,
                'secret' => $this->mpConfig->app_secret,
                'token' => $this->mpConfig->token,
                'aes_key' => $this->mpConfig->aes_key,
            ]);
        }

        if ($this->openConfig) {
            $this->openApp = new Application([
                'app_id' => $this->openConfig->app_id,
                'secret' => $this->openConfig->app_secret,
                'token' => $this->openConfig->token,
                'aes_key' => $this->openConfig->aes_key,
            ]);
        }
    }
}
