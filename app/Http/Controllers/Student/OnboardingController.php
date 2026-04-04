<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreOnboardingRequest;
use App\Models\ExamFamily;
use App\Models\UserExamAccess;
use App\Models\UserExamRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public const EXAM_FAMILY_OPTIONS = [
        'telc' => 'TELC',
        'goethe' => 'Goethe',
        'osd' => 'ÖSD',
        'ecl' => 'ECL',
    ];

    public const LEVEL_OPTIONS = [
        'A1' => 'A1',
        'A2' => 'A2',
        'B1' => 'B1',
        'B2' => 'B2',
        'C1' => 'C1',
        'C2' => 'C2',
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

        $hasActiveAccess = UserExamAccess::query()
            ->where('user_id', $user->id)
            ->where('status', UserExamAccess::STATUS_ACTIVE)
            ->exists();

        if ($hasActiveAccess) {
            return redirect()->route('dashboard');
        }

        $latestPendingRequest = UserExamRequest::query()
            ->with('examFamily')
            ->where('user_id', $user->id)
            ->where('status', UserExamRequest::STATUS_PENDING)
            ->latest('id')
            ->first();

        return view('student.setup.show', [
            'examFamilyOptions' => self::EXAM_FAMILY_OPTIONS,
            'levelOptions' => self::LEVEL_OPTIONS,
            'latestPendingRequest' => $latestPendingRequest,
        ]);
    }

    public function store(StoreOnboardingRequest $request): RedirectResponse
    {
        $user = $request->user();

        $hasActiveAccess = UserExamAccess::query()
            ->where('user_id', $user->id)
            ->where('status', UserExamAccess::STATUS_ACTIVE)
            ->exists();

        if ($hasActiveAccess) {
            return redirect()->route('dashboard');
        }

        $examFamilyId = ExamFamily::query()
            ->where('code', $request->validated('exam_family'))
            ->value('id');

        abort_unless($examFamilyId, 422);

        $latestPendingRequest = UserExamRequest::query()
            ->where('user_id', $user->id)
            ->where('status', UserExamRequest::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($latestPendingRequest) {
            $latestPendingRequest->update([
                'exam_family_id' => $examFamilyId,
                'level' => $request->validated('preferred_level'),
                'submitted_at' => now(),
            ]);
        } else {
            UserExamRequest::create([
                'user_id' => $user->id,
                'exam_family_id' => $examFamilyId,
                'level' => $request->validated('preferred_level'),
                'status' => UserExamRequest::STATUS_PENDING,
                'submitted_at' => now(),
            ]);
        }

        return redirect()
            ->route('approval.pending')
            ->with('status', 'تم تحديث الطلب وإرساله للمراجعة.');
    }
}