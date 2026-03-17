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
            'preferred_level' => ['required', 'string', Rule::in(array_keys(OnboardingController::LEVEL_OPTIONS))],
            'study_goal' => ['required', 'string', Rule::in(array_keys(OnboardingController::GOAL_OPTIONS))],
            'daily_minutes' => ['required', 'integer', Rule::in(OnboardingController::DAILY_MINUTE_OPTIONS)],
            'focus_sections' => ['required', 'array', 'min:1'],
            'focus_sections.*' => ['string', Rule::in(array_keys(OnboardingController::SECTION_OPTIONS))],
        ];
    }

    public function messages(): array
    {
        return [
            'preferred_level.required' => 'اختر المستوى المناسب قبل المتابعة.',
            'study_goal.required' => 'حدد هدفك من الدراسة.',
            'daily_minutes.required' => 'اختر الوقت اليومي الذي يناسبك.',
            'focus_sections.required' => 'اختر قسماً واحداً على الأقل للتركيز عليه.',
            'focus_sections.min' => 'اختر قسماً واحداً على الأقل للتركيز عليه.',
        ];
    }
}
