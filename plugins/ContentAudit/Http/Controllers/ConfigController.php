<?php

namespace Plugins\ContentAudit\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

use function strlen;

class ConfigController extends Controller
{
    /**
     * 获取插件配置.
     */
    public function get(): JsonResponse
    {
        try {
            $configPath = base_path('plugins/ContentAudit/.env');
            $config = $this->parseEnvFile($configPath);

            // 隐藏敏感信息（Token只显示前几位）
            if (isset($config['AUDIT_API_TOKEN']) && strlen($config['AUDIT_API_TOKEN']) > 10) {
                $config['AUDIT_API_TOKEN'] = substr($config['AUDIT_API_TOKEN'], 0, 10).'...';
            }

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $config,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取配置失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 更新插件配置.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'AUDIT_API_BASE_URL' => 'required|url',
            'AUDIT_API_TOKEN' => 'required|string',
            'AUDIT_API_TIMEOUT' => 'nullable|integer|min:1|max:300',
            'AUDIT_ENABLED' => 'nullable|boolean',
            'AUDIT_ASYNC' => 'nullable|boolean',
            'AUDIT_RETRY_TIMES' => 'nullable|integer|min:0|max:10',
            'AUDIT_RETRY_DELAY' => 'nullable|integer|min:1|max:3600',
            'AUDIT_AUTO_PUBLISH_ON_PASS' => 'nullable|boolean',
            'AUDIT_AUTO_UNPUBLISH_ON_REJECT' => 'nullable|boolean',
        ]);

        try {
            $configPath = base_path('plugins/ContentAudit/.env');
            $examplePath = base_path('plugins/ContentAudit/.env.example');

            // 如果 .env 不存在，从 .env.example 复制
            if (! File::exists($configPath) && File::exists($examplePath)) {
                File::copy($examplePath, $configPath);
            }

            // 读取现有配置
            $existingConfig = $this->parseEnvFile($configPath);

            // 更新配置
            $data = $request->only([
                'AUDIT_API_BASE_URL',
                'AUDIT_API_TOKEN',
                'AUDIT_API_TIMEOUT',
                'AUDIT_ENABLED',
                'AUDIT_ASYNC',
                'AUDIT_RETRY_TIMES',
                'AUDIT_RETRY_DELAY',
                'AUDIT_AUTO_PUBLISH_ON_PASS',
                'AUDIT_AUTO_UNPUBLISH_ON_REJECT',
            ]);

            // 合并配置
            $config = array_merge($existingConfig, array_filter($data, function ($value) {
                return $value !== null;
            }));

            // 写入配置文件
            $this->writeEnvFile($configPath, $config);

            return response()->json([
                'code' => 200,
                'message' => '配置更新成功',
                'data' => null,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '更新配置失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 解析 .env 文件.
     */
    protected function parseEnvFile(string $filePath): array
    {
        $config = [];

        if (! File::exists($filePath)) {
            return $config;
        }

        $lines = File::lines($filePath);
        foreach ($lines as $line) {
            $line = trim($line);

            // 跳过空行和注释
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // 解析 KEY=VALUE 格式
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // 移除引号
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }

                $config[$key] = $value;
            }
        }

        return $config;
    }

    /**
     * 写入 .env 文件.
     */
    protected function writeEnvFile(string $filePath, array $config): void
    {
        $content = "# 内容审核插件配置文件\n";
        $content .= "# 自动生成，请勿手动编辑\n\n";

        // 定义配置项顺序和注释
        $configItems = [
            'AUDIT_API_BASE_URL' => '审核服务API基础地址',
            'AUDIT_API_TOKEN' => '审核服务API Token（Bearer Token）',
            'AUDIT_API_TIMEOUT' => '请求超时时间（秒）',
            'AUDIT_ENABLED' => '是否启用AI审核',
            'AUDIT_ASYNC' => '是否异步审核（推荐）',
            'AUDIT_RETRY_TIMES' => '审核失败重试次数',
            'AUDIT_RETRY_DELAY' => '审核失败重试间隔（秒）',
            'AUDIT_AUTO_PUBLISH_ON_PASS' => '审核通过后是否自动发布',
            'AUDIT_AUTO_UNPUBLISH_ON_REJECT' => '审核拒绝后是否自动下架',
        ];

        foreach ($configItems as $key => $comment) {
            $value = $config[$key] ?? '';
            $content .= "# {$comment}\n";
            $content .= "{$key}={$value}\n\n";
        }

        File::put($filePath, $content);
    }
}
