<?php

namespace Plugins\WechatLogin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BindWechatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string',
        ];
    }
}
