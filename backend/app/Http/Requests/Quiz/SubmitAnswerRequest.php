<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quiz_question_id' => ['required', 'integer', 'exists:quiz_questions,id'],
            'selected_option' => ['nullable', 'string', 'max:1'],
            'time_taken_seconds' => ['required', 'integer', 'min:0'],
        ];
    }
}
