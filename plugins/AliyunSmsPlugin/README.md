# 阿里云短信插件 (AliyunSmsPlugin)

阿里云短信服务插件，为YFSNS系统提供完整的阿里云短信功能。

## 功能特性

- ✅ 完整的阿里云短信SDK集成
- ✅ 支持短信模板发送
- ✅ 支持短信签名查询
- ✅ 支持短信模板查询
- ✅ 插件化配置管理
- ✅ 自动依赖安装

## 安装要求

- PHP >= 8.1
- Laravel >= 10.0
- 阿里云账号及相关权限

## 安装步骤

1. **复制插件到plugins目录**
   ```bash
   cp -r AliyunSmsPlugin /path/to/your/project/plugins/
   ```

2. **安装依赖**
   ```bash
   composer require alibabacloud/client
   ```

3. **启用插件**
   - 通过插件管理界面启用插件
   - 或使用命令行：`php artisan plugin:enable AliyunSmsPlugin`

4. **配置插件**
   - 在插件管理界面配置阿里云API凭据
   - 设置短信签名和地域信息

## 配置说明

| 配置项 | 类型 | 说明 | 示例 |
|--------|------|------|------|
| ALIYUN_SMS_ACCESS_KEY_ID | password | 阿里云AccessKey ID | LTAI5t6A7B8C9D0E1F2G3H4I |
| ALIYUN_SMS_ACCESS_KEY_SECRET | password | 阿里云AccessKey Secret | J5K6L7M8N9O0P1Q2R3S4T5U6V7W8X9Y0Z |
| ALIYUN_SMS_SIGN_NAME | text | 短信签名 | 您的应用名称 |
| ALIYUN_SMS_REGION_ID | select | 地域节点 | cn-hangzhou |
| ALIYUN_SMS_ENABLED | checkbox | 是否启用 | true |

## 使用方法

### 发送短信

```php
use Plugins\AliyunSmsPlugin\Services\AliyunSmsService;

$smsService = app(AliyunSmsService::class);

// 发送验证码短信
$result = $smsService->send('13800138000', 'verification_code', [
    'code' => '123456',
    'expire' => '10'
]);

if ($result['success']) {
    echo '短信发送成功';
} else {
    echo '短信发送失败：' . $result['message'];
}
```

### 查询模板和签名

```php
$templates = $smsService->getTemplates();
print_r($templates);
```

## API接口

插件启用后提供以下API接口：

```
GET /api/plugins/aliyun-sms/templates - 查询短信模板和签名
```

## 注意事项

1. **权限配置**: 确保阿里云账号有短信服务的相关权限
2. **签名审核**: 短信签名需要在阿里云控制台审核通过
3. **模板审核**: 短信模板需要在阿里云控制台审核通过
4. **余额充足**: 确保阿里云账号有足够的短信余额

## 故障排除

### 常见问题

1. **AccessKey权限不足**
   - 检查阿里云账号是否有短信服务的权限
   - 确认AccessKey没有过期

2. **签名或模板未审核通过**
   - 在阿里云短信控制台检查签名和模板状态
   - 等待审核通过后再使用

3. **地域配置错误**
   - 确认地域ID配置正确
   - 部分地域可能不支持短信服务

## 更新日志

### v1.0.0
- 初始版本发布
- 支持基本的短信发送功能
- 支持模板和签名查询

## 许可证

本插件遵循Apache 2.0许可证。
