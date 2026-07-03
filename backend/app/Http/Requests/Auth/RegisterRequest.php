<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['student', 'teacher_pending'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'level_id' => ['required_if:role,student', 'nullable', 'integer', 'exists:levels,id'],

            // Teacher applicants must submit verification documents so an admin
            // can confirm their identity and credentials before approval.
            'national_id' => ['required_if:role,teacher_pending', 'nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'degree_certificate' => ['required_if:role,teacher_pending', 'nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'teaching_qualification' => ['required_if:role,teacher_pending', 'nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'cv' => ['required_if:role,teacher_pending', 'nullable', 'file', 'max:5120', 'mimes:pdf,doc,docx'],
        ];
    }
}
