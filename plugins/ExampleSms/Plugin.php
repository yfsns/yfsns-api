<?php

/**
 * 示例短信通道插件
 *
 * 这是一个演示如何开发短信通道插件的示例
 */

namespace Plugins\ExampleSms;

use App\Modules\Sms\Channels\Plugin\SmsChannelPlugin;
use App\Modules\Sms\Channels\Registry\SmsChannelRegistryInterface;

class Plugin extends SmsChannelPlugin
{
    public function getInfo(): array
    {
        return [
            'name' => 'examplesms',
            'version' => '1.0.0',
            'description' => '示例短信通道插件，演示如何开发自定义短信通道',
            'author' => 'YFSNS Team',
            'dependencies' => [],
        ];
    }

    /**
     * 注册短信通道
     */
    public function registerSmsChannels(SmsChannelRegistryInterface $registry): void
    {
        // 注册示例短信通道
        $this->registerChannel($registry, 'example', ExampleSmsChannel::class);

        // 可以注册多个通道
        // $this->registerChannel($registry, 'example_pro', ExampleProSmsChannel::class);
    }

    /**
     * 获取插件提供的通道列表（详细配置）
     */
    public function getSmsChannels(): array
    {
        return [
            [
                'type' => 'example',
                'name' => '示例短信通道',
                'description' => '用于演示的示例短信通道，支持验证码和通知短信',
                'driver_class' => ExampleSmsChannel::class,
                'capabilities' => ['verification', 'notification'],
                'config_fields' => [
                    'api_url' => [
                        'type' => 'text',
                        'label' => 'API地址',
                        'required' => true,
                        'placeholder' => 'https://api.example.com/sms/send',
                        'help' => '短信服务API地址'
                    ],
                    'api_key' => [
                        'type' => 'text',
                        'label' => 'API密钥',
                        'required' => true,
                        'placeholder' => '请输入API密钥',
                        'help' => '从服务商获取的API密钥'
                    ],
                    'sender_id' => [
                        'type' => 'text',
                        'label' => '发送者ID',
                        'required' => true,
                        'placeholder' => 'EXAMPLE',
                        'help' => '短信发送者标识'
                    ]
                ]
            ]
        ];
    }
}
