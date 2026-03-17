<?php

namespace App\Http\Requests\Admin;

use App\Models\ExamPart;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamPartRequest extends FormRequest
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
        $bankMode = $this->filled('part_bank_item_id') || $this->boolean('random_from_bank');

        return [
            'part_bank_item_id' => ['nullable', 'integer', 'exists:part_bank_items,id'],
            'random_from_bank' => ['nullable', 'boolean'],
            'title' => [Rule::requiredIf(! $bankMode), 'nullable', 'string', 'max:255'],
            'instruction_text' => ['nullable', 'string'],
            'part_type' => [Rule::requiredIf(! $bankMode || $this->boolean('random_from_bank')), Rule::in(array_keys(ExamPart::types()))],
            'points' => [Rule::requiredIf(! $bankMode), 'nullable', 'integer', 'min:0', 'max:200'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'config_json' => ['nullable', 'array'],
        ];
    }
}
