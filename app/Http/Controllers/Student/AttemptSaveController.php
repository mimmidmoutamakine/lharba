<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SaveAttemptAnswerRequest;
use App\Models\ExamAttempt;
use App\Models\ExamPart;
use App\Services\ExamAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AttemptSaveController extends Controller
{
    public function __invoke(
        SaveAttemptAnswerRequest $request,
        ExamAttempt $attempt,
        ExamAttemptService $attemptService
    ): JsonResponse {
        if (! ($attempt->user_id === Auth::id() || Auth::user()?->is_admin)) {
            throw ValidationException::withMessages(['attempt' => 'Unauthorized attempt access.']);
        }

        $part = ExamPart::query()->findOrFail($request->integer('exam_part_id'));
        if ($part->section->exam_id !== $attempt->exam_id) {
            throw ValidationException::withMessages(['exam_part_id' => 'Part does not belong to this attempt.']);
        }

        $payload = $request->input('answer_json');
        if ($part->part_type === ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS) {
            $attemptService->saveMatchingAssignments($attempt, $part, $payload);
        } elseif ($part->part_type === ExamPart::TYPE_READING_TEXT_MCQ) {
            $attemptService->saveReadingMcqChoices($attempt, $part, $payload);
        } elseif ($part->part_type === ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X) {
            $attemptService->saveSituationAssignments($attempt, $part, $payload);
        } elseif ($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ) {
            $attemptService->saveSprachGapChoices($attempt, $part, $payload);
        } elseif ($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH) {
            $attemptService->saveSprachPoolAssignments($attempt, $part, $payload);
        } elseif ($part->part_type === ExamPart::TYPE_HOEREN_TRUE_FALSE) {
            $attemptService->saveHoerenTrueFalseChoices($attempt, $part, $payload);
        } elseif ($part->part_type === ExamPart::TYPE_WRITING_TASK) {
            $attemptService->saveWritingResponse($attempt, $part, $payload);
        } else {
            throw ValidationException::withMessages(['exam_part_id' => 'Unsupported part type for save endpoint.']);
        }

        $attempt->refresh();

        return response()->json([
            'ok' => true,
            'status' => $request->boolean('manual') ? 'saved' : 'autosaved',
            'remaining_seconds' => $part->section->type === \App\Models\ExamSection::TYPE_HOEREN
                ? (int) ($attempt->hoeren_remaining_seconds ?? 0)
                : ($part->section->type === \App\Models\ExamSection::TYPE_SCHREIBEN
                    ? (int) ($attempt->schreiben_remaining_seconds ?? 0)
                    : (int) $attempt->remaining_seconds),
            'attempt_status' => $attempt->status,
        ]);
    }
}
