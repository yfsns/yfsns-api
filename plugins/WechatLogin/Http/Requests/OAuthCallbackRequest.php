<?php

namespace Plugins\WechatLogin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OAuthCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string',
            'state' => 'nullable|string|max:128',
        ];
    }
}
