<?php

/**
 * 示例云存储插件
 *
 * 这是一个演示如何开发云存储插件的示例
 */

namespace Plugins\ExampleStorage;

use App\Modules\PluginSystem\BasePlugin;

class Plugin extends BasePlugin
{
    protected function initialize(): void
    {
        // 初始化插件
    }

    public function getInfo(): array
    {
        return [
            'name' => 'examplestorage',
            'version' => '1.0.0',
            'description' => '示例云存储插件，演示如何开发自定义云存储通道',
            'author' => 'YFSNS Team',
            'dependencies' => [],
        ];
    }


    /**
     * 获取插件提供的云存储通道列表（详细配置）
     */
    public function getCloudStorageChannels(): array
    {
        return [
            [
                'type' => 'example_cloud',
                'name' => '示例云存储',
                'description' => '用于演示的示例云存储，支持文件上传、删除和CDN加速',
                'driver_class' => \Plugins\Examplestorage\ExampleCloudChannel::class,
                'capabilities' => ['upload', 'delete', 'cdn', 'batch'],
                'config_fields' => [
                    'api_endpoint' => [
                        'type' => 'text',
                        'label' => 'API端点',
                        'required' => true,
                        'placeholder' => 'https://api.example-storage.com/v1',
                        'help' => '云存储服务的API端点地址'
                    ],
                    'access_token' => [
                        'type' => 'password',
                        'label' => '访问令牌',
                        'required' => true,
                        'placeholder' => '请输入访问令牌',
                        'help' => '从服务商获取的访问令牌'
                    ],
                    'bucket' => [
                        'type' => 'text',
                        'label' => '存储桶',
                        'required' => true,
                        'placeholder' => 'my-bucket',
                        'help' => '云存储的存储桶名称'
                    ],
                    'region' => [
                        'type' => 'select',
                        'label' => '地域',
                        'required' => true,
                        'default' => 'us-east-1',
                        'options' => [
                            'us-east-1' => '美国东部1',
                            'us-west-1' => '美国西部1',
                            'eu-west-1' => '欧洲西部1',
                            'ap-southeast-1' => '亚太东南1',
                        ],
                        'help' => '选择云存储服务所在的地域'
                    ],
                    'enable_cdn' => [
                        'type' => 'boolean',
                        'label' => '启用CDN',
                        'required' => false,
                        'default' => true,
                        'help' => '是否启用CDN加速'
                    ]
                ]
            ]
        ];
    }

    /**
     * 上传文件（极简接口）
     */
    public function upload($filePath, $fileName)
    {
        // 模拟上传成功
        return [
            'success' => true,
            'url' => 'https://example-storage.com/files/' . $fileName,
            'path' => 'files/' . $fileName,
            'size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath),
        ];
    }
}
