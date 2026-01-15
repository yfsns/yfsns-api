<?php

namespace Tests\Unit\Modules\Post;

use App\Modules\Post\Models\Post;
use App\Modules\Post\Services\PostService;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $postservice;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postservice = app(PostService::class);

        // 创建测试用户
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    /** @test */
    public function it_can_create_a_post(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'content' => '测试动态内容',
            'type' => 'text',
            'status' => 1,
        ];

        $post = $this->postservice->create($data);

        static::assertInstanceOf(Post::class, $post);
        static::assertEquals($data['content'], $post->content);
        static::assertEquals($data['type'], $post->type);
        static::assertEquals($data['status'], $post->status);
        static::assertEquals($this->user->id, $post->user_id);
    }

    /** @test */
    public function it_can_update_a_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'content' => '原始内容',
            'type' => 'text',
            'status' => 1,
        ]);

        $updateData = [
            'content' => '更新后的内容',
            'status' => 0,
        ];

        $updatedpost = $this->postservice->update($post->id, $updateData);

        static::assertEquals($updateData['content'], $updatedpost->content);
        static::assertEquals($updateData['status'], $updatedpost->status);
        static::assertEquals($this->user->id, $updatedpost->user_id);
    }

    /** @test */
    public function it_can_delete_a_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $result = $this->postservice->delete($post->id);

        static::assertTrue($result);
        static::assertNull(Post::find($post->id));
    }

    /** @test */
    public function it_can_get_post_list(): void
    {
        Post::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $posts = $this->postservice->getList([
            'user_id' => $this->user->id,
        ]);

        static::assertCount(5, $posts);
    }

    /** @test */
    public function it_can_get_post_detail(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $foundpost = $this->postservice->getDetail($post->id);

        static::assertInstanceOf(Post::class, $foundpost);
        static::assertEquals($post->id, $foundpost->id);
        static::assertEquals($this->user->id, $foundpost->user_id);
    }
}
