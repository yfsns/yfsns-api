# HhhkOss 阿里云OSS插件

## 简介

HhhkOss是一个简单的阿里云OSS文件存储插件，为Laravel应用提供OSS文件上传、下载和管理功能。

### 📋 插件配置标准

本插件遵循**插件配置标准规范**，每个插件都必须包含 `config.json` 配置文件。

#### 📁 标准文件结构
```
plugins/hhhkoss/
├── config.json          # 必需：配置模式定义
├── config.values.json   # 自动生成：用户配置值
├── Plugin.php          # 插件主类
└── README.md           # 插件文档
```

#### 核心特性

- ✅ **自动配置加载**: 插件系统自动识别并加载JSON配置文件
- ✅ **前端自动渲染**: 支持动态表单渲染，无需硬编码UI
- ✅ **类型安全验证**: 完整的字段验证和类型检查
- ✅ **分组管理**: 配置项按功能分组，提高用户体验
- ✅ **实时生效**: 配置更改立即生效，支持缓存优化
- ✅ **标准合规**: 遵循插件开发标准规范

## 功能特性

- ✅ 文件上传到阿里云OSS
- ✅ 文件删除
- ✅ 获取文件访问URL
- ✅ Laravel文件系统集成
- ✅ 插件生命周期管理

## 安装配置

### 1. 插件系统自动识别

插件系统会自动检测和加载 `config.json` 配置文件：

```json
{
  "version": "1.0.0",
  "fields": {
    "OSS_ACCESS_KEY_ID": {
      "type": "text",
      "label": "AccessKey ID",
      "required": true
    }
  },
  "values": {
    "OSS_ACCESS_KEY_ID": ""
  }
}
```

当插件初始化时，系统会：
1. 读取 `config.json` 获取字段定义
2. 加载 `config.values.json` 获取用户配置
3. 自动合并配置到插件实例中

### 2. 插件配置

插件提供了可视化的配置界面，支持以下配置项：

#### 访问凭证
- **AccessKey ID**: 阿里云账户的AccessKey ID
- **AccessKey Secret**: 阿里云账户的AccessKey Secret

#### 存储桶设置
- **存储桶名称**: OSS存储桶的名称
- **地域节点**: OSS服务的地域节点（下拉选择）

#### CDN加速（可选）
- **CDN域名**: CDN加速域名
- **启用CDN加速**: 是否启用CDN加速功能

#### 高级设置
- **地域代码**: OSS地域代码
- **使用自定义域名**: 是否使用自定义域名访问
- **启用HTTPS**: 是否使用HTTPS协议
- **请求超时时间**: 请求超时时间（秒）

### 2. 配置界面

通过插件管理界面访问配置：
```
GET /api/admin/plugins/HhhkOss/config
```

前端会根据 `config.json` 中的字段定义自动渲染表单。

### 3. 配置API

#### 获取配置表单结构
```http
GET /api/admin/plugins/HhhkOss/config
```

**响应示例：**
```json
{
  "success": true,
  "data": {
    "schema": {
      "version": "1.0.0",
      "fields": {
        "OSS_ACCESS_KEY_ID": {
          "type": "text",
          "label": "AccessKey ID",
          "required": true,
          "group": "credentials"
        }
      },
      "groups": {
        "credentials": {
          "label": "访问凭证",
          "order": 1
        }
      }
    },
    "values": {
      "OSS_ACCESS_KEY_ID": "",
      "OSS_ACCESS_KEY_SECRET": "",
      // ... 其他配置值
    },
    "groups": {
      "credentials": {
        "label": "访问凭证",
        "description": "阿里云OSS的访问凭证配置",
        "icon": "key",
        "order": 1
      }
    }
  }
}
```

#### 更新配置
```http
PUT /api/admin/plugins/HhhkOss/config
Content-Type: application/json

{
  "OSS_ACCESS_KEY_ID": "your_access_key_id",
  "OSS_ACCESS_KEY_SECRET": "your_access_key_secret",
  "OSS_BUCKET": "your-bucket-name",
  "OSS_ENDPOINT": "oss-cn-hangzhou.aliyuncs.com"
}
```

#### 测试连接
```http
POST /api/admin/plugins/HhhkOss/config/test
```

#### 重置配置
```http
POST /api/admin/plugins/HhhkOss/config/reset
```

## 插件系统配置架构

### 📁 文件结构

```
plugins/hhhkoss/
├── config.json          # 配置模式定义
├── config.values.json   # 用户配置值（自动生成）
└── Plugin.php          # 插件主类
```

### 🔧 配置加载流程

1. **插件初始化** → `BasePlugin::loadConfig()`
2. **检测Schema** → 识别JSON配置文件结构
3. **加载字段定义** → 解析 `config.json`
4. **加载配置值** → 读取 `config.values.json`
5. **合并配置** → 将配置值注入插件实例

### 配置访问方法

```php
// 在插件类中访问配置
class Plugin extends BasePlugin
{
    public function someMethod()
    {
        // 获取配置schema
        $schema = $this->getConfigSchema();

        // 获取配置值
        $values = $this->getConfigValues();

        // 检查是否支持schema配置
        if ($this->hasConfigSchema()) {
            // 处理schema配置
        }
    }
}

// 在服务类中访问配置
class OssService
{
    public function __construct()
    {
        $plugin = app('plugin.manager')->getPlugin('HhhkOss');
        $config = $plugin->getConfigValues();
    }
}
```

## 前端集成示例

### 自动表单渲染

基于配置模式的JSON结构，前端可以自动渲染配置表单：

```javascript
// 获取配置结构和当前值
async function loadConfig() {
  const response = await fetch('/api/admin/plugins/HhhkOss/config');
  const data = await response.json();

  if (data.success) {
    renderConfigForm(data.data);
  }
}

// 自动渲染表单
function renderConfigForm(configData) {
  const { schema, values, groups } = configData;

  // 按分组渲染
  Object.keys(groups).sort((a, b) => groups[a].order - groups[b].order).forEach(groupKey => {
    const group = groups[groupKey];
    const groupFields = Object.entries(schema.fields)
      .filter(([_, field]) => field.group === groupKey)
      .sort((a, b) => (a[1].order || 0) - (b[1].order || 0));

    renderGroup(group, groupFields, values);
  });
}

// 渲染字段组
function renderGroup(group, fields, values) {
  const groupDiv = document.createElement('div');
  groupDiv.className = 'config-group';

  // 组标题
  const title = document.createElement('h3');
  title.textContent = group.label;
  groupDiv.appendChild(title);

  // 渲染字段
  fields.forEach(([fieldName, fieldConfig]) => {
    const fieldDiv = renderField(fieldName, fieldConfig, values[fieldName]);
    groupDiv.appendChild(fieldDiv);
  });

  document.getElementById('config-form').appendChild(groupDiv);
}

// 渲染单个字段
function renderField(name, config, value) {
  const fieldDiv = document.createElement('div');
  fieldDiv.className = 'form-field';

  // 标签
  const label = document.createElement('label');
  label.textContent = config.label + (config.required ? ' *' : '');
  fieldDiv.appendChild(label);

  // 输入控件
  const input = createInput(name, config, value);
  fieldDiv.appendChild(input);

  // 描述
  if (config.description) {
    const desc = document.createElement('small');
    desc.textContent = config.description;
    desc.className = 'field-description';
    fieldDiv.appendChild(desc);
  }

  return fieldDiv;
}

// 创建输入控件
function createInput(name, config, value) {
  let input;

  switch (config.type) {
    case 'select':
      input = document.createElement('select');
      input.name = name;

      if (config.options) {
        config.options.forEach(option => {
          const opt = document.createElement('option');
          opt.value = option.value;
          opt.textContent = option.label;
          opt.selected = option.value === value;
          input.appendChild(opt);
        });
      }
      break;

    case 'boolean':
      input = document.createElement('input');
      input.type = 'checkbox';
      input.name = name;
      input.checked = value || false;
      break;

    case 'number':
      input = document.createElement('input');
      input.type = 'number';
      input.name = name;
      input.value = value || '';
      if (config.validation) {
        if (config.validation.min) input.min = config.validation.min;
        if (config.validation.max) input.max = config.validation.max;
      }
      break;

    case 'password':
      input = document.createElement('input');
      input.type = 'password';
      input.name = name;
      input.value = value || '';
      break;

    case 'text':
    default:
      input = document.createElement('input');
      input.type = 'text';
      input.name = name;
      input.value = value || '';
      input.placeholder = config.placeholder || '';
      break;
  }

  if (config.required) {
    input.required = true;
  }

  if (config.validation && config.validation.max_length) {
    input.maxLength = config.validation.max_length;
  }

  return input;
}

// 提交配置
async function saveConfig(formData) {
  const response = await fetch('/api/admin/plugins/HhhkOss/config', {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(formData)
  });

  const result = await response.json();

  if (result.success) {
    alert('配置保存成功！');
  } else {
    alert('保存失败：' + result.message);
  }
}
```

### 配置界面预览

配置界面将按以下分组显示：

1. **访问凭证**
   - AccessKey ID (文本输入，必填)
   - AccessKey Secret (密码输入，必填)

2. **存储桶设置**
   - 存储桶名称 (文本输入，必填)
   - 地域节点 (下拉选择，必填)

3. **CDN加速** 🌐
   - CDN域名 (文本输入，可选)
   - 启用CDN加速 (复选框，可选)

4. **高级设置**
   - 地域代码 (文本输入，可选)
   - 使用自定义域名 (复选框，可选)
   - 启用HTTPS (复选框，可选)
   - 请求超时时间 (数字输入，可选)

每个字段都有相应的验证规则和用户友好的标签说明。

### 2. 插件安装

通过插件管理系统安装HhhkOss插件：

```bash
# 安装插件
POST /api/admin/plugins/HhhkOss/install

# 启用插件
POST /api/admin/plugins/HhhkOss/enable
```

## API使用

### 文件上传

```http
POST /api/oss/upload
Content-Type: multipart/form-data

file: [文件]
path: uploads/avatar (可选)
```

**响应示例：**
```json
{
  "success": true,
  "data": {
    "url": "https://bucket-name.oss-cn-hangzhou.aliyuncs.com/uploads/avatar/abc123_image.jpg",
    "key": "uploads/avatar/abc123_image.jpg",
    "size": 1024000,
    "mime_type": "image/jpeg"
  },
  "message": "文件上传成功"
}
```

### 文件删除

```http
DELETE /api/oss/delete
Content-Type: application/json

{
  "key": "uploads/avatar/abc123_image.jpg"
}
```

### 获取文件URL

```http
GET /api/oss/url?key=uploads/avatar/abc123_image.jpg&expire=3600
```

## Laravel文件系统使用

插件启用后，可以在Laravel中使用OSS文件系统：

```php
// 在config/filesystems.php中配置
'disks' => [
    'oss' => [
        'driver' => 'oss',
        'bucket' => env('OSS_BUCKET'),
        'endpoint' => env('OSS_ENDPOINT'),
        'access_key' => env('OSS_ACCESS_KEY_ID'),
        'secret_key' => env('OSS_ACCESS_KEY_SECRET'),
    ],
],

// 使用示例
Storage::disk('oss')->put('example.txt', 'Hello World!');
$url = Storage::disk('oss')->url('example.txt');
```

## 开发说明

这是一个超简单的示例实现，实际项目中需要：

1. 集成阿里云OSS SDK
2. 实现完整的文件操作
3. 添加错误处理和重试机制
4. 增加文件分片上传支持
5. 添加图片处理功能

## 作者

- **作者**: hhhk
- **版本**: 1.0.0
- **Laravel**: >=10.0
- **PHP**: >=8.1
