<?php

namespace Plugins\HhhkOss\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\HhhkOss\Services\OssService;

class OssController extends Controller
{
    protected OssService $ossService;

    public function __construct(OssService $ossService)
    {
        $this->ossService = $ossService;
    }

    /**
     * 上传文件
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // 最大10MB
                'path' => 'nullable|string'
            ]);

            $file = $request->file('file');
            $path = $request->input('path', 'uploads/' . date('Y/m/d'));

            // 生成OSS键
            $ossKey = $path . '/' . uniqid() . '_' . $file->getClientOriginalName();

            // 模拟上传（实际项目中需要实现真正的OSS上传）
            $result = $this->ossService->uploadFile($file->getPathname(), $ossKey);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'url' => $this->ossService->getFileUrl($ossKey),
                        'key' => $ossKey,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ],
                    'message' => '文件上传成功'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '文件上传失败'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '文件上传异常: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 删除文件
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'key' => 'required|string'
            ]);

            $ossKey = $request->input('key');

            $result = $this->ossService->deleteFile($ossKey);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => '文件删除成功'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '文件删除失败'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '文件删除异常: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取文件URL
     */
    public function getUrl(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'key' => 'required|string',
                'expire' => 'nullable|integer|min:1|max:604800' // 最大7天
            ]);

            $ossKey = $request->input('key');
            $expire = $request->input('expire', 3600); // 默认1小时

            $url = $this->ossService->getFileUrl($ossKey, $expire);

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $url,
                    'expire' => $expire
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取URL异常: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取OSS配置状态
     */
    public function getConfig(): JsonResponse
    {
        try {
            $config = $this->ossService->getConfig();

            return response()->json([
                'success' => true,
                'data' => [
                    'configured' => $this->ossService->isConfigured(),
                    'config' => [
                        'access_key_id' => !empty($config['OSS_ACCESS_KEY_ID']) ? substr($config['OSS_ACCESS_KEY_ID'], 0, 8) . '****' : '',
                        'bucket' => $config['OSS_BUCKET'] ?? '',
                        'endpoint' => $config['OSS_ENDPOINT'] ?? '',
                        'region' => $config['OSS_REGION'] ?? '',
                        'is_cname' => $config['OSS_IS_CNAME'] ?? false,
                        'ssl' => $config['OSS_SSL'] ?? true,
                        'timeout' => $config['OSS_TIMEOUT'] ?? 60,
                        'cdn_domain' => $config['OSS_CDN_DOMAIN'] ?? '',
                        'cdn_enabled' => $config['OSS_CDN_ENABLED'] ?? false,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取配置异常: ' . $e->getMessage()
            ], 500);
        }
    }
}
