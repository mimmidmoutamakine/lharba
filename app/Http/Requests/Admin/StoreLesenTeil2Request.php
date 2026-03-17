<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLesenTeil2Request extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'instruction_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:0', 'max:200'],
            'passage.title' => ['nullable', 'string', 'max:255'],
            'passage.body_text' => ['required', 'string'],
            'questions' => ['required', 'array', 'size:5'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'size:3'],
            'questions.*.options.*.option_key' => ['required', 'string', 'max:10'],
            'questions.*.options.*.option_text' => ['required', 'string', 'max:255'],
            'questions.*.correct_option_key' => ['required', 'string'],
        ];
    }
}
