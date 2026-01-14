# 短信模块重构说明

## 📋 概述

短信模块已重构为支持多通道的标准架构，支持内置短信通道和插件扩展通道，前端对接更加标准化。

## 架构设计

### 核心组件

1. **通道注册器** (`SmsChannelRegistry`)
   - 管理所有可用的短信通道
   - 支持内置通道和插件通道的统一注册

2. **标准化通道接口** (`SmsChannelInterface`)
   - 统一的通道接口定义
   - 标准化的配置验证和测试方法

3. **配置管理器** (`SmsConfigManager`)
   - 通道配置的统一管理
   - 配置验证和缓存管理

4. **统一短信服务** (`SmsService`)
   - 统一的短信发送入口
   - 支持按通道类型发送

5. **插件桥接器** (`PluginChannelBridge`)
   - 插件通道的注册和管理
   - 插件通道的生命周期管理

## 📁 目录结构

```
app/Modules/Sms/
├── Channels/                    # 通道相关
│   ├── Registry/                # 通道注册器
│   ├── BuiltIn/                 # 内置通道实现
│   └── Plugin/                  # 插件通道桥接
├── Config/                      # 配置管理
├── Contracts/                   # 接口定义
├── Services/                    # 服务层
├── Controllers/                 # 控制器
└── Models/                      # 数据模型
```

## 使用方法

### 前端发送短信

```javascript
// 获取可用通道列表
GET /api/v1/sms/channels

// 发送短信（自动选择默认通道）
POST /api/v1/sms/send
{
    "phone": "13800138000",
    "template_code": "verification_code",
    "data": {"code": "123456"}
}

// 发送短信（指定通道）
POST /api/v1/sms/send
{
    "phone": "13800138000",
    "template_code": "verification_code",
    "data": {"code": "123456"},
    "channel_type": "aliyun"
}

// 发送通知短信
POST /api/v1/sms/send-notification
{
    "phone": "13800138000",
    "title": "系统通知",
    "content": "您的订单已发货"
}
```

### 管理后台配置

```javascript
// 获取通道列表和状态
GET /api/admin/sms/channels

// 保存通道配置
POST /api/admin/sms/channel/config
{
    "channel_type": "aliyun",
    "name": "阿里云短信",
    "config": {
        "access_key_id": "xxx",
        "access_key_secret": "xxx",
        "sign_name": "测试签名",
        "region_id": "cn-hangzhou"
    }
}

// 测试通道配置
POST /api/admin/sms/channel/test
{
    "channel_type": "aliyun",
    "config": {...}  // 可选，不传则使用已保存配置
}

// 启用/禁用通道
POST /api/admin/sms/{id}/enable
POST /api/admin/sms/{id}/disable
```

## 开发新通道

### 内置通道开发

1. 在 `Channels/BuiltIn/` 下创建通道类
2. 实现 `SmsChannelInterface` 接口
3. 在 `SmsServiceProvider` 中注册通道

```php
// 示例：创建新内置通道
class MySmsChannel implements SmsChannelInterface
{
    public function getChannelType(): string
    {
        return 'my_channel';
    }

    // 实现其他接口方法...
}

// 在 SmsServiceProvider 中注册
$registry->registerChannel('my_channel', MySmsChannel::class);
```

### 插件通道开发

1. 创建插件目录 `plugins/MySmsPlugin/`
2. 创建插件主类继承 `SmsChannelPlugin`
3. 创建通道实现类

```php
// plugins/MySmsPlugin/Plugin.php
class Plugin extends SmsChannelPlugin
{
    public function registerSmsChannels(SmsChannelRegistryInterface $registry): void
    {
        $this->registerChannel($registry, 'my_plugin_channel', MySmsChannel::class);
    }
}

// plugins/MySmsPlugin/MySmsChannel.php
class MySmsChannel implements SmsChannelInterface
{
    // 实现通道接口...
}
```

## 🔄 向后兼容

- 原有的 `SmsServiceImpl` 接口保持兼容
- 数据库结构无需修改
- 现有的模板和配置继续有效
- API接口保持向后兼容

## 迁移指南

### 现有代码更新

1. **控制器更新**
   ```php
   // 旧代码
   use App\Modules\Sms\Infrastructure\Services\SmsServiceImpl;

   // 新代码
   use App\Modules\Sms\Services\SmsService;
   ```

2. **服务调用更新**
   ```php
   // 旧代码
   $this->smsService->send($phone, $templateCode, $data);

   // 新代码（支持指定通道）
   $this->smsService->send($phone, $templateCode, $data, $channelType);
   ```

### 配置迁移

现有的短信配置会自动适配新的架构，无需手动迁移。

## 🧪 测试

### 通道测试

```bash
# 测试阿里云通道
curl -X POST /api/admin/sms/channel/test \
  -d '{"channel_type": "aliyun"}'

# 测试腾讯云通道
curl -X POST /api/admin/sms/channel/test \
  -d '{"channel_type": "tencent"}'
```

### 发送测试

```bash
# 发送测试短信
curl -X POST /api/admin/sms/test \
  -d '{
    "phone": "13800138000",
    "template_code": "verification_code",
    "template_data": {"code": "123456"}
  }'
```

## 安全考虑

- 通道配置包含敏感信息，已加密存储
- API密钥通过环境变量管理
- 插件通道需要验证后才能使用
- 支持IP白名单和频率限制

## 📚 相关文档

- [插件系统开发指南](../PluginSystem/README.md)
- [API文档](../../docs/api/)
- [数据库迁移文档](../Database/Migrations/)

---

## 总结

新的短信架构具有以下优势：

1. **标准化**: 统一的接口和配置格式
2. **可扩展性**: 支持插件无缝扩展新通道
3. **易维护**: 模块化设计，职责分离
4. **高可用**: 支持多通道 failover
5. **向后兼容**: 无缝升级现有系统
