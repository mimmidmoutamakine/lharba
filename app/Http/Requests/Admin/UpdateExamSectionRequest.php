<?php

namespace App\Http\Requests\Admin;

use App\Models\ExamSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamSectionRequest extends FormRequest
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
        $examId = (int) $this->route('exam')->id;
        $sectionId = (int) $this->route('section')->id;

        return [
            'type' => ['required', Rule::in(array_keys(ExamSection::types()))],
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => [
                'required',
                'integer',
                'min:1',
                'max:999',
                Rule::unique('exam_sections')
                    ->ignore($sectionId)
                    ->where(function ($query) use ($examId) {
                        return $query
                            ->where('exam_id', $examId)
                            ->where('type', $this->input('type'));
                    }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'sort_order.unique' => 'This section type already uses this sort order for this exam.',
        ];
    }
}
