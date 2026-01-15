<?php

namespace App\Modules\Notification\Database\Seeders;

use App\Modules\Notification\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('开始填充通知模板数据...');

        // 清除现有数据
        NotificationTemplate::query()->delete();

        $templates = [
            // 1. 用户注册成功
            [
                'code' => 'user_register_success',
                'name' => '用户注册成功',
                'category' => 'user',
                'channels' => ['database'],
                'content' => [
                    'database' => '欢迎 {username}，注册成功！',
                ],
                'variables' => ['username'],
                'status' => 1,
                'priority' => 2,
                'remark' => '用户注册成功后的系统通知',
            ],

            // 2. 用户登录成功
            [
                'code' => 'user_login_success',
                'name' => '用户登录成功',
                'category' => 'security',
                'channels' => ['database', 'mail', 'sms'],
                'content' => [
                    'database' => '用户 {username} 于 {login_time} 登录成功，IP地址：{ip}',
                    'mail' => "亲爱的 {username}：\n\n您的账号于 {login_time} 成功登录。\n\n登录IP：{ip}\n登录地点：{location}\n登录设备：{device}\n\n如果这不是您的操作，请立即修改密码并联系客服。\n\n{app_name} 团队",
                    'sms' => '您的账号{username}于{login_time}登录成功。如非本人操作，请及时修改密码。',
                ],
                'variables' => ['username', 'login_time'],
                'sms_template_id' => '2580033', // 腾讯云登录成功通知模板ID
                'status' => 1,
                'priority' => 1,
                'remark' => '用户登录成功后的安全通知',
            ],

            // 3. 验证码短信
            [
                'code' => 'verification_code_sms',
                'name' => '验证码短信',
                'category' => 'security',
                'channels' => ['sms'],
                'content' => [
                    'sms' => '您的验证码是：{code}，有效期{expire}分钟，请勿泄露给他人。',
                ],
                'variables' => ['code', 'expire'],
                'sms_template_id' => '2350344', // 腾讯云验证码模板ID
                'status' => 1,
                'priority' => 3,
                'remark' => '发送验证码的短信通知',
            ],

            // 4. 验证码邮件
            [
                'code' => 'verification_code_email',
                'name' => '验证码邮件',
                'category' => 'security',
                'channels' => ['mail'],
                'content' => [
                    'mail' => "亲爱的用户：\n\n您的验证码是：{code}\n\n有效期：{expire}分钟\n\n请勿泄露给他人。\n\n{app_name} 团队",
                ],
                'variables' => ['code', 'expire'],
                'status' => 1,
                'priority' => 3,
                'remark' => '发送验证码的邮件通知',
            ],

            // 5. 有人给你点赞
            [
                'code' => 'post_liked',
                'name' => '有人给你点赞',
                'category' => 'social',
                'channels' => ['database'],
                'content' => [
                    'database' => '{sender_name} 点赞了你的动态',
                ],
                'variables' => ['sender_name', 'post_id', 'post_content'],
                'status' => 1,
                'priority' => 2,
                'remark' => '用户点赞动态后的通知',
            ],

            // 6. 有人给你评论
            [
                'code' => 'comment_reply',
                'name' => '有人给你评论',
                'category' => 'social',
                'channels' => ['database'],
                'content' => [
                    'database' => '{sender_name} 回复了你的评论：{reply_content}',
                ],
                'variables' => ['sender_name', 'original_comment_id', 'reply_comment_id', 'reply_content'],
                'status' => 1,
                'priority' => 2,
                'remark' => '用户评论回复后的通知',
            ],

            // 7. 有人给你的动态评论
            [
                'code' => 'post_commented',
                'name' => '有人给你的动态评论',
                'category' => 'social',
                'channels' => ['database'],
                'content' => [
                    'database' => '{sender_name} 评论了你的动态：{comment_content}',
                ],
                'variables' => ['sender_name', 'post_id', 'post_content', 'comment_content'],
                'status' => 1,
                'priority' => 2,
                'remark' => '用户给动态评论后的通知',
            ],

            // 8. 有人@你了
            [
                'code' => 'user_mentioned',
                'name' => '有人@你了',
                'category' => 'social',
                'channels' => ['database'],
                'content' => [
                    'database' => '{sender_name} 在动态中提到了你：{post_content}',
                ],
                'variables' => ['sender_name', 'post_id', 'post_content'],
                'status' => 1,
                'priority' => 2,
                'remark' => '用户在动态中@其他用户后的通知',
            ],
        ];

        $createdCount = 0;
        $failedCount = 0;

        foreach ($templates as $template) {
            try {
                NotificationTemplate::create($template);
                $createdCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $this->command->error("✗ 创建模板失败: {$template['name']} ({$template['code']}) - {$e->getMessage()}");
            }
        }

        $this->command->info("通知模板填充完成：新建 {$createdCount} 条，失败 {$failedCount} 条");
        if ($failedCount > 0) {
            $this->command->warn('部分模板创建失败，请检查错误信息');
        }
    }
}
