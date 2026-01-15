<?php

namespace Tests\Unit\Modules\Article\Policies;

use App\Modules\Article\Models\Article;
use App\Modules\Article\Policies\ArticlePolicy;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ArticlePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ArticlePolicy $policy;

    protected User $author;

    protected User $otherUser;

    protected User $admin;

    protected Article $publishedArticle;

    protected Article $draftArticle;

    protected Article $pendingArticle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ArticlePolicy();

        // 创建测试用户
        $this->author = User::factory()->create(['status' => User::STATUS_ENABLED]);
        $this->otherUser = User::factory()->create(['status' => User::STATUS_ENABLED]);
        $this->admin = User::factory()->create(['is_admin' => true, 'status' => User::STATUS_ENABLED]);

        // 创建测试文章
        $this->publishedArticle = Article::create([
            'title' => '已发布文章',
            'slug' => 'published-article',
            'content' => '这是已发布的文章内容',
            'user_id' => $this->author->id,
            'status' => Article::STATUS_PUBLISHED,
        ]);

        $this->draftArticle = Article::create([
            'title' => '草稿文章',
            'slug' => 'draft-article',
            'content' => '这是草稿文章内容',
            'user_id' => $this->author->id,
            'status' => Article::STATUS_DRAFT,
        ]);

        $this->pendingArticle = Article::create([
            'title' => '待审核文章',
            'slug' => 'pending-article',
            'content' => '这是待审核文章内容',
            'user_id' => $this->author->id,
            'status' => Article::STATUS_PENDING,
        ]);
    }

    /**
     * 测试查看列表权限 - 所有人可以查看
     */
    public function test_view_any_allows_all_users(): void
    {
        $this->assertTrue($this->policy->viewAny($this->author));
        $this->assertTrue($this->policy->viewAny($this->otherUser));
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    /**
     * 测试查看已发布文章 - 所有人可以查看
     */
    public function test_view_published_article_allows_all_users(): void
    {
        // 已登录用户
        $this->assertTrue($this->policy->view($this->author, $this->publishedArticle));
        $this->assertTrue($this->policy->view($this->otherUser, $this->publishedArticle));
        $this->assertTrue($this->policy->view($this->admin, $this->publishedArticle));

        // 未登录用户（null）
        $this->assertTrue($this->policy->view(null, $this->publishedArticle));
    }

    /**
     * 测试查看草稿文章 - 只有作者可以查看
     */
    public function test_view_draft_article_only_allows_author(): void
    {
        // 作者可以查看
        $this->assertTrue($this->policy->view($this->author, $this->draftArticle));

        // 其他用户不能查看
        $this->assertFalse($this->policy->view($this->otherUser, $this->draftArticle));
        $this->assertFalse($this->policy->view($this->admin, $this->draftArticle));

        // 未登录用户不能查看
        $this->assertFalse($this->policy->view(null, $this->draftArticle));
    }

    /**
     * 测试查看待审核文章 - 只有作者可以查看
     */
    public function test_view_pending_article_only_allows_author(): void
    {
        // 作者可以查看
        $this->assertTrue($this->policy->view($this->author, $this->pendingArticle));

        // 其他用户不能查看
        $this->assertFalse($this->policy->view($this->otherUser, $this->pendingArticle));
        $this->assertFalse($this->policy->view($this->admin, $this->pendingArticle));

        // 未登录用户不能查看
        $this->assertFalse($this->policy->view(null, $this->pendingArticle));
    }

    /**
     * 测试创建文章 - 只有活跃用户可以创建
     */
    public function test_create_article_allows_enabled_users(): void
    {
        // 活跃用户可以创建
        $enabledUser = User::factory()->create(['status' => User::STATUS_ENABLED]);
        $this->assertTrue($this->policy->create($enabledUser));

        // 禁用用户不能创建
        $disabledUser = User::factory()->create(['status' => User::STATUS_DISABLED]);
        $this->assertFalse($this->policy->create($disabledUser));
    }

    /**
     * 测试更新文章 - 只有作者可以更新
     */
    public function test_update_article_only_allows_author(): void
    {
        // 作者可以更新
        $this->assertTrue($this->policy->update($this->author, $this->publishedArticle));

        // 其他用户不能更新
        $this->assertFalse($this->policy->update($this->otherUser, $this->publishedArticle));

        // 管理员可以通过 before 方法获得权限
        $this->assertTrue($this->policy->update($this->admin, $this->publishedArticle));
    }

    /**
     * 测试删除文章 - 只有作者可以删除
     */
    public function test_delete_article_only_allows_author(): void
    {
        // 作者可以删除
        $this->assertTrue($this->policy->delete($this->author, $this->publishedArticle));

        // 其他用户不能删除
        $this->assertFalse($this->policy->delete($this->otherUser, $this->publishedArticle));

        // 管理员可以通过 before 方法获得权限
        $this->assertTrue($this->policy->delete($this->admin, $this->publishedArticle));
    }

    /**
     * 测试发布文章 - 只有作者可以发布
     */
    public function test_publish_article_only_allows_author(): void
    {
        // 作者可以发布
        $this->assertTrue($this->policy->publish($this->author, $this->draftArticle));

        // 其他用户不能发布
        $this->assertFalse($this->policy->publish($this->otherUser, $this->draftArticle));

        // 管理员可以通过 before 方法获得权限
        $this->assertTrue($this->policy->publish($this->admin, $this->draftArticle));
    }

    /**
     * 测试撤回文章 - 只有作者可以撤回
     */
    public function test_unpublish_article_only_allows_author(): void
    {
        // 作者可以撤回
        $this->assertTrue($this->policy->unpublish($this->author, $this->publishedArticle));

        // 其他用户不能撤回
        $this->assertFalse($this->policy->unpublish($this->otherUser, $this->publishedArticle));

        // 管理员可以通过 before 方法获得权限
        $this->assertTrue($this->policy->unpublish($this->admin, $this->publishedArticle));
    }

    /**
     * 测试提交审核 - 只有作者可以提交
     */
    public function test_submit_for_review_only_allows_author(): void
    {
        // 作者可以提交审核
        $this->assertTrue($this->policy->submitForReview($this->author, $this->draftArticle));

        // 其他用户不能提交审核
        $this->assertFalse($this->policy->submitForReview($this->otherUser, $this->draftArticle));

        // 管理员可以通过 before 方法获得权限
        $this->assertTrue($this->policy->submitForReview($this->admin, $this->draftArticle));
    }

    /**
     * 测试审核文章 - 只有管理员可以审核
     */
    public function test_review_article_only_allows_admin(): void
    {
        // 管理员可以审核
        $this->assertTrue($this->policy->review($this->admin, $this->pendingArticle));

        // 作者不能审核
        $this->assertFalse($this->policy->review($this->author, $this->pendingArticle));

        // 其他用户不能审核
        $this->assertFalse($this->policy->review($this->otherUser, $this->pendingArticle));
    }

    /**
     * 测试管理员拥有所有权限（before 方法）
     */
    public function test_admin_has_all_permissions_via_before_method(): void
    {
        // 管理员应该能够执行所有操作
        $this->assertTrue($this->policy->update($this->admin, $this->publishedArticle));
        $this->assertTrue($this->policy->delete($this->admin, $this->publishedArticle));
        $this->assertTrue($this->policy->publish($this->admin, $this->draftArticle));
        $this->assertTrue($this->policy->unpublish($this->admin, $this->publishedArticle));
        $this->assertTrue($this->policy->submitForReview($this->admin, $this->draftArticle));
        $this->assertTrue($this->policy->review($this->admin, $this->pendingArticle));
    }

    /**
     * 测试通过 Gate 使用 Policy
     */
    public function test_policy_works_via_gate(): void
    {
        // 测试作者可以更新自己的文章
        $this->actingAs($this->author);
        $this->assertTrue(Gate::allows('update', $this->publishedArticle));

        // 测试其他用户不能更新
        $this->actingAs($this->otherUser);
        $this->assertFalse(Gate::allows('update', $this->publishedArticle));

        // 测试管理员可以更新
        $this->actingAs($this->admin);
        $this->assertTrue(Gate::allows('update', $this->publishedArticle));
    }

    /**
     * 测试 Gate authorize 抛出异常
     */
    public function test_gate_authorize_throws_exception(): void
    {
        $this->actingAs($this->otherUser);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        Gate::authorize('update', $this->publishedArticle);
    }
}

