<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }
}
