<?php

namespace Plugins\WechatLogin;

use App\Modules\PluginSystem\BasePlugin;
use App\Modules\PluginSystem\Contracts\LoginPluginInterface;
use Exception;

use function in_array;
use function is_string;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Plugins\WechatLogin\Models\WechatConfig;

class Plugin extends BasePlugin implements LoginPluginInterface
{
    protected function initialize(): void
    {
        $this->name = 'wechatlogin';
        $this->version = '1.0.0';
        $this->description = '提供微信 OAuth 与扫码登录能力的插件';
        $this->author = 'yfsns';
        $this->requirements = ['php:8.1', 'laravel:10.0'];

        // 注意：不要在这里设置enabled状态，应该由PluginManager管理
        $this->enabled = false; // 默认未启用
    }

    /**
     * 实现 LoginPluginInterface.
     */
    public function getUniqueId(): string
    {
        // 格式：{author}-{plugin-name}-v{version}
        return strtolower($this->author . '-' . $this->name . '-v' . str_replace('.', '', $this->version));
    }

    public function getLoginMethods(): array
    {
        $methods = [];

        // 扫码登录（需要开放平台配置）
        if ($this->hasOpenPlatformConfig()) {
            $methods[] = [
                'id' => 'qrcode',
                'name' => '扫码登录',
                'type' => 'qrcode',
                'platform' => ['web', 'mobile'],
                'icon' => null,
                'description' => '使用微信扫码登录（需要微信开放平台账号）',
                'config' => [
                    'scope' => 'snsapi_login',
                ],
            ];
        }

        // OAuth登录（需要公众号配置）
        if ($this->hasOfficialAccountConfig()) {
            $methods[] = [
                'id' => 'oauth',
                'name' => '微信内授权',
                'type' => 'oauth',
                'platform' => ['mobile'],
                'icon' => null,
                'description' => '在微信内打开网页时使用（需要微信公众号）',
                'config' => [
                    'scope' => 'snsapi_userinfo',
                ],
            ];
        }

        return $methods;
    }

    public function getAuthUrl(string $methodId, string $redirectUri, array $params = []): string
    {
        /** @var Services\WechatAuthService $authService */
        $authService = app(Services\WechatAuthService::class);

        $state = $params['state'] ?? '';

        if ($methodId === 'qrcode') {
            if (! $this->hasOpenPlatformConfig()) {
                throw new Exception('微信开放平台配置未初始化，请先配置 type=open 的微信开放平台应用');
            }

            return $authService->getQrCodeLoginUrl($redirectUri, $state);
        }
        if ($methodId === 'oauth') {
            if (! $this->hasOfficialAccountConfig()) {
                throw new Exception('公众号配置未初始化，请先配置 type=mp 的微信公众号');
            }
            $scope = $params['scope'] ?? 'snsapi_userinfo';

            return $authService->getAuthUrl($redirectUri, $scope, $state);
        }

        throw new Exception("不支持的登录方法: {$methodId}");
    }

    public function handleCallback(string $methodId, array $callbackData): array
    {
        if (! isset($callbackData['code'])) {
            return [
                'success' => false,
                'error' => '授权码不能为空',
                'error_code' => 'MISSING_CODE',
            ];
        }

        /** @var Services\WechatAuthService $authService */
        $authService = app(Services\WechatAuthService::class);

        try {
            if ($methodId === 'qrcode') {
                $result = $authService->qrCodeLogin($callbackData['code']);
            } elseif ($methodId === 'oauth') {
                $result = $authService->oauthLogin($callbackData['code']);
            } else {
                return [
                    'success' => false,
                    'error' => "不支持的登录方法: {$methodId}",
                    'error_code' => 'INVALID_METHOD',
                ];
            }

            if (! $result) {
                return [
                    'success' => false,
                    'error' => '登录失败，请重试',
                    'error_code' => 'LOGIN_FAILED',
                ];
            }

            return [
                'success' => true,
                'token_data' => $result['token_data'] ?? [],
                'third_party_info' => $result['wechat_info'] ?? [],
            ];
        } catch (Exception $e) {
            Log::error('微信登录回调处理失败', [
                'method' => $methodId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'CALLBACK_ERROR',
            ];
        }
    }

    public function isConfigured(): bool
    {
        return $this->hasOpenPlatformConfig() || $this->hasOfficialAccountConfig();
    }


    protected function onEnable(): void
    {
        parent::onEnable();

        // 注册服务
        $this->registerServices();

        // 加载路由
        $this->loadRoutes();

        // 执行迁移
        $this->runMigrations();

        Log::info('WechatLogin plugin onEnable executed');
    }

    protected function determineEnabledState(): bool
    {
        if (app()->bound('plugin.manager')) {
            return app('plugin.manager')->isPluginEnabled($this->name);
        }

        $enabled = config('plugins.enabled', []);
        if (is_string($enabled)) {
            $enabled = array_filter(array_map('trim', explode(',', $enabled)));
        }

        return in_array($this->name, (array) $enabled, true);
    }

    protected function registerServices(): void
    {
        // 注册微信认证服务
        app()->singleton(
            \Plugins\WechatLogin\Services\WechatAuthService::class,
            fn () => new \Plugins\WechatLogin\Services\WechatAuthService()
        );
    }

    protected function loadRoutes(): void
    {
        // 加载路由
        if (file_exists(__DIR__ . '/routes/api.php')) {
            \Illuminate\Support\Facades\Route::middleware(['api'])
                ->prefix('api/v1/wechat')
                ->group(__DIR__ . '/routes/api.php');
        }
    }

    /**
     * 检查是否有开放平台配置.
     */
    protected function hasOpenPlatformConfig(): bool
    {
        return WechatConfig::where('type', 'open')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * 检查是否有公众号配置.
     */
    protected function hasOfficialAccountConfig(): bool
    {
        return WechatConfig::where('type', 'mp')
            ->where('is_active', true)
            ->exists();
    }
}
