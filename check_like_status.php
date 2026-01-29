<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // 获取admin用户
    $user = \App\Modules\User\Models\User::where('username', 'admin')->first();
    if (!$user) {
        echo "Admin user not found\n";
        exit(1);
    }

    echo "Admin User ID: {$user->id}\n";
    echo "Admin Username: {$user->username}\n";
    echo "================================\n";

    // 检查ID为27的记录是否存在（可能是动态或评论）
    $post = \App\Modules\Post\Models\Post::find(27);
    if ($post) {
        echo "Found Post ID 27:\n";
        echo "- Type: {$post->type}\n";
        echo "- Content: " . substr($post->content ?? '', 0, 50) . "...\n";
        echo "- User ID: {$post->user_id}\n";

        // 检查admin用户是否对这个动态点了赞
        $like = \App\Modules\Like\Models\Like::where('user_id', $user->id)
            ->where('likeable_id', 27)
            ->where('likeable_type', 'post')
            ->first();

        if ($like) {
            echo "✅ Admin user HAS liked Post ID 27\n";
        } else {
            echo "❌ Admin user has NOT liked Post ID 27\n";
        }
    } else {
        echo "Post ID 27 not found\n";
    }

    echo "================================\n";

    // 检查是否有任何动态ID为27的点赞记录
    $allLikesFor27 = \App\Modules\Like\Models\Like::where('likeable_id', 27)->get();
    echo "All likes for ID 27: {$allLikesFor27->count()}\n";
    foreach ($allLikesFor27 as $like) {
        $liker = \App\Modules\User\Models\User::find($like->user_id);
        echo "- User {$liker->username} ({$like->user_id}) liked {$like->likeable_type} {$like->likeable_id}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}