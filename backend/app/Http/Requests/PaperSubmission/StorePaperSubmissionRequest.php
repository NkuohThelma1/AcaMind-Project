<?php

namespace App\Http\Requests\PaperSubmission;

use Illuminate\Foundation\Http\FormRequest;

class StorePaperSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'question_reference' => ['nullable', 'string', 'max:255'],
            'submitted_text' => ['required_without:file_path', 'nullable', 'string'],
            'file_path' => ['required_without:submitted_text', 'nullable', 'string'],
        ];
    }
}
