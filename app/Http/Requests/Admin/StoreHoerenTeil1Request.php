<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreHoerenTeil1Request extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'instruction_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:0', 'max:200'],
            'audio_url' => ['nullable', 'url', 'max:2048'],
            'audio_duration_seconds' => ['nullable', 'integer', 'min:1', 'max:7200'],
            'questions' => ['required', 'array', 'size:5'],
            'questions.*.statement_text' => ['required', 'string'],
            'questions.*.is_true_correct' => ['required', 'boolean'],
        ];
    }
}

