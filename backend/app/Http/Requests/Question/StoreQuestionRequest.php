<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'stem' => ['required', 'string'],
            'options' => ['required', 'array', 'min:2', 'max:4'],
            'options.A' => ['required', 'string'],
            'options.B' => ['required', 'string'],
            'options.C' => ['nullable', 'string'],
            'options.D' => ['nullable', 'string'],
            'correct_option' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'explanation' => ['nullable', 'string'],
            'marks' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $options = $this->input('options', []);
            $correct = $this->input('correct_option');

            if ($correct && ! array_key_exists($correct, array_filter($options))) {
                $validator->errors()->add('correct_option', 'The correct option must match one of the provided options.');
            }
        });
    }
}
