<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Services\ExamAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AttemptSubmitController extends Controller
{
    public function __invoke(ExamAttempt $attempt, ExamAttemptService $attemptService): JsonResponse
    {
        if (! ($attempt->user_id === Auth::id() || Auth::user()?->is_admin)) {
            throw ValidationException::withMessages(['attempt' => 'Unauthorized attempt access.']);
        }

        $attemptService->syncHoerenRemainingSeconds($attempt);
        $attemptService->syncSchreibenRemainingSeconds($attempt);
        $attempt->refresh();

        $forceExpired = $attempt->remaining_seconds <= 0
            || (! is_null($attempt->hoeren_remaining_seconds) && $attempt->hoeren_remaining_seconds <= 0)
            || (! is_null($attempt->schreiben_remaining_seconds) && $attempt->schreiben_remaining_seconds <= 0);
        $summary = $attemptService->submitAttempt($attempt, $forceExpired);

        return response()->json([
            'ok' => true,
            'summary' => $summary,
            'redirect_url' => route('attempts.finished', $attempt),
            'message' => $summary['status'] === ExamAttempt::STATUS_EXPIRED
                ? 'Time is over. Exam submitted automatically.'
                : 'Exam submitted successfully.',
        ]);
    }
}
