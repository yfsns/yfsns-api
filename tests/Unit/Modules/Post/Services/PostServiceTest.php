<?php

namespace Tests\Unit\Modules\Post\Services;

use App\Http\Exceptions\BusinessException;
use App\Modules\Collect\Services\CollectService;
use App\Modules\File\Models\File;
use App\Modules\Post\Repositories\PostRepository;
use App\Modules\Post\Services\PostService;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $postService;

    protected $user;

    protected $imageFile;

    protected $videoFile;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建测试用户
        $this->user = User::factory()->create();

        // 创建测试文件
        $this->imageFile = File::factory()->image()->create();
        $this->videoFile = File::factory()->video()->create();

        // 创建服务实例
        $this->postService = new PostService(
            app(PostRepository::class),
            app(CollectService::class),
            $this->user
        );
    }

    /**
     * 测试发布纯文本动态
     */
    public function test_create_text_post(): void
    {
        $data = [
            'content' => '这是一条测试动态',
            'visibility' => 1,
            'user_id' => $this->user->id,
        ];

        $post = $this->postService->create($data);

        static::assertNotNull($post);
        static::assertEquals($data['content'], $post->content);
        static::assertEquals($data['visibility'], $post->visibility);
        static::assertEquals($this->user->id, $post->user_id);
    }

    /**
     * 测试发布带图片的动态
     */
    public function test_create_image_post(): void
    {
        $data = [
            'content' => '这是一条带图片的测试动态',
            'visibility' => 1,
            'user_id' => $this->user->id,
            'file_ids' => [$this->imageFile->id],
        ];

        $post = $this->postService->create($data);

        static::assertNotNull($post);
        static::assertEquals($data['content'], $post->content);
        static::assertCount(1, $post->files);
        static::assertEquals($this->imageFile->id, $post->files->first()->id);
    }

    /**
     * 测试发布带视频的动态
     */
    public function test_create_video_post(): void
    {
        $data = [
            'content' => '这是一条带视频的测试动态',
            'visibility' => 1,
            'user_id' => $this->user->id,
            'file_ids' => [$this->videoFile->id],
        ];

        $post = $this->postService->create($data);

        static::assertNotNull($post);
        static::assertEquals($data['content'], $post->content);
        static::assertCount(1, $post->files);
        static::assertEquals($this->videoFile->id, $post->files->first()->id);
    }

    /**
     * 测试同时包含图片和视频的动态（应该失败）.
     */
    public function test_create_post_with_both_image_and_video(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('不能同时包含图片和视频');

        $data = [
            'content' => '这是一条测试动态',
            'visibility' => 1,
            'user_id' => $this->user->id,
            'file_ids' => [$this->imageFile->id, $this->videoFile->id],
        ];

        $this->postService->create($data);
    }

    /**
     * 测试发布超过9张图片的动态（应该失败）.
     */
    public function test_create_post_with_too_many_images(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('最多只能添加9张图片');

        // 创建10张图片
        $imageFiles = File::factory()->image()->count(10)->create();

        $data = [
            'content' => '这是一条测试动态',
            'visibility' => 1,
            'user_id' => $this->user->id,
            'file_ids' => $imageFiles->pluck('id')->toArray(),
        ];

        $this->postService->create($data);
    }

    /**
     * 测试发布多个视频的动态（应该失败）.
     */
    public function test_create_post_with_multiple_videos(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('只能添加1个视频');

        // 创建2个视频
        $videoFiles = File::factory()->video()->count(2)->create();

        $data = [
            'content' => '这是一条测试动态',
            'visibility' => 1,
            'user_id' => $this->user->id,
            'file_ids' => $videoFiles->pluck('id')->toArray(),
        ];

        $this->postService->create($data);
    }
}
