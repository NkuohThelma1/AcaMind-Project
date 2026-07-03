<?php

namespace App\Http\Requests\PeerGroup;

use Illuminate\Foundation\Http\FormRequest;

class StorePeerGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'level_id' => ['required', 'integer', 'exists:levels,id'],
        ];
    }
}
