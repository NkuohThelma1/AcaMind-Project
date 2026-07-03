<?php

namespace App\Http\Requests\LearningResource;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLearningResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['video', 'pdf', 'notes', 'exercise', 'mock_paper', 'past_paper'])],
            'url' => ['nullable', 'url', 'max:2048'],
            'file' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('url') && ! $this->hasFile('file')) {
                $validator->errors()->add('url', 'Provide either a URL (e.g. a video link) or upload a file.');
            }
        });
    }
}
