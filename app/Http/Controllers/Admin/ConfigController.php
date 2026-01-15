<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 配置管理控制器
 *
 * 提供声明式配置系统的Web管理界面
 */
class ConfigController extends Controller
{
    protected ConfigRepository $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * 显示配置分组列表
     */
    public function index()
    {
        try {
            $groups = $this->configRepository->getAllGroups();

            return response()->json([
                'code' => 200,
                'message' => '获取配置分组成功',
                'data' => [
                    'groups' => $groups
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取配置分组失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 显示指定分组的配置
     */
    public function show(string $group)
    {
        try {
            $configs = $this->configRepository->getGroup($group);

            // 获取配置文件的声明式架构（如果存在）
            $schema = [];
            if (function_exists('config')) {
                $configData = config($group);
                if (isset($configData['schema'])) {
                    $schema = $configData['schema'];
                }
            }

            return response()->json([
                'code' => 200,
                'message' => '获取配置成功',
                'data' => [
                    'group' => $group,
                    'configs' => $configs,
                    'schema' => $schema
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取配置失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量更新配置
     */
    public function update(Request $request, string $group)
    {
        $validated = $request->validate([
            'configs' => 'required|array',
            'configs.*.value' => 'required',
            'configs.*.type' => 'required|in:string,integer,boolean,float,json',
        ]);

        try {
            foreach ($validated['configs'] as $key => $configData) {
                $fullKey = $group . '.' . $key;
                $this->configRepository->set(
                    $fullKey,
                    $configData['value'],
                    $configData['type'],
                    $group,
                    $configData['description'] ?? ''
                );
            }

            // 清除相关缓存
            Cache::forget('config_group:' . $group);

            return response()->json([
                'code' => 200,
                'message' => '配置更新成功'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '配置更新失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 重置配置为默认值
     */
    public function reset(string $group)
    {
        try {
            // 获取配置文件的声明式架构
            $configData = config($group);
            if (isset($configData['schema'])) {
                $schema = $configData['schema'];

                // 重置为默认值
                foreach ($schema as $key => $definition) {
                    $fullKey = $group . '.' . $key;
                    $this->configRepository->set(
                        $fullKey,
                        $definition['default'] ?? null,
                        $definition['type'] ?? 'string',
                        $group,
                        $definition['description'] ?? ''
                    );
                }

                // 清除缓存
                Cache::forget('config_group:' . $group);

                return response()->json([
                    'code' => 200,
                    'message' => '配置已重置为默认值'
                ]);
            }

            return response()->json([
                'code' => 400,
                'message' => '无法找到配置架构定义'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '重置失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 导出配置
     */
    public function export(string $group)
    {
        try {
            $configs = $this->configRepository->exportGroup($group);

            return response()->json([
                'group' => $group,
                'configs' => $configs,
                'exported_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 导入配置
     */
    public function import(Request $request, string $group)
    {
        $validated = $request->validate([
            'configs' => 'required|array',
        ]);

        try {
            $this->configRepository->importGroup($group, $validated['configs']);

            return response()->json([
                'code' => 200,
                'message' => '配置导入成功'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '配置导入失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
