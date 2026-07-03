<?php

namespace App\Http\Requests\LearningResource;

use Illuminate\Foundation\Http\FormRequest;

class RejectLearningResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
