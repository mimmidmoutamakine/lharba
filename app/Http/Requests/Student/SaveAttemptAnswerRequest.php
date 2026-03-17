<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class SaveAttemptAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_part_id' => ['required', 'integer', 'exists:exam_parts,id'],
            'answer_json' => ['required', 'array'],
            'answer_json.assignments' => ['nullable', 'array'],
            'answer_json.choices' => ['nullable', 'array'],
            'answer_json.situation_assignments' => ['nullable', 'array'],
            'answer_json.gap_choices' => ['nullable', 'array'],
            'answer_json.pool_assignments' => ['nullable', 'array'],
            'answer_json.tf_choices' => ['nullable', 'array'],
            'answer_json.writing_response' => ['nullable', 'array'],
            'answer_json.writing_response.selected_task_key' => ['nullable', 'string', 'max:10'],
            'answer_json.writing_response.text' => ['nullable', 'string', 'max:20000'],
            'manual' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $json = $this->input('answer_json', []);
            if (! isset($json['assignments']) && ! isset($json['choices']) && ! isset($json['situation_assignments']) && ! isset($json['gap_choices']) && ! isset($json['pool_assignments']) && ! isset($json['tf_choices']) && ! isset($json['writing_response'])) {
                $validator->errors()->add('answer_json', 'answer_json must contain assignments, choices, situation_assignments, gap_choices, pool_assignments, tf_choices, or writing_response.');
            }
        });
    }
}
