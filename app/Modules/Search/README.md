# 搜索模块 (Search Module)

## 概述

搜索模块为 YFSNS 系统提供了强大的搜索功能，支持多模型搜索、智能过滤、搜索建议等特性。

## 功能特性

### 核心功能
- **全局搜索**：支持搜索所有可搜索的内容类型
- **分类搜索**：支持按类型搜索（动态、用户、评论、话题、群组）
- **智能过滤**：支持多种过滤条件
- **搜索建议**：实时搜索建议和自动补全
- **热门搜索**：热门搜索词统计和展示

### 高级特性
- **权重排序**：智能相关性排序
- **高亮显示**：搜索结果关键词高亮
- **缓存支持**：搜索结果缓存优化
- **日志记录**：搜索行为统计和分析

## 目录结构

```
app/Modules/Search/
├── Controllers/           # 控制器
│   ├── SearchController.php          # 前台搜索控制器
│   └── Admin/                       # 后台管理控制器
│       └── SearchAdminController.php
├── Services/              # 服务层
│   └── SearchService.php            # 搜索服务
├── Resources/             # 资源类
│   └── SearchResultResource.php     # 搜索结果格式化
├── Routes/                # 路由
│   ├── api.php                      # 前台API路由
│   └── admin.php                    # 后台管理路由
├── Providers/             # 服务提供者
│   └── SearchServiceProvider.php    # 搜索模块服务提供者
├── config/                # 配置文件
│   └── search.php                   # 搜索模块配置
└── README.md              # 说明文档
```

## API 接口

### 前台接口

#### 1. 全局搜索
```
GET /api/v1/search?q={关键词}&type={类型}&limit={限制}&filters={过滤器}
```

**参数说明：**
- `q` (必填): 搜索关键词
- `type` (可选): 搜索类型 (all, posts, users, comments, topics, groups)
- `limit` (可选): 返回数量限制，默认20，最大100
- `filters` (可选): 过滤条件

**示例：**
```bash
# 搜索所有类型
GET /api/v1/search?q=人工智能&type=all&limit=20

# 只搜索动态
GET /api/v1/search?q=机器学习&type=posts&limit=10

# 带过滤条件
GET /api/v1/search?q=区块链&type=posts&filters[user_id]=1
```

#### 2. 分类搜索
```
GET /api/v1/search/posts?q={关键词}&limit={限制}&filters={过滤器}
GET /api/v1/search/users?q={关键词}&limit={限制}&filters={过滤器}
GET /api/v1/search/comments?q={关键词}&limit={限制}&filters={过滤器}
GET /api/v1/search/topics?q={关键词}&limit={限制}&filters={过滤器}
GET /api/v1/search/groups?q={关键词}&limit={限制}&filters={过滤器}
```

#### 3. 搜索建议
```
GET /api/v1/search/suggestions?q={关键词}&limit={限制}
```

#### 4. 热门搜索
```
GET /api/v1/search/hot?limit={限制}
```

### 后台管理接口

#### 1. 搜索统计
```
GET /api/admin/search/stats
```

#### 2. 热门搜索词管理
```
GET    /api/admin/search/hot-words          # 获取热门搜索词
POST   /api/admin/search/hot-words          # 添加热门搜索词
PUT    /api/admin/search/hot-words/{id}    # 更新热门搜索词
DELETE /api/admin/search/hot-words/{id}    # 删除热门搜索词
```

#### 3. 搜索日志
```
GET    /api/admin/search/logs               # 获取搜索日志
DELETE /api/admin/search/logs               # 清空搜索日志
```

## 配置说明

### 环境变量
```env
# 搜索限制配置
SEARCH_DEFAULT_LIMIT=20
SEARCH_MAX_LIMIT=100
SEARCH_SUGGESTIONS_LIMIT=10
SEARCH_HOT_SEARCHES_LIMIT=20

# 搜索缓存配置
SEARCH_CACHE_ENABLED=true
SEARCH_CACHE_TTL=300

# 搜索日志配置
SEARCH_LOGGING_ENABLED=true
SEARCH_LOGGING_LEVEL=info

# 搜索高亮配置
SEARCH_HIGHLIGHT_ENABLED=true
SEARCH_HIGHLIGHT_MAX_LENGTH=200
SEARCH_HIGHLIGHT_TAG=em

# 搜索过滤器配置
SEARCH_FILTERS_ENABLED=true
```

### 权重配置
```php
'weights' => [
    'title' => 10,        // 标题权重最高
    'content' => 5,       // 内容权重中等
    'username' => 8,      // 用户名权重高
    'nickname' => 8,      // 昵称权重高
    'bio' => 3,           // 个人简介权重低
    'name' => 10,         // 名称权重最高
    'description' => 5,   // 描述权重中等
],
```

## 使用方法

### 1. 注册服务提供者

在 `config/app.php` 中注册搜索模块：

```php
'providers' => [
    // ... 其他服务提供者
    App\Modules\Search\Providers\SearchServiceProvider::class,
],
```

### 2. 在控制器中使用

```php
use App\Modules\Search\Services\SearchService;

class SomeController extends Controller
{
    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }
    
    public function search(Request $request)
    {
        $results = $this->searchService->globalSearch(
            $request->get('q'),
            $request->get('filters', []),
            $request->get('limit', 20)
        );
        
        return response()->json($results);
    }
}
```

### 3. 前端调用示例

```javascript
// 全局搜索
const searchResults = await fetch('/api/v1/search?q=人工智能&type=all&limit=20');

// 搜索建议
const suggestions = await fetch('/api/v1/search/suggestions?q=人工&limit=10');

// 热门搜索
const hotSearches = await fetch('/api/v1/search/hot?limit=20');
```

## 扩展开发

### 添加新的可搜索模型

1. 在 `config/search.php` 中添加新模型：
```php
'searchable_models' => [
    // ... 现有模型
    'articles' => \App\Modules\Article\Models\Article::class,
],
```

2. 在 `SearchService` 中添加搜索方法：
```php
public function searchArticles(string $query, array $filters = [], int $limit = 20): Collection
{
    // 实现文章搜索逻辑
}
```

3. 在 `SearchController` 中添加对应的API接口

### 自定义搜索算法

可以通过继承 `SearchService` 或实现自定义的搜索接口来扩展搜索功能：

```php
interface SearchInterface
{
    public function search(string $query, array $filters = [], int $limit = 20): Collection;
}

class ElasticsearchService implements SearchInterface
{
    // 实现 Elasticsearch 搜索
}

class MeilisearchService implements SearchInterface
{
    // 实现 Meilisearch 搜索
}
```

## 性能优化

### 1. 数据库索引
确保搜索字段有适当的数据库索引：
```sql
-- 为标题和内容添加全文索引
ALTER TABLE posts ADD FULLTEXT(title, content);

-- 为用户名和昵称添加索引
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_nickname ON users(nickname);
```

### 2. 缓存策略
- 热门搜索结果缓存
- 搜索建议缓存
- 热门搜索词缓存

### 3. 分页优化
- 使用游标分页代替偏移分页
- 限制单次查询数量

## 注意事项

1. **搜索权限**：确保敏感内容不被未授权用户搜索到
2. **性能监控**：监控搜索响应时间和资源消耗
3. **数据一致性**：确保搜索结果与数据库数据一致
4. **用户体验**：提供搜索加载状态和错误处理

## 更新日志

### v1.0.0 (2024-01-XX)
- 初始版本发布
- 支持基础搜索功能
- 支持多模型搜索
- 支持搜索建议和热门搜索

## 贡献指南

欢迎为搜索模块贡献代码！请遵循以下步骤：

1. Fork 项目
2. 创建功能分支
3. 提交更改
4. 创建 Pull Request

## 许可证

本项目采用 MIT 许可证。
