# YFSNS 社交网络服务系统（后端）

YFSNS-API 是一个基于 **Laravel 12 + PHP 8.4** 的社交网络服务系统后端，提供用户认证、动态（帖子）、评论、点赞、关注、话题、通知等完整社交能力，面向前后端分离的 API 场景。

2026.1.14首次发布v1.0.0版本
未来开发规划：
vip会员管理模块
用户权限管理模块
订单模块
钱包模块

## 快速开始

```bash
#安装依赖：
composer install
或 composer install --no-dev --optimize-autoloader

cp .env.example .env
php artisan key:generate

# 配置好 .env 中的数据库后：
php artisan migrate --seed

#创建软链接：
php artisan storage:link

php artisan serve
```

##访问后台：http://localhost:8000/admin  
管理员登录：admin/password

官网：http://www.yfsns.cn

## 联系我们

<div align="center">
  <img src="https://foruda.gitee.com/images/1768383496175566831/a990e049_14688257.jpeg" alt="微信二维码" width="200" height="200" />
  <p><strong>微信扫码联系</strong></p>
</div>


## 主要特性

- 统一认证：用户名 / 邮箱 / 手机号登录，基于 Sanctum 的 Token 认证。
- 内容系统：动态发布、转发、图片/视频附件、话题、地理位置。
- 社交关系：关注 / 粉丝、推荐用户、推荐话题。
- 互动通知：点赞、评论、回复、@ 提及、登录提醒等多种通知。

## 技术栈

- laravel 12.44
- PHP ≥ 8.4  
- MySQL ≥ 8.0  
- Redis ≥ 6.0  
- Composer

## 说明

本仓库为服务端代码

## 免责声明
开源协议：apache2.0 ，友好的商业开源协议，无论你是个人还是企业，都可以永久免费使用本系统构建你的网站或平台（只需要保留版权即可），请注意合法使用本系统，遵守相关法律法规。
本软件按“原样”提供，不提供任何明示或暗示的担保。使用本软件所造成的任何问题、损失或风险由使用者自行承担，开发团队及贡献者不承担任何法律责任。