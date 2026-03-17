<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLesenTeil3Request extends FormRequest
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
            'ads' => ['required', 'array', 'size:12'],
            'ads.*.label' => ['required', 'string', 'max:10'],
            'ads.*.title' => ['nullable', 'string', 'max:255'],
            'ads.*.body_text' => ['required', 'string'],
            'situations' => ['required', 'array', 'size:10'],
            'situations.*.label' => ['required', 'string', 'max:10'],
            'situations.*.situation_text' => ['required', 'string', 'max:500'],
            'correct_answers' => ['required', 'array', 'size:10'],
            'correct_answers.*' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'ads.size' => 'Teil 3 requires exactly 12 Anzeigen.',
            'situations.size' => 'Teil 3 requires exactly 10 Situationen.',
            'correct_answers.size' => 'Each of the 10 situations needs one correct answer (ad label or X).',
        ];
    }
}
