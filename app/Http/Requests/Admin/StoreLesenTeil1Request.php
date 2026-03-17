<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLesenTeil1Request extends FormRequest
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
            'texts' => ['required', 'array', 'min:1'],
            'texts.*.id' => ['nullable', 'integer', 'exists:lesen_matching_texts,id'],
            'texts.*.label' => ['required', 'string', 'max:30'],
            'texts.*.body_text' => ['required', 'string'],
            'texts.*.sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'options' => ['required', 'array', 'min:1'],
            'options.*.id' => ['nullable', 'integer', 'exists:lesen_matching_options,id'],
            'options.*.option_key' => ['required', 'string', 'max:10'],
            'options.*.option_text' => ['required', 'string', 'max:255'],
            'options.*.sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'correct_answers' => ['required', 'array'],
            'correct_answers.*' => ['required', 'string', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'correct_answers.*.distinct' => 'Each text must have a different correct option.',
        ];
    }
}
