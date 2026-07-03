<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meet_link' => ['required', 'url', 'max:500'],
            'interview_at' => ['required', 'date', 'after:now'],
        ];
    }
}
