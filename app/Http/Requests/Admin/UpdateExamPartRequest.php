<?php

namespace App\Http\Requests\Admin;

use App\Models\ExamPart;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamPartRequest extends FormRequest
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
            'instruction_text' => ['nullable', 'string'],
            'part_type' => ['required', Rule::in(array_keys(ExamPart::types()))],
            'points' => ['required', 'integer', 'min:0', 'max:200'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'config_json' => ['nullable', 'array'],
        ];
    }
}
