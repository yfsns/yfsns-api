<?php
/**
 * YFSNS社交网络服务系统
 *
 * Copyright (C) 2025 合肥音符信息科技有限公司
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace App\Modules\File\Services;

/**
 * 文件上传服务 - 极简实现
 */
class LocalStorageService
{
    /**
     * 文件上传
     */
    public function upload($file, array $data = [])
    {
        // 使用配置生成路径和文件名
        $path = $this->generatePath($data);
        $filename = $this->generateFilename($file, $data);

        // 存储文件
        $file->storeAs($path, $filename, 'public');

        // 创建数据库记录
        $record = \App\Modules\File\Models\File::create([
            'name' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path . '/' . $filename,
            'storage' => 'local',
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'type' => $data['type'] ?? 'file',
            'module' => $data['module'] ?? 'other',
            'module_id' => $data['module_id'] ?? null,
            'user_id' => $data['user_id'] ?? auth()->id() ?? null,
        ]);

        return [
            'id' => $record->id,
            'name' => $filename,
            'originalName' => $record->original_name,
            'url' => \Storage::disk('public')->url($record->path),
            'size' => $record->size,
            'sizeText' => $record->size . ' B',
            'mimeType' => $record->mime_type,
            'type' => $record->type,
            'storage_type' => $record->storage,
            'createdAt' => $record->created_at->toISOString(),
        ];
    }

    protected function generatePath($data)
    {
        return str_replace(
            ['{module}', '{date}'],
            [$data['module'] ?? 'other', date('Y/m/d')],
            config('upload.patterns.directory')
        );
    }

    protected function generateFilename($file, $data)
    {
        return str_replace(
            ['{user_id}', '{timestamp}', '{random}', '{extension}'],
            [
                $data['user_id'] ?? \Auth::id() ?? 0,
                time(),
                substr(md5(microtime()), 0, 8),
                $file->getClientOriginalExtension()
            ],
            config('upload.patterns.filename')
        );
    }

    /**
     * 批量上传
     */
    public function uploadMultiple(array $files, array $data = [])
    {
        $results = [];

        foreach ($files as $file) {
            $results[] = $this->upload($file, $data);
        }

        return [
            'results' => $results,
            'total_uploaded' => count($results)
        ];
    }

}
