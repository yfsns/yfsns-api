# 定位服务插件 (Location Plugin)

完整的定位服务系统，支持多种地图服务提供商的逆地理编码、地理编码、IP定位等功能。

## 🚀 功能特性

- ✅ **多驱动支持**: 腾讯地图、高德地图、百度地图
- ✅ **智能缓存**: 提高响应速度，减少API调用
- ✅ **故障转移**: 自动切换到其他可用驱动
- ✅ **统一接口**: 前端无需关心具体实现
- ✅ **插件化架构**: 支持独立部署和升级

## 📦 安装说明

### 1. 复制插件文件
将整个 `plugins/Location/` 目录上传到项目的 `plugins/Location/` 目录下。

### 2. 安装插件
通过管理后台或API安装插件：

```bash
# API方式安装
POST /api/admin/plugins/location/install

# 启用插件
POST /api/admin/plugins/location/enable
```

### 3. 配置腾讯地图API密钥

#### 获取API密钥
1. 访问 [腾讯地图开放平台](https://lbs.qq.com/)
2. 注册账号并创建应用
3. 申请WebService API密钥

#### 配置密钥
在插件配置中设置 `TENCENT_MAP_KEY`：

```json
{
  "TENCENT_MAP_KEY": "您的腾讯地图API密钥"
}
```

## 🔧 API接口

### 逆地理编码（坐标转地址）
```
GET /api/v1/location/reverse?lat=39.9042&lng=116.4074&driver=tencent
```

**参数说明:**
- `lat`: 纬度 (必需)
- `lng`: 经度 (必需)
- `driver`: 驱动名称 (可选，默认使用配置的驱动)

**响应示例:**
```json
{
  "success": true,
  "driver": "tencent",
  "country": "中国",
  "province": "北京市",
  "city": "北京市",
  "district": "东城区",
  "formattedAddress": "北京市东城区正义路",
  "latitude": 39.9042,
  "longitude": 116.4074,
  "pois": [
    {
      "id": "1355963025687984233",
      "name": "北京市政府(旧址)",
      "address": "北京市东城区正义路2号"
    }
  ]
}
```

### 地理编码（地址转坐标）
```
GET /api/v1/location/geocode?address=北京市朝阳区&driver=tencent
```

### IP定位
```
GET /api/v1/location/ip?ip=8.8.8.8&driver=tencent
```

### 获取可用驱动
```
GET /api/v1/location/drivers
```

**响应示例:**
```json
{
  "drivers": ["tencent", "amap", "baidu"],
  "default": "tencent"
}
```

## 🧪 测试验证

### 测试腾讯地图API
```bash
# 测试逆地理编码（北京天安门坐标）
curl "http://your-domain.com/api/v1/location/reverse?lat=39.9042&lng=116.4074"

# 预期返回北京市东城区的地址信息
```

### 验证配置生效
```bash
# 检查插件状态
GET /api/admin/plugins/location/config

# 查看API密钥是否正确配置
```

## ⚙️ 配置说明

### 基础配置
- `LOCATION_DEFAULT_DRIVER`: 默认使用的定位驱动
- `LOCATION_CACHE_ENABLED`: 是否启用缓存
- `LOCATION_CACHE_TTL`: 缓存时间（分钟）

### 腾讯地图配置
- `TENCENT_MAP_ENABLED`: 是否启用腾讯地图
- `TENCENT_MAP_KEY`: 腾讯地图API密钥
- `TENCENT_MAP_TIMEOUT`: 请求超时时间（秒）

### 高德地图配置
- `AMAP_ENABLED`: 是否启用高德地图
- `AMAP_KEY`: 高德地图API密钥
- `AMAP_SECRET`: 高德地图数字签名

### 百度地图配置
- `BAIDU_MAP_ENABLED`: 是否启用百度地图
- `BAIDU_MAP_AK`: 百度地图AK

## 🔍 故障排除

### API调用失败
1. 检查API密钥是否正确配置
2. 确认密钥是否有足够的调用额度
3. 查看API响应中的错误信息

### 插件无法加载
1. 确认插件文件完整上传
2. 检查插件目录权限
3. 查看Laravel日志中的错误信息

### 缓存问题
```bash
# 清除定位缓存
POST /api/v1/location/cache/clear
```

## 📚 技术架构

### 插件结构
```
plugins/Location/
├── Plugin.php                 # 主插件类
├── config.json               # 插件配置
├── Providers/
│   └── LocationServiceProvider.php
├── Services/
│   ├── LocationManager.php    # 驱动管理器
│   └── LocationService.php    # 业务服务
├── Controllers/               # API控制器
├── Drivers/                   # 具体驱动实现
│   ├── TencentDriver.php
│   ├── AmapDriver.php
│   └── BaiduDriver.php
├── Routes/api.php            # 路由定义
└── Http/Requests/            # 请求验证
```

### 设计模式
- **策略模式**: 不同地图服务商作为可替换的策略
- **适配器模式**: 统一不同API的接口差异
- **工厂模式**: 动态创建驱动实例
- **门面模式**: 提供简洁的统一接口

## 🔄 更新日志

### v1.0.0
- 初始版本发布
- 支持腾讯地图、高德地图、百度地图
- 实现逆地理编码、地理编码、IP定位
- 支持缓存和故障转移

## 📞 支持

如有问题，请查看：
1. [腾讯地图开放平台文档](https://lbs.qq.com/webservice_v1/guide-geocoder.html)
2. 项目GitHub Issues
3. 技术支持邮箱