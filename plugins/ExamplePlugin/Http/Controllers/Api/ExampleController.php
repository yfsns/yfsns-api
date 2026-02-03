<?php

namespace Plugins\ExamplePlugin\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * 示例插件控制器
 *
 * 演示如何在插件中创建安全的API控制器
 */
class ExampleController extends Controller
{
    /**
     * 获取示例列表
     */
    public function index(Request $request)
    {
        // 示例数据
        $examples = [
            [
                'id' => 1,
                'name' => '示例项目1',
                'description' => '这是第一个示例项目',
                'created_at' => now()->toISOString(),
            ],
            [
                'id' => 2,
                'name' => '示例项目2',
                'description' => '这是第二个示例项目',
                'created_at' => now()->toISOString(),
            ],
        ];

        return response()->json([
            'code' => 200,
            'message' => '获取示例列表成功',
            'data' => $examples,
        ], 200);
    }

    /**
     * 获取单个示例
     */
    public function show(Request $request, string $example)
    {
        // 模拟根据ID获取数据
        $exampleData = [
            'id' => $example,
            'name' => "示例项目{$example}",
            'description' => "这是示例项目{$example}的详细信息",
            'content' => '这里是示例内容...',
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        return response()->json([
            'code' => 200,
            'message' => '获取示例详情成功',
            'data' => $exampleData,
        ], 200);
    }

    /**
     * 创建示例
     */
    public function store(Request $request)
    {
        // 验证输入数据
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // 模拟创建数据
        $newExample = [
            'id' => rand(1000, 9999),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'created_at' => now()->toISOString(),
        ];

        return response()->json([
            'code' => 201,
            'message' => '创建示例成功',
            'data' => $newExample,
        ], 201);
    }

    /**
     * 更新示例
     */
    public function update(Request $request, string $example)
    {
        // 验证输入数据
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // 模拟更新数据
        $updatedExample = [
            'id' => $example,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'updated_at' => now()->toISOString(),
        ];

        return response()->json([
            'code' => 200,
            'message' => '更新示例成功',
            'data' => $updatedExample,
        ], 200);
    }

    /**
     * 删除示例
     */
    public function destroy(Request $request, string $example)
    {
        // 模拟删除操作
        return response()->json([
            'code' => 200,
            'message' => '删除示例成功',
            'data' => null,
        ], 200);
    }

    /**
     * 获取统计信息（管理员功能）
     */
    public function stats(Request $request)
    {
        // 模拟统计数据
        $stats = [
            'total_examples' => 42,
            'active_examples' => 38,
            'total_views' => 1250,
            'last_updated' => now()->toISOString(),
        ];

        return response()->json([
            'code' => 200,
            'message' => '获取统计信息成功',
            'data' => $stats,
        ], 200);
    }

    /**
     * 更新插件配置（管理员功能）
     */
    public function updateConfig(Request $request)
    {
        // 验证配置数据
        $validated = $request->validate([
            'default_items_per_page' => 'nullable|integer|min:1|max:100',
            'enable_cache' => 'nullable|boolean',
            'cache_ttl' => 'nullable|integer|min:60|max:3600',
        ]);

        // 这里可以保存配置到数据库或配置文件
        // 暂时只返回成功响应

        return response()->json([
            'code' => 200,
            'message' => '配置更新成功',
            'data' => $validated,
        ], 200);
    }
}
