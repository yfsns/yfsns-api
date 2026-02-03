<?php

namespace Plugins\VoteSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:single,multiple',
            'options' => 'required|array|min:2|max:50',
            'options.*.title' => 'required|string|max:255',
            'options.*.description' => 'nullable|string|max:500',
            'options.*.image' => 'nullable|url',
            'start_time' => 'nullable|date|after:now',
            'end_time' => 'nullable|date|after:start_time',
            'allow_guest' => 'boolean',
            'show_results' => 'boolean',
            'require_login' => 'boolean',
            'max_votes' => 'integer|min:1|max:20',
            'settings' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '投票标题不能为空',
            'title.max' => '投票标题不能超过255个字符',
            'description.max' => '投票描述不能超过1000个字符',
            'type.required' => '请选择投票类型',
            'type.in' => '投票类型无效',
            'options.required' => '至少需要2个投票选项',
            'options.min' => '至少需要2个投票选项',
            'options.max' => '最多只能有50个投票选项',
            'options.*.title.required' => '选项标题不能为空',
            'options.*.title.max' => '选项标题不能超过255个字符',
            'options.*.description.max' => '选项描述不能超过500个字符',
            'options.*.image.url' => '选项图片必须是有效的URL',
            'start_time.date' => '开始时间格式不正确',
            'start_time.after' => '开始时间必须晚于当前时间',
            'end_time.date' => '结束时间格式不正确',
            'end_time.after' => '结束时间必须晚于开始时间',
            'max_votes.min' => '最多可投选项数不能少于1',
            'max_votes.max' => '最多可投选项数不能超过20',
        ];
    }
}
