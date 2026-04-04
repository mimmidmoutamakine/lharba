<?php

namespace App\Http\Requests\Student;

use App\Http\Controllers\Student\OnboardingController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'exam_family' => ['required', 'string', Rule::in(array_keys(OnboardingController::EXAM_FAMILY_OPTIONS))],
            'preferred_level' => ['required', 'string', Rule::in(array_keys(OnboardingController::LEVEL_OPTIONS))],
        ];
    }

    public function messages(): array
    {
        return [
            'exam_family.required' => 'اختر نوع الامتحان قبل المتابعة.',
            'preferred_level.required' => 'اختر المستوى المناسب قبل المتابعة.',
        ];
    }
}