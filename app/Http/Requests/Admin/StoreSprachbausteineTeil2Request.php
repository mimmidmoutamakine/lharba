<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSprachbausteineTeil2Request extends FormRequest
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
            'passage.title' => ['nullable', 'string', 'max:255'],
            'passage.body_text' => ['required', 'string'],

            'gaps' => ['required', 'array', 'size:10'],
            'gaps.*.id' => ['nullable', 'integer', 'exists:sprach_pool_gaps,id'],
            'gaps.*.label' => ['required', 'string', 'max:30'],
            'gaps.*.sort_order' => ['required', 'integer', 'min:1', 'max:999'],

            'options' => ['required', 'array', 'size:15'],
            'options.*.id' => ['nullable', 'integer', 'exists:sprach_pool_options,id'],
            'options.*.option_key' => ['required', 'string', 'max:10'],
            'options.*.option_text' => ['required', 'string', 'max:255'],
            'options.*.sort_order' => ['required', 'integer', 'min:1', 'max:999'],

            'correct_answers' => ['required', 'array', 'size:10'],
            'correct_answers.*' => ['required', 'string', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'gaps.size' => 'Teil 2 requires exactly 10 gaps.',
            'options.size' => 'Teil 2 requires exactly 15 options (A-O).',
            'correct_answers.*.distinct' => 'Each gap must have a different correct option.',
        ];
    }
}
