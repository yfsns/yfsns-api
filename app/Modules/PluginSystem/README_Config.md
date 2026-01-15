# 插件配置规范文档

## 概述

本文档定义了插件系统的统一配置规范，所有插件必须遵循此规范来定义和管理配置项。

## 配置文件的结构

### 文件位置
插件配置文件必须放置在插件目录下的 `config.json` 文件中：
```
plugins/{PluginName}/config.json
```

### 配置结构

```json
{
  "version": "1.0.0",
  "last_updated": "2025-12-30",
  "description": "插件的简要描述",

  "fields": {
    "FIELD_KEY": {
      "type": "text|password|select|checkbox|number|email|url|textarea",
      "label": "显示标签",
      "description": "字段的详细描述",
      "placeholder": "输入框占位符",
      "required": true,
      "default": "默认值",
      "validation": "验证规则字符串",
      "group": "分组标识符",
      "order": 1,
      "options": [
        {
          "value": "option_value",
          "label": "选项标签"
        }
      ]
    }
  },

  "groups": {
    "group_key": {
      "label": "分组显示名称",
      "description": "分组的详细描述",
      "icon": "分组图标标识符",
      "order": 1
    }
  },

  "values": {
    "FIELD_KEY": "实际配置值"
  }
}
```

## 字段类型定义

### 基础字段类型

| 类型 | 说明 | 示例 |
|------|------|------|
| `text` | 单行文本输入 | `"type": "text"` |
| `password` | 密码输入（显示为星号） | `"type": "password"` |
| `email` | 邮箱输入 | `"type": "email"` |
| `url` | URL输入 | `"type": "url"` |
| `number` | 数字输入 | `"type": "number"` |
| `textarea` | 多行文本输入 | `"type": "textarea"` |
| `checkbox` | 复选框（布尔值） | `"type": "checkbox"` |
| `select` | 下拉选择 | `"type": "select"` |

### Select 类型选项格式

```json
{
  "type": "select",
  "options": [
    {
      "value": "option1",
      "label": "选项一"
    },
    {
      "value": "option2",
      "label": "选项二"
    }
  ]
}
```

## 验证规则

验证规则使用 Laravel 验证规则语法：

```json
{
  "validation": "required|string|min:3|max:255|email"
}
```

常用验证规则：
- `required` - 必填
- `string` - 字符串
- `integer` - 整数
- `numeric` - 数字
- `email` - 邮箱格式
- `url` - URL格式
- `min:value` - 最小值/长度
- `max:value` - 最大值/长度
- `in:value1,value2` - 枚举值

## 配置分组

### 分组定义

```json
{
  "groups": {
    "credentials": {
      "label": "访问凭证",
      "description": "API访问凭证配置",
      "icon": "key",
      "order": 1
    },
    "settings": {
      "label": "基本设置",
      "description": "基本功能设置",
      "icon": "settings",
      "order": 2
    }
  }
}
```

### 常用分组图标

- `key` - 凭证/密钥
- `settings` - 设置
- `database` - 数据库
- `network` - 网络
- `security` - 安全
- `notification` - 通知
- `advanced` - 高级

## 配置管理流程

### 1. 插件安装时
1. 读取插件的 `config.json` 文件
2. 将 `fields` 和 `groups` 信息写入数据库配置表
3. 初始化 `values` 中的默认值

### 2. 运行时配置管理
1. 前端通过API获取配置定义（fields + groups）
2. 用户修改配置后，通过API保存到数据库
3. 插件代码通过配置管理器读取配置值

### 3. 配置更新
- 插件升级时，可选择是否更新配置定义
- 保持用户已修改的值不变

## 配置管理API

### 获取配置定义
```
GET /api/admin/plugins/{pluginName}/config
```

返回配置字段定义和分组信息，用于前端渲染表单。

### 获取配置值
```
GET /api/admin/plugins/{pluginName}/config/values
```

返回当前配置值。

### 更新配置
```
PUT /api/admin/plugins/{pluginName}/config
```

批量更新配置项的值。

### 重置配置
```
POST /api/admin/plugins/{pluginName}/config/reset
```

将所有配置项重置为默认值。

### 卸载配置
```
DELETE /api/admin/plugins/{pluginName}/config
```

删除插件的所有配置项。通常在插件卸载或需要清理配置时使用。

## 插件代码中的配置读取

### PHP代码中读取配置

```php
// 在插件类中
public function getConfig($key, $default = null)
{
    return app(PluginConfigManager::class)->getPluginConfigValue(
        $this->getName(),
        $key,
        $default
    );
}

// 使用示例
$apiKey = $this->getConfig('API_KEY');
$timeout = $this->getConfig('API_TIMEOUT', 30);
```

## 示例配置

### 简单的API配置

```json
{
  "version": "1.0.0",
  "fields": {
    "API_BASE_URL": {
      "type": "url",
      "label": "API基础地址",
      "description": "第三方API的基础URL地址",
      "required": true,
      "default": "https://api.example.com",
      "validation": "required|url",
      "group": "api",
      "order": 1
    },
    "API_KEY": {
      "type": "password",
      "label": "API密钥",
      "description": "API访问密钥",
      "required": true,
      "validation": "required|string|min:10",
      "group": "api",
      "order": 2
    },
    "API_TIMEOUT": {
      "type": "number",
      "label": "请求超时",
      "description": "API请求超时时间（秒）",
      "default": "30",
      "validation": "integer|min:1|max:300",
      "group": "api",
      "order": 3
    }
  },
  "groups": {
    "api": {
      "label": "API配置",
      "description": "第三方API相关配置",
      "icon": "network",
      "order": 1
    }
  },
  "values": {
    "API_BASE_URL": "",
    "API_KEY": "",
    "API_TIMEOUT": 30
  }
}
```

### 复杂配置示例

```json
{
  "version": "1.0.0",
  "fields": {
    "ENABLED": {
      "type": "checkbox",
      "label": "启用功能",
      "description": "是否启用此插件功能",
      "default": "1",
      "group": "general",
      "order": 1
    },
    "NOTIFICATION_TYPE": {
      "type": "select",
      "label": "通知方式",
      "description": "选择通知发送方式",
      "default": "email",
      "options": [
        {"value": "email", "label": "邮件通知"},
        {"value": "webhook", "label": "Webhook"},
        {"value": "both", "label": "邮件+Webhook"}
      ],
      "group": "notification",
      "order": 1
    },
    "WEBHOOK_URL": {
      "type": "url",
      "label": "Webhook地址",
      "description": "接收通知的Webhook URL",
      "validation": "nullable|url",
      "group": "notification",
      "order": 2
    }
  },
  "groups": {
    "general": {
      "label": "通用设置",
      "icon": "settings",
      "order": 1
    },
    "notification": {
      "label": "通知设置",
      "icon": "notification",
      "order": 2
    }
  }
}
```

## 迁移指南

### 从旧配置格式迁移

如果插件使用旧的配置格式，需要迁移到新规范：

1. 将配置定义转换为 `config.json` 格式
2. 更新插件代码中的配置读取方式
3. 测试配置功能是否正常

### 严格规范要求

**重要提醒：** 系统不会自动兼容不符合规范的配置格式。插件开发者必须严格按照本规范编写配置，否则将无法正常注册和使用。

-  只支持规范中定义的字段类型
-  validation 必须是字符串格式
-  配置文件必须完全符合 JSON Schema
-  不支持类型别名（如 boolean → checkbox）
-  不支持对象格式的验证规则

### 向后兼容性

新规范完全兼容现有的配置管理API，不会影响现有功能。

## 配置验证

### 验证规则

系统会对配置文件进行严格的格式验证：

1. **JSON格式验证**：配置文件必须是有效的JSON
2. **必需字段验证**：每个字段必须包含 `type` 和 `label`
3. **类型验证**：只接受规范中定义的字段类型
4. **结构验证**：select类型必须包含options数组
5. **格式验证**：validation必须是字符串格式

### 常见验证错误

```json
//  错误：使用不支持的类型
{"type": "boolean"} // 应该使用 "checkbox"

//  错误：validation使用对象格式
{"validation": {"min": 1, "max": 100}} // 应该使用 "integer|min:1|max:100"

//  错误：select类型缺少options
{"type": "select"} // 需要添加 "options": [...]
```

## 最佳实践

1. **分组合理**：将相关配置项放在同一分组
2. **验证严格**：为关键配置项设置适当的验证规则
3. **默认值**：为所有配置项提供合理的默认值
4. **文档清晰**：为复杂配置项提供详细的描述
5. **安全性**：敏感信息使用 `password` 类型
6. **严格规范**：严格按照规范编写配置，不要使用扩展或别名

## 版本控制

配置规范版本遵循语义化版本：
- 主版本：破坏性变更
- 次版本：新增功能
- 修订版本：错误修复

当前版本：1.0.0

## API 接口

### 插件管理接口

#### 安装插件
```
POST /api/admin/plugins/{pluginName}/install
```

安装指定的插件，包括创建安装记录和注册配置项。

**功能特性**：
- 首次安装：创建新的安装记录
- 重新安装：如果插件之前被卸载，可以重新安装（不会创建重复记录）
- 自动注册配置：从 `config.json` 读取并注册配置项
- 状态更新：将 `installed` 设为 `true`，清除 `uninstalled_at`

#### 卸载插件
```
DELETE /api/admin/plugins/{pluginName}
```

卸载指定的插件，删除所有配置项并标记为未安装状态。

**注意**：此操作会同时执行：
- 删除所有配置项
- 将 `installed` 字段设为 `false`
- 将 `enabled` 字段设为 `false`
- 设置 `uninstalled_at` 时间戳

#### 启用插件
```
POST /api/admin/plugins/{pluginName}/enable
```

启用指定的插件。

#### 禁用插件
```
POST /api/admin/plugins/{pluginName}/disable
```

禁用指定的插件。

#### 获取插件列表
```
GET /api/admin/plugins
```

获取所有插件的列表信息。

#### 插件安全检查
```
GET /api/admin/plugins/{pluginName}/security-check
```

执行插件的安全检查，包括文件完整性、代码安全和权限检查。

### 配置管理接口

#### 获取配置
```
GET /api/admin/plugins/{pluginName}/config
```

获取插件的所有配置项。

#### 更新配置
```
PUT /api/admin/plugins/{pluginName}/config
```

批量更新插件的配置项值。
