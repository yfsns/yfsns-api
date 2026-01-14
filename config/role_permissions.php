<?php

return [
    'post' => [
        [
            'slug' => 'post.view',
            'name' => '浏览动态',
            'description' => '允许访问动态列表与详情',
        ],
        [
            'slug' => 'post.text',
            'name' => '发布文字动态',
            'description' => '允许发布纯文字动态内容',
        ],
        [
            'slug' => 'post.image',
            'name' => '发布图片动态',
            'description' => '允许上传并发布图片动态',
        ],
        [
            'slug' => 'post.video',
            'name' => '发布视频动态',
            'description' => '允许上传并发布视频动态',
        ],
        [
            'slug' => 'post.delete',
            'name' => '删除自己的动态',
            'description' => '允许删除自己发布的动态',
        ],
    ],
    'comment' => [
        [
            'slug' => 'comment.create',
            'name' => '发表评论',
            'description' => '允许在内容下发布评论',
        ],
        [
            'slug' => 'comment.delete',
            'name' => '删除评论',
            'description' => '允许删除自身或受管控的评论',
        ],
        [
            'slug' => 'comment.review',
            'name' => '审核评论',
            'description' => '允许后台审核、屏蔽不当评论',
        ],
    ],
    'topic' => [
        [
            'slug' => 'topic.create',
            'name' => '创建话题',
            'description' => '允许创建新的话题',
        ],
        [
            'slug' => 'topic.review',
            'name' => '审核话题',
            'description' => '允许审核、启用或禁用话题',
        ],
        [
            'slug' => 'topic.delete',
            'name' => '删除话题',
            'description' => '允许删除话题',
        ],
    ],
];
