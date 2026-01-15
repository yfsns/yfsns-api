<?php

namespace App\Modules\System\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PluginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 对于我们的用例，直接从resource对象构建数组
        // 因为resource可能是stdClass而不是Eloquent模型
        $data = [];

        // 如果resource是数组，转换为对象处理
        $resource = is_array($this->resource) ? (object) $this->resource : $this->resource;

        // 获取所有属性
        if (is_object($resource)) {
            $data = get_object_vars($resource);
        }

        // 确保插件名是驼峰格式（前端要求的格式）
        if (isset($data['name'])) {
            $data['name'] = $this->ensureCamelCase($data['name']);
        }

        return $data;
    }

    /**
     * 确保字符串是驼峰格式
     */
    private function ensureCamelCase(string $string): string
    {
        // 如果已经是驼峰格式（首字母大写，后续单词首字母大写），直接返回
        if (preg_match('/^[A-Z][a-zA-Z]*$/', $string)) {
            return $string;
        }

        // 处理中划线格式：content-audit -> ContentAudit
        if (strpos($string, '-') !== false) {
            return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
        }

        // 处理下划线格式：content_audit -> ContentAudit
        if (strpos($string, '_') !== false) {
            return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        }

        // 处理全小写格式：contentaudit -> Contentaudit
        if (strtolower($string) === $string) {
            return ucfirst($string);
        }

        // 其他情况，直接首字母大写
        return ucfirst($string);
    }
}
