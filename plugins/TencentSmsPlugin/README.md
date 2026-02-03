# 腾讯云短信插件 (TencentSmsPlugin)

腾讯云短信服务插件，为YFSNS系统提供完整的腾讯云短信功能。

## 功能特性

- ✅ 完整的腾讯云短信SDK集成
- ✅ **独立模板管理**：插件拥有自己的模板数据库表
- ✅ **自动模板同步**：从腾讯云自动同步短信模板
- ✅ **配置界面集成**：支持在后台管理界面展示和管理模板
- ✅ 支持短信模板发送
- ✅ 支持短信签名配置
- ✅ 插件化配置管理
- ✅ 自动依赖安装

## 安装要求

- PHP >= 8.1
- Laravel >= 10.0
- 腾讯云账号及相关权限

## 安装步骤

1. **复制插件到plugins目录**
   ```bash
   cp -r TencentSmsPlugin /path/to/your/project/plugins/
   ```

2. **安装依赖**
   ```bash
   composer require tencentcloud/tencentcloud-sdk-php
   ```

3. **启用插件**
   - 通过插件管理界面启用插件
   - 或使用命令行：`php artisan plugin:enable TencentSmsPlugin`

4. **配置插件**
   - 在插件管理界面配置腾讯云API凭据
   - 设置短信签名和SDK AppId

## 配置说明

| 配置项 | 类型 | 说明 | 示例 |
|--------|------|------|------|
| TENCENT_SMS_SECRET_ID | password | 腾讯云SecretId | AKID5t6A7B8C9D0E1F2G3H4I5J6K7L8M |
| TENCENT_SMS_SECRET_KEY | password | 腾讯云SecretKey | N5O6P7Q8R9S0T1U2V3W4X5Y6Z7A8B9C0 |
| TENCENT_SMS_SDK_APP_ID | text | SDK AppId | 1400123456 |
| TENCENT_SMS_SIGN_NAME | text | 短信签名 | 您的应用名称 |
| TENCENT_SMS_REGION_ID | select | 地域节点 | ap-guangzhou |
| TENCENT_SMS_ENABLED | checkbox | 是否启用 | true |

### 模板管理配置

插件提供专门的模板管理功能：

#### 动态模板列表
- **配置项**: `TENCENT_SMS_TEMPLATES`
- **类型**: 动态表格
- **功能**: 在配置界面展示所有已同步的短信模板

#### 支持的操作
- **模板同步**: 从腾讯云同步最新模板
- **状态管理**: 启用/禁用单个模板
- **状态显示**: 显示审核状态（待审核/已通过/已拒绝）

## 使用方法

### 发送短信

```php
use Plugins\TencentSmsPlugin\Services\TencentSmsService;

$smsService = app(TencentSmsService::class);

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

### 模板管理接口
```
GET  /api/plugins/tencent-sms/templates - 查询短信模板和签名
POST /api/plugins/tencent-sms/templates/sync - 同步腾讯云模板
GET  /api/plugins/tencent-sms/templates/{id} - 获取单个模板详情
```

### 配置管理接口
```
GET  /api/plugins/tencent-sms/config/templates - 获取模板列表（配置界面）
POST /api/plugins/tencent-sms/config/templates/sync - 同步模板（配置界面）
PATCH /api/plugins/tencent-sms/config/templates/{id}/status - 更新模板状态
```

### 测试接口
```
POST /api/plugins/tencent-sms/test/send - 发送测试短信
POST /api/plugins/tencent-sms/test/connection - 测试连接
```

## 注意事项

1. **权限配置**: 确保腾讯云账号有短信服务的相关权限
2. **签名审核**: 短信签名需要在腾讯云控制台审核通过
3. **模板审核**: 短信模板需要在腾讯云控制台审核通过
4. **AppId配置**: 确保SDK AppId配置正确
5. **余额充足**: 确保腾讯云账号有足够的短信余额
6. **模板同步**: 首次使用需要同步模板，之后可定期同步以获取最新模板
7. **模板状态**: 本地模板状态独立于腾讯云审核状态，可根据需要启用/禁用

## 故障排除

### 常见问题

1. **SecretId/SecretKey权限不足**
   - 检查腾讯云账号是否有短信服务的权限
   - 确认密钥没有过期

2. **签名或模板未审核通过**
   - 在腾讯云短信控制台检查签名和模板状态
   - 等待审核通过后再使用

3. **SDK AppId配置错误**
   - 确认SDK AppId是从腾讯云短信控制台获取的正确值
   - 检查AppId是否与签名和模板匹配

4. **地域配置错误**
   - 确认地域ID配置正确
   - 短信服务通常使用广州(ap-guangzhou)等主要地域

## 更新日志

### v1.0.1
- ✨ 新增独立模板管理功能
- ✨ 支持从腾讯云自动同步短信模板
- ✨ 添加配置界面模板列表展示
- ✨ 支持模板启用/禁用状态管理
- 🔧 优化插件架构，支持动态配置数据

### v1.0.0
- 初始版本发布
- 支持基本的短信发送功能
- 支持模板参数配置

## 许可证

本插件遵循Apache 2.0许可证。
