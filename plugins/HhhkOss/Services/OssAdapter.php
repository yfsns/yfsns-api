<?php

namespace Plugins\HhhkOss\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\Flysystem\Util;

class OssAdapter extends AbstractAdapter
{
    protected OssService $ossService;

    public function __construct(array $config = [])
    {
        $this->ossService = app(OssService::class);
    }

    /**
     * 写入文件
     */
    public function write($path, $contents, Config $config): bool
    {
        // 简单的文件写入实现
        // 实际项目中需要将$contents写入OSS

        return $this->ossService->uploadFile($contents, $path);
    }

    /**
     * 写入文件流
     */
    public function writeStream($path, $resource, Config $config): bool
    {
        // 从流中读取内容并上传
        $contents = stream_get_contents($resource);

        return $this->write($path, $contents, $config);
    }

    /**
     * 读取文件
     */
    public function read($path): string
    {
        // 简单的文件读取实现
        // 实际项目中需要从OSS下载文件

        throw new FileNotFoundException("OSS文件读取功能暂未实现: {$path}");
    }

    /**
     * 读取文件流
     */
    public function readStream($path)
    {
        throw new FileNotFoundException("OSS文件流读取功能暂未实现: {$path}");
    }

    /**
     * 删除文件
     */
    public function delete($path): bool
    {
        return $this->ossService->deleteFile($path);
    }

    /**
     * 删除目录
     */
    public function deleteDir($dirname): bool
    {
        // 简单的目录删除实现
        return true;
    }

    /**
     * 创建目录
     */
    public function createDir($dirname, Config $config): bool
    {
        // OSS不需要显式创建目录
        return true;
    }

    /**
     * 检查文件是否存在
     */
    public function has($path): bool
    {
        // 简单的文件存在性检查
        // 实际项目中需要检查OSS上的文件

        return false; // 暂时返回false，表示文件不存在
    }

    /**
     * 列出目录内容
     */
    public function listContents($directory = '', $recursive = false): array
    {
        // 返回空数组，暂不支持目录列表
        return [];
    }

    /**
     * 获取文件元数据
     */
    public function getMetadata($path): array
    {
        return [
            'type' => 'file',
            'path' => $path,
            'timestamp' => time(),
            'size' => 0,
        ];
    }

    /**
     * 获取文件大小
     */
    public function getSize($path): array
    {
        return [
            'size' => 0,
        ];
    }

    /**
     * 获取文件修改时间
     */
    public function getMimetype($path): array
    {
        return [
            'mimetype' => 'application/octet-stream',
        ];
    }

    /**
     * 获取文件最后修改时间
     */
    public function getTimestamp($path): array
    {
        return [
            'timestamp' => time(),
        ];
    }

    /**
     * 获取文件可见性
     */
    public function getVisibility($path): array
    {
        return [
            'visibility' => 'public',
        ];
    }

    /**
     * 设置文件可见性
     */
    public function setVisibility($path, $visibility): bool
    {
        // 简单的可见性设置
        return true;
    }

    /**
     * 复制文件
     */
    public function copy($from, $to): bool
    {
        // 简单的文件复制实现
        return true;
    }

    /**
     * 移动文件
     */
    public function rename($from, $to): bool
    {
        // 简单的文件重命名实现
        return true;
    }
}
