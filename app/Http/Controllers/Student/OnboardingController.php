<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreOnboardingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public const LEVEL_OPTIONS = [
        'B1' => 'B1',
        'B2' => 'B2',
    ];

    public const GOAL_OPTIONS = [
        'daily_practice' => 'تمرين يومي منظم',
        'exam_ready' => 'الاستعداد للامتحان',
        'section_focus' => 'التركيز على أقسام معينة',
        'speed_training' => 'تمرين سريع ومكثف',
    ];

    public const DAILY_MINUTE_OPTIONS = [15, 30, 45, 60, 90];

    public const SECTION_OPTIONS = [
        'lesen_t1' => 'القراءة - الجزء 1',
        'lesen_t2' => 'القراءة - الجزء 2',
        'lesen_t3' => 'القراءة - الجزء 3',
        'sprach_t1' => 'اللغويات - الجزء 1',
        'sprach_t2' => 'اللغويات - الجزء 2',
        'hoeren_t1' => 'الاستماع - الجزء 1',
        'hoeren_t2' => 'الاستماع - الجزء 2',
        'schreiben_t1' => 'الكتابة - الجزء 1',
    ];

    public function show(): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.exams.index');
        }

        if (! $user->isApproved()) {
            return redirect()->route('approval.pending');
        }

        if (! $user->needsOnboarding()) {
            return redirect()->route('dashboard');
        }

        return view('student.setup.show', [
            'levelOptions' => self::LEVEL_OPTIONS,
            'goalOptions' => self::GOAL_OPTIONS,
            'dailyMinuteOptions' => self::DAILY_MINUTE_OPTIONS,
            'sectionOptions' => self::SECTION_OPTIONS,
        ]);
    }

    public function store(StoreOnboardingRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isApproved()) {
            return redirect()->route('approval.pending');
        }

        $user->forceFill([
            'preferred_level' => $request->validated('preferred_level'),
            'study_goal' => $request->validated('study_goal'),
            'daily_minutes' => (int) $request->validated('daily_minutes'),
            'focus_sections' => array_values($request->validated('focus_sections')),
            'onboarding_completed_at' => now(),
        ])->save();

        return redirect()
            ->route('dashboard')
            ->with('status', 'تم حفظ إعداداتك. مرحباً بك من جديد.');
    }
}
