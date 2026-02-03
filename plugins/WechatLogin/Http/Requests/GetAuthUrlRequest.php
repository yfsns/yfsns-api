<?php

namespace Plugins\WechatLogin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAuthUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'redirect_uri' => 'required|url',
            'scope' => 'nullable|string',
            'state' => 'nullable|string|max:128',
        ];
    }
}
