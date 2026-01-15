<?php

namespace Tests\Feature\Comment;

use App\Modules\Comment\Models\Comment;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建测试用户
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        // 获取登录token
        $response = $this->postJson('/api/v1.0/auth/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $this->token = $response->json('access_token');
    }

    /**
     * 测试发表评论.
     */
    public function test_can_create_comment(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1.0/comments', [
            'target_id' => 1,
            'target_type' => 'post',
            'content' => '这是一条测试评论',
            'content_type' => 'text',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'id',
                    'target_id',
                    'target_type',
                    'user_id',
                    'content',
                    'content_type',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'target_id' => 1,
            'target_type' => 'post',
            'content' => '这是一条测试评论',
        ]);
    }

    /**
     * 测试获取评论列表.
     */
    public function test_can_get_comment_list(): void
    {
        // 先创建一些测试评论
        Comment::factory()->count(5)->create([
            'target_id' => 1,
            'target_type' => 'post',
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1.0/comments?target_id=1&target_type=post&sort=latest');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'content',
                            'user_id',
                            'created_at',
                        ],
                    ],
                    'total',
                ],
            ]);
    }

    /**
     * 测试评论点赞.
     */
    public function test_can_like_comment(): void
    {
        $comment = Comment::factory()->create([
            'target_id' => 1,
            'target_type' => 'post',
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1.0/comments/{$comment->id}/like");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * 测试取消评论点赞.
     */
    public function test_can_unlike_comment(): void
    {
        $comment = Comment::factory()->create([
            'target_id' => 1,
            'target_type' => 'post',
            'user_id' => $this->user->id,
        ]);

        // 先点赞
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1.0/comments/{$comment->id}/like");

        // 再取消点赞
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/v1.0/comments/{$comment->id}/like");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * 测试删除评论.
     */
    public function test_can_delete_comment(): void
    {
        $comment = Comment::factory()->create([
            'target_id' => 1,
            'target_type' => 'post',
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/v1.0/comments/{$comment->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('comments', [
            'id' => $comment->id,
        ]);
    }

    /**
     * 测试获取评论回复.
     */
    public function test_can_get_comment_replies(): void
    {
        // 创建父评论
        $parentComment = Comment::factory()->create([
            'target_id' => 1,
            'target_type' => 'post',
            'user_id' => $this->user->id,
        ]);

        // 创建回复评论
        Comment::factory()->count(3)->create([
            'target_id' => 1,
            'target_type' => 'post',
            'user_id' => $this->user->id,
            'parent_id' => $parentComment->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1.0/comments/{$parentComment->id}/replies");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'content',
                            'user_id',
                            'parent_id',
                            'created_at',
                        ],
                    ],
                    'total',
                ],
            ]);
    }
}
