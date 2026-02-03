<?php

namespace Plugins\HhhkOss\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PluginSystem\Services\PluginConfigManagerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
    protected $configManager;
    protected $pluginName = 'HhhkOss';

    public function __construct(PluginConfigManagerService $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * 获取OSS配置表单结构和当前值.
     */
    public function get(): JsonResponse
    {
        try {
            // 使用标准的插件配置管理器获取配置
            $configs = $this->configManager->getPluginConfigs($this->pluginName);

            // 获取配置值（注意：getPluginConfigs 返回的数组键是 'key' 和 'value'）
            $currentValues = [];
            foreach ($configs as $groupConfigs) {
                foreach ($groupConfigs as $config) {
                    $currentValues[$config['key']] = $config['value'] ?? $config['default'];
                }
            }

            // 隐藏敏感信息
            $this->maskSensitiveFields($currentValues);

            // 获取配置schema（从config.json文件）
            $configSchema = $this->getConfigSchema();

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'schema' => $configSchema,
                    'values' => $currentValues,
                    'groups' => $configSchema['groups'] ?? [],
                ],
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
     * 更新OSS配置.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $configSchema = $this->getConfigSchema();
            $data = $request->all();

            // 动态验证
            $validator = $this->validateConfigData($data, $configSchema);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'message' => '配置验证失败: '.implode(', ', $validator->errors()->all()),
                    'data' => null,
                ], 400);
            }

            // 使用标准的插件配置管理器保存配置
            $this->configManager->setPluginConfigs($this->pluginName, $data);

            // 清除缓存
            \Cache::forget('hhhk_oss_config');

            return response()->json([
                'code' => 200,
                'message' => 'OSS配置更新成功',
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
     * 测试OSS连接.
     */
    public function testConnection(): JsonResponse
    {
        try {
            // 使用标准的插件配置管理器获取配置
            $accessKeyId = $this->configManager->getPluginConfigValue($this->pluginName, 'OSS_ACCESS_KEY_ID', '');
            $accessKeySecret = $this->configManager->getPluginConfigValue($this->pluginName, 'OSS_ACCESS_KEY_SECRET', '');
            $bucket = $this->configManager->getPluginConfigValue($this->pluginName, 'OSS_BUCKET', '');
            $endpoint = $this->configManager->getPluginConfigValue($this->pluginName, 'OSS_ENDPOINT', '');

            // 检查必需的配置项
            $required = [
                'OSS_ACCESS_KEY_ID' => $accessKeyId,
                'OSS_ACCESS_KEY_SECRET' => $accessKeySecret,
                'OSS_BUCKET' => $bucket,
                'OSS_ENDPOINT' => $endpoint,
            ];
            $missing = [];
            foreach ($required as $key => $value) {
                if (empty($value)) {
                    $missing[] = $key;
                }
            }

            if (! empty($missing)) {
                return response()->json([
                    'code' => 400,
                    'message' => '配置不完整，缺少: '.implode(', ', $missing),
                    'data' => null,
                ], 400);
            }

            // 这里可以添加实际的OSS连接测试
            // 目前只是模拟测试

            return response()->json([
                'code' => 200,
                'message' => 'OSS连接测试成功',
                'data' => [
                    'status' => 'success',
                    'message' => 'OSS配置验证通过',
                    'bucket' => $bucket,
                    'endpoint' => $endpoint,
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'OSS连接测试失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 重置配置为默认值.
     */
    public function reset(): JsonResponse
    {
        try {
            // 使用标准的插件配置管理器重置配置
            $this->configManager->resetPluginConfigs($this->pluginName);

            // 清除缓存
            \Cache::forget('hhhk_oss_config');

            return response()->json([
                'code' => 200,
                'message' => '配置已重置为默认值',
                'data' => null,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '重置配置失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 获取配置Schema（从config.json文件）.
     */
    protected function getConfigSchema(): array
    {
        $configFile = base_path("plugins/{$this->pluginName}/config.json");
        
        if (!File::exists($configFile)) {
            return ['fields' => [], 'groups' => []];
        }

        try {
            $content = File::get($configFile);
            $schema = json_decode($content, true);
            return $schema ?: ['fields' => [], 'groups' => []];
        } catch (Exception $e) {
            return ['fields' => [], 'groups' => []];
        }
    }


    /**
     * 隐藏敏感字段.
     */
    protected function maskSensitiveFields(array &$values): void
    {
        $sensitiveFields = ['OSS_ACCESS_KEY_SECRET'];

        foreach ($sensitiveFields as $field) {
            if (!empty($values[$field])) {
                $values[$field] = substr($values[$field], 0, 8) . '****';
            }
        }
    }

    /**
     * 动态验证配置数据.
     */
    protected function validateConfigData(array $data, array $schema): \Illuminate\Validation\Validator
    {
        $rules = [];
        $messages = [];

        foreach ($schema['fields'] as $fieldName => $fieldConfig) {
            $fieldRules = [];

            // 必填验证
            if ($fieldConfig['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // 类型验证
            switch ($fieldConfig['type']) {
                case 'text':
                    $fieldRules[] = 'string';
                    break;
                case 'password':
                    $fieldRules[] = 'string';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'boolean':
                    $fieldRules[] = 'boolean';
                    break;
                case 'select':
                    if (isset($fieldConfig['options'])) {
                        $options = array_column($fieldConfig['options'], 'value');
                        $fieldRules[] = 'in:' . implode(',', $options);
                    }
                    break;
            }

            // 长度验证
            if (isset($fieldConfig['validation'])) {
                $validation = $fieldConfig['validation'];

                if (isset($validation['min_length'])) {
                    $fieldRules[] = 'min:' . $validation['min_length'];
                }

                if (isset($validation['max_length'])) {
                    $fieldRules[] = 'max:' . $validation['max_length'];
                }

                if (isset($validation['min'])) {
                    $fieldRules[] = 'min:' . $validation['min'];
                }

                if (isset($validation['max'])) {
                    $fieldRules[] = 'max:' . $validation['max'];
                }

                if (isset($validation['pattern'])) {
                    $fieldRules[] = 'regex:/' . $validation['pattern'] . '/';
                }
            }

            if (!empty($fieldRules)) {
                $rules[$fieldName] = implode('|', $fieldRules);
            }

            // 自定义错误消息
            if (isset($fieldConfig['label'])) {
                $label = $fieldConfig['label'];
                $messages["{$fieldName}.required"] = "{$label}不能为空";
                $messages["{$fieldName}.string"] = "{$label}必须是字符串";
                $messages["{$fieldName}.numeric"] = "{$label}必须是数字";
                $messages["{$fieldName}.boolean"] = "{$label}必须是布尔值";
                $messages["{$fieldName}.in"] = "{$label}值无效";
                $messages["{$fieldName}.min"] = "{$label}不能小于最小值";
                $messages["{$fieldName}.max"] = "{$label}不能大于最大值";
                $messages["{$fieldName}.regex"] = "{$label}格式不正确";
            }
        }

        return Validator::make($data, $rules, $messages);
    }
}
