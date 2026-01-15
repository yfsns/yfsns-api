<?php

namespace Tests\Unit\Modules\Dynamic;

use App\Modules\Post\Controllers\PostController;
use App\Modules\Post\Models\Post;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DynamicControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = app(PostController::class);

        // 创建测试用户
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 模拟认证用户
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_create_a_post(): void
    {
        $request = new Request([
            'content' => '测试动态内容',
            'type' => 'text',
            'status' => 1,
        ]);

        $response = $this->controller->create($request);
        $data = $response->getData(true);

        static::assertEquals(200, $response->getStatusCode());
        static::assertArrayHasKey('data', $data);
        static::assertEquals($this->user->id, $data['data']['user_id']);
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

        $request = new Request([
            'content' => '更新后的内容',
            'status' => 0,
        ]);

        $response = $this->controller->update($request, $post->id);
        $data = $response->getData(true);

        static::assertEquals(200, $response->getStatusCode());
        static::assertArrayHasKey('data', $data);
        static::assertEquals($this->user->id, $data['data']['user_id']);
    }

    /** @test */
    public function it_can_delete_a_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->controller->delete($post->id);
        $data = $response->getData(true);

        static::assertEquals(200, $response->getStatusCode());
        static::assertArrayHasKey('message', $data);
    }

    /** @test */
    public function it_can_get_post_list(): void
    {
        // 创建15条测试动态
        Post::factory()->count(15)->create([
            'user_id' => $this->user->id,
        ]);

        $request = new Request([
            'user_id' => $this->user->id,
        ]);

        $response = $this->controller->getList($request);
        $data = $response->getData(true);

        static::assertEquals(200, $response->getStatusCode());
        static::assertArrayHasKey('data', $data);
        static::assertArrayHasKey('data', $data['data']); // 分页数据中的 data 字段
        static::assertCount(10, $data['data']['data']); // 默认每页返回10条数据
        static::assertEquals(15, $data['data']['total']); // 总记录数应为15条
        static::assertEquals(2, $data['data']['last_page']); // 总页数应为2页
    }

    /** @test */
    public function it_can_get_post_detail(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->controller->getDetail($post->id);
        $data = $response->getData(true);

        static::assertEquals(200, $response->getStatusCode());
        static::assertArrayHasKey('data', $data);
        static::assertEquals($this->user->id, $data['data']['user_id']);
    }
}
