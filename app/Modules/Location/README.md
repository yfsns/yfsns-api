# Location 模块

Location 模块负责管理地理位置信息，提供位置数据的规范化存储、统计和查询功能。

## 功能特性

- **位置规范化存储**：避免重复存储相同位置信息
- **地理位置统计**：统计位置使用热度、热门城市等
- **高效地理查询**：支持附近位置搜索、地理范围筛选
- **位置分类管理**：支持餐厅、景点、学校等位置类型
- **向后兼容**：保持与现有 API 的兼容性

## 数据结构

### locations 表

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| latitude | decimal(10,8) | 纬度 |
| longitude | decimal(11,8) | 经度 |
| address | varchar(255) | 完整地址 |
| country | varchar(255) | 国家 |
| province | varchar(255) | 省份 |
| city | varchar(255) | 城市 |
| district | varchar(255) | 区县 |
| place_name | varchar(255) | 具体地点名称 |
| category | varchar(255) | 地点类型 |
| post_count | int | 使用该位置的帖子数量 |
| metadata | json | 扩展信息 |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

## 核心方法

### Location 模型

```php
// 查找或创建位置
$location = Location::findOrCreate([
    'latitude' => 39.9042,
    'longitude' => 116.4074,
    'address' => '北京市朝阳区',
    'country' => '中国',
    'province' => '北京市',
    'city' => '北京市',
    'district' => '朝阳区',
    'place_name' => '天安门',
    'category' => '景点'
]);

// 获取完整地址
echo $location->full_address; // 中国北京市北京市朝阳区天安门

// 获取坐标字符串
echo $location->coordinates; // 39.9042,116.4074
```

### LocationService 服务

```php
$locationService = app(LocationService::class);

// 获取热门位置
$popularLocations = $locationService->getPopularLocations(10);

// 按城市获取位置
$beijingLocations = $locationService->getLocationsByCity('北京');

// 查找附近热门位置
$nearbyLocations = $locationService->getNearbyPopularLocations(
    39.9042, 116.4074, 5.0, 20
);

// 获取位置统计
$stats = $locationService->getLocationStats();
```

## API 接口

### 地理编码 (LocationController)

```
GET /api/location/geocode?address=北京市朝阳区
POST /api/location/reverse-geocode
GET /api/location/distance
```

### 位置管理

```
GET /api/location/popular
GET /api/location/nearby?lat=39.9042&lng=116.4074&radius=5
GET /api/location/city/{city}
GET /api/location/category/{category}
```

## 统计功能

```php
// 热门城市统计
$popularCities = Location::select('city')
    ->selectRaw('SUM(post_count) as total_posts')
    ->whereNotNull('city')
    ->groupBy('city')
    ->orderBy('total_posts', 'desc')
    ->take(10)
    ->get();

// 位置类型分布
$categories = Location::select('category')
    ->selectRaw('COUNT(*) as count')
    ->whereNotNull('category')
    ->groupBy('category')
    ->get();
```

## 数据库迁移

### 创建位置表
```bash
php artisan migrate --path=app/Modules/Location/Database/Migrations/2026_01_06_131641_create_locations_table.php
```

### 数据迁移
```bash
php artisan migrate --path=app/Modules/Location/Database/Migrations/2026_01_06_131930_migrate_existing_location_data_to_locations_table.php
```

## 注意事项

1. **数据迁移**：运行迁移时会自动将 posts 表中的 location JSON 数据迁移到新结构
2. **向后兼容**：Post 模型的 location 访问器保持 API 接口不变
3. **性能优化**：位置数据相对稳定，建议启用缓存
4. **地理搜索**：复杂地理查询建议使用专门的地理数据库扩展

## 扩展计划

- [ ] 集成地图服务 (高德、百度、腾讯地图)
- [ ] 支持地理围栏功能
- [ ] 添加位置标签系统
- [ ] 支持位置推荐算法
- [ ] 添加地理位置缓存层
