<?php

namespace App\Modules\Post\Queries;

use App\Modules\Post\Models\Post;
use App\Modules\Post\Services\PostService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostQuery
{
    protected Builder $query;
    protected array $params;
    protected ?int $currentUserId;
    protected ?int $total = null;
    protected bool $countCached = false;

    // 声明式API入口
    public static function build(array $params): self
    {
        return new self($params);
    }

    protected function __construct(array $params)
    {
        $this->params = $params;
        $this->currentUserId = auth()->id();
        $this->query = Post::query();
    }

    // 1. 关联加载
    public function withRelations(): self
    {
        // 保持原有方法用于详情页或其他需要完整数据的地方
        $this->query->with([
            'user' => fn($q) => $q->withEssentialFields('status'),
            'location' => fn($q) => $q->select('*'),
            'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',
            'likes:id,likeable_id,user_id',
            'comments:id,target_id,user_id,content,created_at',
            'collects:id,collectable_id,user_id',
            'mentions' => [
                'id,sender_id,receiver_id,username,nickname_at_time',
                'user' => fn($q) => $q->withBasicFields()
            ],
            'topics:id,name,description,cover,post_count,follower_count',
            'originalPost' => [
                'user' => fn($q) => $q->withEssentialFields('status'),
                'location' => fn($q) => $q->select('*'),
                'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at'
            ]
        ]);

        return $this;
    }

    /**
     * 列表页轻量级关联加载 - 只加载必要数据，提高性能
     */
    public function withListRelations(): self
    {
        $this->query->with([
            // 用户基本信息（头像、昵称等）
            'user:id,username,nickname,avatar,status',

            // 位置信息（只在有位置时加载）
            'location' => fn($q) => $q->select('*'),

            // 文件信息（图片、视频等）
            'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',

            // 只加载当前用户的点赞状态（如果已登录）
            'likes' => fn($q) => $q->when($this->currentUserId,
                fn($query) => $query->where('user_id', $this->currentUserId)
                    ->select('id', 'likeable_id', 'user_id')
            ),

            // 只加载当前用户的收藏状态（如果已登录）
            'collects' => fn($q) => $q->when($this->currentUserId,
                fn($query) => $query->where('user_id', $this->currentUserId)
                    ->select('id', 'collectable_id', 'user_id')
            ),

            // 话题基本信息
            'topics:id,name,description,cover',

            // 转发原帖的轻量级信息
            'originalPost' => fn($q) => $q->with([
                'user:id,username,nickname,avatar',
                'files:id,name,type,path,thumbnail'
            ])->select('id', 'user_id', 'content')
        ]);

        return $this;
    }

    /**
     * 列表页超轻量级关联加载 - 极致性能优化
     * 移除不必要字段，减少数据库传输和内存使用
     */
    public function withUltraLightRelations(): self
    {
        $this->query->select('posts.id', 'posts.user_id', 'posts.content', 'posts.type', 'posts.visibility', 'posts.like_count', 'posts.comment_count', 'posts.collect_count', 'posts.repost_id', 'posts.created_at', 'posts.is_top', 'posts.is_essence', 'posts.is_recommend')
            ->with([
                // 用户基本信息（移除status字段，列表页不需要）
                'user:id,username,nickname,avatar',

                // 位置信息（只选择前端需要的字段）
                'location' => fn($q) => $q->select('id', 'title', 'latitude', 'longitude', 'address', 'city'),

                // 文件信息（图片、视频等）
                'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',

                // 只加载当前用户的点赞状态（如果已登录）
                'likes' => fn($q) => $q->when(
                    $this->currentUserId,
                    fn($query) => $query->where('user_id', $this->currentUserId)
                        ->select('id', 'likeable_id', 'user_id')
                ),

                // 只加载当前用户的收藏状态（如果已登录）
                'collects' => fn($q) => $q->when(
                    $this->currentUserId,
                    fn($query) => $query->where('user_id', $this->currentUserId)
                        ->select('id', 'collectable_id', 'user_id')
                ),

                // 转发原帖的最小化信息（包含文件完整URL所需字段）
                'originalPost' => fn($q) => $q->with([
                    'user:id,username,nickname,avatar',
                    'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',
                ])->select('id', 'user_id', 'content')
            ]);

        return $this;
    }

    // 2. 应用筛选条件
    public function applyFilters(): self
    {
        $this->query->published()->byType($this->params['type']);

        $this->applyFilterByType($this->params['filter'] ?? 'all');

        return $this;
    }

    // 3. 应用排序
    public function applySorting(): self
    {
        $cursor = $this->params['cursor'] ?? null;

        // 优先级排序（仅第一页）
        if (!$cursor) {
            $this->query->orderBy('is_top', 'desc')
                       ->orderBy('is_essence', 'desc')
                       ->orderBy('is_recommend', 'desc');
        }

        // 时间排序（保证游标分页一致）
        $this->query->orderBy('created_at', 'desc')
                   ->orderBy('id', 'desc');

        return $this;
    }

    // 4. 游标分页
    public function paginateWithCursor(): self
    {
        $limit = $this->params['limit'] ?? 10;
        $cursor = $this->params['cursor'] ?? null;

        // 使用Laravel原生cursor分页，使用查询中已有的select字段
        $this->paginator = $this->query->cursorPaginate($limit, cursorName: 'cursor', cursor: $cursor);

        return $this;
    }

    // 5. 缓存总数
    public function cacheCount(): self
    {
        $cacheKey = $this->generateCacheKey();

        $this->total = Cache::remember($cacheKey, 300,  // 增加缓存时间到5分钟
            fn() => (clone $this->query)->count()
        );

        $this->countCached = true;
        return $this;
    }

    // 6. 预计算用户状态（占位符）
    public function enrichUserStatus(): self
    {
        return $this;
    }

    // 7. 执行查询并格式化响应
    public function toResponse()
    {
        // 获取分页数据
        $paginator = $this->paginator;

        // 预计算用户状态（返回集合）
        $posts = $this->enrichPostsWithUserStatus(collect($paginator->items()), $this->currentUserId);

        // 转换为资源格式的数组
        $items = $posts->map(fn($post) =>
            app(\App\Modules\Post\Resources\PostResource::class, ['resource' => $post])->resolve()
        )->toArray();

        // 返回自定义格式，保持向后兼容但使用Laravel分页的cursor
        return [
            'data' => $items,
            'path' => $paginator->path(),
            'per_page' => $paginator->perPage(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_cursor' => $paginator->previousCursor()?->encode(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ];
    }

    // ==================== 内部实现 ====================

    protected function applyFilterByType(string $filter): void
    {
        // 配置驱动的过滤器映射
        $filterMap = [
            'all' => fn() => $this->applyVisibilityFilter($this->query, $this->currentUserId),
            'media' => fn() => $this->query->public()->whereHas('files'),
            'liked' => fn() => $this->query->whereHas('likes',
                fn($q) => $q->where('user_id', $this->currentUserId)
            )->tap(fn() => $this->applyVisibilityFilter($this->query, $this->currentUserId)),
            'user' => fn() => $this->query->byUser($this->params['userId'] ?? null)
                ->tap(fn() => $this->applyVisibilityFilter($this->query, $this->currentUserId, $this->params['userId'] ?? null)),
            'my' => fn() => $this->query->byUser($this->currentUserId),
            'following' => function() {
                $this->query->whereExists(function($q) {
                    $q->selectRaw(1)->from('user_follows')
                        ->where('follower_id', $this->currentUserId)
                        ->whereColumn('following_id', 'posts.user_id');
                })->tap(fn() => $this->applyVisibilityFilter($this->query, $this->currentUserId));
            },
            'topic' => function() {
                $topicIdentifier = $this->params['topicId'] ?? $this->params['topicName'] ?? null;

                if (!$topicIdentifier) {
                    throw new \InvalidArgumentException('按话题筛选时必须提供topicId或topicName参数');
                }

                $this->query->public()->whereHas('topics', function($q) use ($topicIdentifier) {
                    // 如果是数字，认为是ID；否则认为是名称
                    if (is_numeric($topicIdentifier)) {
                        $q->where('id', $topicIdentifier);
                    } else {
                        $q->where('name', $topicIdentifier);
                    }
                });
            },
        ];

        if (!isset($filterMap[$filter])) {
            throw new InvalidArgumentException("不支持的筛选类型: {$filter}");
        }

        $filterMap[$filter]();
    }

    protected function generateCacheKey(): string
    {
        $userFilters = ['my', 'liked', 'following'];

        return 'posts_count_' . md5(serialize([
            'type' => $this->params['type'],
            'filter' => $this->params['filter'] ?? 'all',
            'userId' => $this->params['userId'] ?? null,
            'topicId' => $this->params['topicId'] ?? null,
            'topicName' => $this->params['topicName'] ?? null,
            'currentUserId' => in_array($this->params['filter'] ?? 'all', $userFilters)
                ? $this->currentUserId : null,
        ]));
    }

    /**
     * 应用动态可见性过滤.
     */
    protected function applyVisibilityFilter($query, ?int $currentUserId, ?int $targetUserId = null): void
    {
        // 构建可见性过滤条件
        $query->where(function ($q) use ($currentUserId): void {
            // 情况1: 公开动态 - 所有人可见
            $q->where('visibility', Post::VISIBILITY_PUBLIC);

            if ($currentUserId) {
                // 情况2: 自己的动态 - 无论可见性都能看到
                $q->orWhere('user_id', $currentUserId);

                // 情况3: 粉丝可见动态 - 当前用户是发布者的粉丝
                $q->orWhere(function ($subQ) use ($currentUserId): void {
                    $subQ->where('visibility', Post::VISIBILITY_FOLLOWERS)
                        ->whereExists(function ($existsQ) use ($currentUserId): void {
                            $existsQ->select(DB::raw(1))
                                ->from('user_follows')
                                ->where('follower_id', $currentUserId)
                                ->whereColumn('following_id', 'posts.user_id');
                        });
                });

                // 情况4: 好友可见动态 - 当前用户是发布者的好友
                $q->orWhere(function ($subQ) use ($currentUserId): void {
                    $subQ->where('visibility', Post::VISIBILITY_FRIENDS)
                        ->whereExists(function ($existsQ) use ($currentUserId): void {
                            $existsQ->select(DB::raw(1))
                                ->from('user_friends')
                                ->where(function ($friendQ) use ($currentUserId): void {
                                    $friendQ->where('user_id', $currentUserId)
                                        ->whereColumn('friend_id', 'posts.user_id');
                                })->orWhere(function ($friendQ) use ($currentUserId): void {
                                    $friendQ->where('friend_id', $currentUserId)
                                        ->whereColumn('user_id', 'posts.user_id');
                                });
                        });
                });

                // 情况5: 仅自己可见动态 - 只有发布者能看到
                // 这已经被情况2覆盖，因为user_id会匹配
            }
        });
    }

    /**
     * 为动态添加用户相关状态.
     */
    protected function enrichPostsWithUserStatus($posts, ?int $currentUserId)
    {
        if (!$currentUserId) {
            // 未登录用户，直接设置状态为false
            $posts->each(function ($post): void {
                $post->isLiked = false;
                $post->isCollected = false;
            });
            return $posts;
        }

        // 已登录用户，由于在查询时已经预加载了当前用户的点赞和收藏状态
        // 这里只需要检查是否存在相关记录即可
        $posts->each(function ($post): void {
            $post->isLiked = $post->likes->isNotEmpty();
            $post->isCollected = $post->collects->isNotEmpty();
        });

        return $posts;
    }

    /**
     * 获取当前认证用户ID.
     */
    protected function getCurrentUserId(): ?int
    {
        return Auth::id();
    }
}
