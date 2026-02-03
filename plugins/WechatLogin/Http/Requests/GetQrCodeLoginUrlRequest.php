<?php

namespace Plugins\WechatLogin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetQrCodeLoginUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'redirect_uri' => 'required|url',
            'state' => 'nullable|string|max:128',
        ];
    }
}
