<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamPart;
use App\Models\ExamSection;
use App\Services\ExamAttemptService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExamPartController extends Controller
{
    public function __invoke(ExamAttempt $attempt, ExamPart $part, ExamAttemptService $attemptService): View
    {
        abort_unless($attempt->user_id === Auth::id() || Auth::user()?->is_admin, 403);
        abort_unless($part->section->exam_id === $attempt->exam_id, 404);

        if ($part->section->type === ExamSection::TYPE_HOEREN) {
            $attemptService->syncHoerenRemainingSeconds($attempt);
        } elseif ($part->section->type === ExamSection::TYPE_SCHREIBEN) {
            $attemptService->syncSchreibenRemainingSeconds($attempt);
        } else {
            $attemptService->syncRemainingSeconds($attempt);
        }
        $attempt->refresh();

        $attempt->load([
            'exam.sections.parts' => fn ($query) => $query->withCount(['lesenMatchingTexts', 'lesenMcqQuestions', 'lesenSituations', 'sprachGapQuestions', 'sprachPoolGaps', 'hoerenTrueFalseQuestions']),
        ]);
        $part->load([
            'section',
            'lesenMatchingTexts',
            'lesenMatchingOptions',
            'lesenMcqPassages',
            'lesenMcqQuestions.options',
            'lesenSituationAds',
            'lesenSituations',
            'sprachGapPassages',
            'sprachGapQuestions.options',
            'sprachPoolPassages',
            'sprachPoolGaps',
            'sprachPoolOptions',
            'hoerenTrueFalseQuestions',
        ]);

        $assignments = $part->part_type === ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS
            ? $attemptService->getPartAssignments($attempt, $part)
            : [];
        $choices = $part->part_type === ExamPart::TYPE_READING_TEXT_MCQ
            ? $attemptService->getPartChoices($attempt, $part)
            : [];
        $situationAssignments = $part->part_type === ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X
            ? $attemptService->getSituationAssignments($attempt, $part)
            : [];
        $gapChoices = $part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ
            ? $attemptService->getSprachGapChoices($attempt, $part)
            : [];
        $poolAssignments = $part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH
            ? $attemptService->getSprachPoolAssignments($attempt, $part)
            : [];
        $tfChoices = $part->part_type === ExamPart::TYPE_HOEREN_TRUE_FALSE
            ? $attemptService->getHoerenTrueFalseChoices($attempt, $part)
            : [];
        $writingResponse = $part->part_type === ExamPart::TYPE_WRITING_TASK
            ? $attemptService->getWritingResponse($attempt, $part)
            : [];
        $partTabs = $attempt->exam->sections
            ->sortBy('sort_order')
            ->flatMap(function ($section) {
                return $section->parts
                    ->sortBy('sort_order')
                    ->map(fn ($sectionPart) => $sectionPart->setRelation('section', $section));
            })
            ->filter(function ($tabPart) use ($part) {
                if ($part->section->type === ExamSection::TYPE_HOEREN) {
                    return $tabPart->section->type === ExamSection::TYPE_HOEREN;
                }
                if ($part->section->type === ExamSection::TYPE_SCHREIBEN) {
                    return $tabPart->section->type === ExamSection::TYPE_SCHREIBEN;
                }

                return in_array($tabPart->section->type, [ExamSection::TYPE_LESEN, ExamSection::TYPE_SPRACHBAUSTEINE], true);
            })
            ->values();

        $completedPartIds = [];
        foreach ($partTabs as $tabPart) {
            if ($tabPart->part_type === ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS) {
                $count = count(
                    array_filter(
                        $attemptService->getPartAssignments($attempt, $tabPart),
                        static fn ($value): bool => ! is_null($value)
                    )
                );
                if ($count >= $tabPart->lesenMatchingTexts()->count()) {
                    $completedPartIds[] = $tabPart->id;
                }
                continue;
            }

            if ($tabPart->part_type === ExamPart::TYPE_READING_TEXT_MCQ) {
                $answered = count(
                    array_filter(
                        $attemptService->getPartChoices($attempt, $tabPart),
                        static fn ($value): bool => ! is_null($value)
                    )
                );
                if ($answered >= $tabPart->lesenMcqQuestions()->count()) {
                    $completedPartIds[] = $tabPart->id;
                }
                continue;
            }

            if ($tabPart->part_type === ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X) {
                $answered = count(
                    array_filter(
                        $attemptService->getSituationAssignments($attempt, $tabPart),
                        static fn ($value): bool => ! is_null($value) && $value !== ''
                    )
                );
                if ($answered >= $tabPart->lesenSituations()->count()) {
                    $completedPartIds[] = $tabPart->id;
                }
                continue;
            }

            if ($tabPart->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ) {
                $answered = count(
                    array_filter(
                        $attemptService->getSprachGapChoices($attempt, $tabPart),
                        static fn ($value): bool => ! is_null($value)
                    )
                );
                if ($answered >= $tabPart->sprachGapQuestions()->count()) {
                    $completedPartIds[] = $tabPart->id;
                }
            }

            if ($tabPart->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH) {
                $answered = count(
                    array_filter(
                        $attemptService->getSprachPoolAssignments($attempt, $tabPart),
                        static fn ($value): bool => ! is_null($value)
                    )
                );
                if ($answered >= $tabPart->sprachPoolGaps()->count()) {
                    $completedPartIds[] = $tabPart->id;
                }
                continue;
            }

            if ($tabPart->part_type === ExamPart::TYPE_HOEREN_TRUE_FALSE) {
                $answered = count(
                    array_filter(
                        $attemptService->getHoerenTrueFalseChoices($attempt, $tabPart),
                        static fn ($value): bool => in_array($value, ['true', 'false'], true)
                    )
                );
                if ($answered >= $tabPart->hoerenTrueFalseQuestions()->count()) {
                    $completedPartIds[] = $tabPart->id;
                }
                continue;
            }

            if ($tabPart->part_type === ExamPart::TYPE_WRITING_TASK) {
                $response = $attemptService->getWritingResponse($attempt, $tabPart);
                $taskKey = trim((string) ($response['selected_task_key'] ?? ''));
                $text = trim((string) ($response['text'] ?? ''));
                $needsTaskChoice = str_starts_with(strtolower((string) $attempt->exam->level), 'b2');

                if ($text !== '' && (! $needsTaskChoice || $taskKey !== '')) {
                    $completedPartIds[] = $tabPart->id;
                }
            }
        }

        return view('student.parts.show', [
            'attempt' => $attempt,
            'part' => $part,
            'partTabs' => $partTabs,
            'assignments' => $assignments,
            'choices' => $choices,
            'situationAssignments' => $situationAssignments,
            'gapChoices' => $gapChoices,
            'poolAssignments' => $poolAssignments,
            'tfChoices' => $tfChoices,
            'writingResponse' => $writingResponse,
            'completedPartIds' => $completedPartIds,
            'moduleRemainingSeconds' => $part->section->type === ExamSection::TYPE_HOEREN
                ? (int) ($attempt->hoeren_remaining_seconds ?? 0)
                : ($part->section->type === ExamSection::TYPE_SCHREIBEN
                    ? (int) ($attempt->schreiben_remaining_seconds ?? 0)
                    : (int) $attempt->remaining_seconds),
        ]);
    }
}
