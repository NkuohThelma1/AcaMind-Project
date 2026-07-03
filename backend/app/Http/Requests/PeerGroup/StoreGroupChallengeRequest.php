<?php

namespace App\Http\Requests\PeerGroup;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'question_count' => ['nullable', 'integer', 'min:5', 'max:50'],
        ];
    }
}
