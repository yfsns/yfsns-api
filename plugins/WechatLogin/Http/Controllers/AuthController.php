<?php

namespace Plugins\WechatLogin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Resources\AuthResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Plugins\WechatLogin\Http\Requests\BindWechatRequest;
use Plugins\WechatLogin\Http\Requests\GetAuthUrlRequest;
use Plugins\WechatLogin\Http\Requests\GetJsapiConfigRequest;
use Plugins\WechatLogin\Http\Requests\GetQrCodeLoginUrlRequest;
use Plugins\WechatLogin\Http\Requests\OAuthCallbackRequest;
use Plugins\WechatLogin\Models\UserWechat;
use Plugins\WechatLogin\Services\WechatAuthService;

class AuthController extends Controller
{
    protected WechatAuthService $authService;

    public function __construct(WechatAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getAuthUrl(GetAuthUrlRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $scope = $data['scope'] ?? 'snsapi_userinfo';
            $scopeMap = [
                'snsapi_base' => 'snsapi_base',
                'snsapi_userinfo' => 'snsapi_userinfo',
                'snsapi_user_info' => 'snsapi_userinfo',
                'snsapi_login' => 'snsapi_login',
            ];
            $scope = $scopeMap[strtolower($scope)] ?? 'snsapi_userinfo';

            $url = $this->authService->getAuthUrl(
                $request->redirect_uri,
                $scope,
                $request->state ?? ''
            );

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => ['url' => $url],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取授权URL失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getQrCodeLoginUrl(GetQrCodeLoginUrlRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $url = $this->authService->getQrCodeLoginUrl(
                $data['redirect_uri'],
                $data['state'] ?? ''
            );

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => ['url' => $url],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取扫码登录URL失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function callback(OAuthCallbackRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->authService->oauthLogin($data['code']);
            if (! $result) {
                return response()->json([
                    'code' => 400,
                    'message' => 'OAuth登录失败',
                    'data' => null,
                ], 400);
            }

            $response = new AuthResource($result['token_data']);
            $responseData = $response->toArray($request);
            $responseData['wechat_info'] = $result['wechat_info'];

            $jsonResponse = response()->json([
                'code' => 200,
                'message' => '登录成功',
                'data' => $responseData,
            ], 200);
            if (isset($result['token_data']['cookies'])) {
                foreach ($result['token_data']['cookies'] as $cookie) {
                    $jsonResponse->withCookie($cookie);
                }
            }

            return $jsonResponse;
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'OAuth登录异常: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function qrCodeCallback(OAuthCallbackRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->authService->qrCodeLogin($data['code']);
            if (! $result) {
                return response()->json([
                    'code' => 400,
                    'message' => '扫码登录失败',
                    'data' => null,
                ], 400);
            }

            $response = new AuthResource($result['token_data']);
            $responseData = $response->toArray($request);
            $responseData['wechat_info'] = $result['wechat_info'];

            $jsonResponse = response()->json([
                'code' => 200,
                'message' => '登录成功',
                'data' => $responseData,
            ], 200);
            if (isset($result['token_data']['cookies'])) {
                foreach ($result['token_data']['cookies'] as $cookie) {
                    $jsonResponse->withCookie($cookie);
                }
            }

            return $jsonResponse;
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '扫码登录异常: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function bind(BindWechatRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
                'data' => null,
            ], 401);
        }

        try {
            $result = $this->authService->bindWechat($user->id, $data['code']);
            if (! $result) {
                return response()->json([
                    'code' => 400,
                    'message' => '绑定失败，该微信账号可能已被其他用户绑定',
                    'data' => null,
                ], 400);
            }

            return response()->json([
                'code' => 200,
                'message' => '绑定成功',
                'data' => null,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '绑定异常: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function unbind(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
                'data' => null,
            ], 401);
        }

        try {
            $result = $this->authService->unbindWechat($user->id);
            if (! $result) {
                return response()->json([
                    'code' => 400,
                    'message' => '解绑失败',
                    'data' => null,
                ], 400);
            }

            return response()->json([
                'code' => 200,
                'message' => '解绑成功',
                'data' => null,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '解绑异常: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getJsapiConfig(GetJsapiConfigRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $config = $this->authService->getJsapiConfig($data['url']);
            if (empty($config)) {
                return response()->json([
                    'code' => 400,
                    'message' => '获取JSAPI配置失败',
                    'data' => null,
                ], 400);
            }

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $config,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取JSAPI配置异常: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function checkBindStatus(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
                'data' => null,
            ], 401);
        }

        try {
            $wechatUser = UserWechat::where('user_id', $user->id)->first();

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'is_bound' => $wechatUser !== null,
                    'wechat_info' => $wechatUser ? [
                        'nickname' => $wechatUser->nickname,
                        'avatar' => $wechatUser->avatar,
                        'bound_at' => $wechatUser->created_at,
                    ] : null,
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '检查绑定状态异常: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
