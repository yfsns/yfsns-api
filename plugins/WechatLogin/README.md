# 微信登录插件 (WechatLogin)

提供微信OAuth网页授权和扫码登录能力的插件，支持公众号、小程序和开放平台的完整集成。

## 功能特性

- ✅ **网页授权**: 支持微信网页授权登录
- ✅ **扫码登录**: 支持微信扫码登录（需开放平台）
- ✅ **小程序登录**: 支持微信小程序登录
- ✅ **多平台支持**: 同时支持公众号、小程序、开放平台
- ✅ **安全可靠**: 完整的令牌验证和加密传输
- ✅ **可视化配置**: 支持Web界面配置，无需手动编辑

## 安装配置

### 1. 插件配置

**插件支持可视化配置界面，无需手动编辑配置文件！**

#### 可视化配置（推荐）

插件现已支持可视化配置界面，可以通过Web界面进行配置：

1. 登录管理后台
2. 进入插件管理页面
3. 点击"配置"按钮进入WechatLogin插件配置页面
4. 根据分组填写微信相关配置：
   - **通用设置**: 启用的登录方式、调试模式
   - **公众号配置**: AppID、AppSecret、Token、AES Key
   - **小程序配置**: AppID、AppSecret
   - **开放平台配置**: AppID、AppSecret、Token、AES Key

#### 微信配置获取

##### 公众号配置
1. 访问 [微信公众平台](https://mp.weixin.qq.com)
2. 进入"设置与开发" → "基本配置"
3. 获取AppID、AppSecret、设置Token和AES Key

##### 小程序配置
1. 访问 [微信开发者工具](https://developers.weixin.qq.com/miniprogram/dev/devtools/stable.html)
2. 进入"开发" → "开发设置"
3. 获取AppID和AppSecret

##### 开放平台配置
1. 访问 [微信开放平台](https://open.weixin.qq.com)
2. 进入"管理中心" → "应用详情"
3. 获取AppID、AppSecret、设置Token和AES Key

### 2. 数据库迁移

插件需要创建数据表来存储微信配置信息：

```bash
php artisan migrate
```

### 3. 插件启用

在插件管理页面启用WechatLogin插件即可开始使用。

## API使用

### 获取授权URL

```http
GET /api/v1/wechat/auth-url?type=mp&redirect_url=https://your-domain.com/callback
```

**参数说明：**
- `type`: 平台类型 (mp=公众号, mini=小程序, open=开放平台)
- `redirect_url`: 授权成功后的跳转地址

### 处理回调

```http
GET /api/v1/wechat/callback?code=xxx&state=xxx
```

### 获取用户信息

```http
POST /api/v1/wechat/userinfo
Authorization: Bearer {token}
```

## 配置说明

### 通用设置
- **启用的登录方式**: 选择支持的微信登录类型
- **调试模式**: 启用后会记录详细的日志信息

### 公众号配置
- **AppID**: 微信公众号的唯一标识
- **AppSecret**: 公众号的密钥
- **Token**: 消息校验Token（可选）
- **AES Key**: 消息加解密密钥（可选，用于加密消息）

### 小程序配置
- **AppID**: 小程序的唯一标识
- **AppSecret**: 小程序的密钥

### 开放平台配置
- **AppID**: 开放平台的唯一标识
- **AppSecret**: 开放平台的密钥
- **Token**: 消息校验Token
- **AES Key**: 消息加解密密钥

## 安全注意事项

1. **密钥保护**: AppSecret等敏感信息已加密存储
2. **HTTPS**: 生产环境必须使用HTTPS
3. **域名验证**: 微信平台需要验证服务器域名
4. **IP白名单**: 建议配置IP白名单限制访问

## 故障排除

### 常见问题

#### 授权失败
- 检查AppID和AppSecret是否正确
- 确认回调域名已配置到微信平台
- 检查服务器时间是否准确

#### 消息推送失败
- 确认Token和AES Key配置正确
- 检查服务器网络连接
- 查看插件日志中的错误信息

#### 小程序登录失败
- 确认小程序AppID和AppSecret正确
- 检查小程序端代码是否正确调用API

## 技术支持

如有问题，请查看插件日志或联系开发团队。

## 作者

- **作者**: yfsns
- **版本**: 1.0.0
- **兼容性**: Laravel 10.0+, PHP 8.1+
