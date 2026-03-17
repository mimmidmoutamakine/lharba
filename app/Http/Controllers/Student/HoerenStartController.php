<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class HoerenStartController extends Controller
{
    public function __invoke(Exam $exam): RedirectResponse
    {
        abort_unless(
            $exam->is_published || Auth::user()?->is_admin || $this->isHubGeneratedExam($exam),
            404
        );

        $attempt = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', Auth::id())
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->latest('id')
            ->first();

        if (! $attempt) {
            $hasSchreiben = $exam->sections()->where('type', ExamSection::TYPE_SCHREIBEN)->exists();
            $attempt = ExamAttempt::query()->create([
                'exam_id' => $exam->id,
                'user_id' => Auth::id(),
                'started_at' => now(),
                'remaining_seconds' => $exam->total_duration_minutes * 60,
                'hoeren_remaining_seconds' => 17 * 60,
                'hoeren_last_synced_at' => now(),
                'schreiben_remaining_seconds' => $hasSchreiben ? 30 * 60 : null,
                'schreiben_last_synced_at' => $hasSchreiben ? now() : null,
                'status' => ExamAttempt::STATUS_IN_PROGRESS,
            ]);
        } elseif (is_null($attempt->hoeren_remaining_seconds)) {
            $attempt->hoeren_remaining_seconds = 17 * 60;
            $attempt->hoeren_last_synced_at = now();
            $attempt->save();
        }

        $firstHoerenPart = $exam->sections()
            ->where('type', ExamSection::TYPE_HOEREN)
            ->with('parts')
            ->get()
            ->flatMap->parts
            ->sortBy('sort_order')
            ->first();

        abort_unless($firstHoerenPart, 404, 'This exam has no Horen parts yet.');

        return redirect()->route('attempts.parts.show', [$attempt, $firstHoerenPart]);
    }

    private function isHubGeneratedExam(Exam $exam): bool
    {
        $title = (string) $exam->title;

        return str_starts_with($title, '[Instant Practice]')
            || str_starts_with($title, '[Targeted Practice]')
            || str_starts_with($title, '[Custom Practice]')
            || str_starts_with($title, '[Model Practice]')
            || str_starts_with($title, '[Survival R');
    }
}
