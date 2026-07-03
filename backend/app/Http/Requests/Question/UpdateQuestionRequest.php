<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['sometimes', 'integer', 'exists:topics,id'],
            'stem' => ['sometimes', 'string'],
            'options' => ['sometimes', 'array', 'min:2', 'max:4'],
            'options.A' => ['required_with:options', 'string'],
            'options.B' => ['required_with:options', 'string'],
            'options.C' => ['nullable', 'string'],
            'options.D' => ['nullable', 'string'],
            'correct_option' => ['sometimes', Rule::in(['A', 'B', 'C', 'D'])],
            'difficulty' => ['sometimes', 'integer', 'between:1,5'],
            'explanation' => ['nullable', 'string'],
            'marks' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('options') || ! $this->has('correct_option')) {
                return;
            }

            $options = $this->input('options', []);
            $correct = $this->input('correct_option');

            if ($correct && ! array_key_exists($correct, array_filter($options))) {
                $validator->errors()->add('correct_option', 'The correct option must match one of the provided options.');
            }
        });
    }
}
