<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamPart;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttemptFinishedController extends Controller
{
    private const PART_TYPE_TO_TRAINING_KEY = [
        ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS => 'lesen_t1',
        ExamPart::TYPE_READING_TEXT_MCQ => 'lesen_t2',
        ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X => 'lesen_t3',
        ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ => 'sprach_t1',
        ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH => 'sprach_t2',
        ExamPart::TYPE_HOEREN_TRUE_FALSE => 'hoeren_t1',
        ExamPart::TYPE_LISTENING_MCQ => 'hoeren_t2',
        ExamPart::TYPE_WRITING_TASK => 'schreiben_t1',
    ];

    public function __invoke(ExamAttempt $attempt): View
    {
        abort_unless($attempt->user_id === Auth::id() || Auth::user()?->is_admin, 403);
        abort_unless($attempt->isClosed(), 404);
        $attempt->load([
            'exam.sections.parts' => fn ($query) => $query
                ->orderBy('sort_order')
                ->with([
                    'section:id,exam_id,type,title,sort_order',
                    'lesenMatchingTexts:id,exam_part_id,label,body_text,sort_order',
                    'lesenMatchingOptions:id,exam_part_id,option_key,option_text,sort_order',
                    'lesenMatchingAnswers:id,exam_part_id,lesen_matching_text_id,correct_option_id',
                    'lesenMcqQuestions:id,exam_part_id,question_text,sort_order',
                    'lesenMcqQuestions.options:id,lesen_mcq_question_id,option_text,is_correct,sort_order',
                    'lesenSituations:id,exam_part_id,situation_text,sort_order',
                    'lesenSituationAds:id,exam_part_id,label,title,sort_order',
                    'lesenSituationAnswers:id,exam_part_id,lesen_situation_id,correct_ad_id,is_no_match',
                    'sprachGapQuestions:id,exam_part_id,gap_number,sort_order',
                    'sprachGapQuestions.options:id,sprach_gap_question_id,option_text,is_correct,sort_order',
                    'sprachPoolGaps:id,exam_part_id,label,sort_order',
                    'sprachPoolOptions:id,exam_part_id,option_key,option_text,sort_order',
                    'sprachPoolAnswers:id,exam_part_id,sprach_pool_gap_id,correct_option_id',
                    'hoerenTrueFalseQuestions:id,exam_part_id,statement_text,is_true_correct,sort_order',
                ]),
            'answers:id,exam_attempt_id,exam_part_id,answer_json,is_correct',
        ]);

        $title = (string) ($attempt->exam->title ?? '');
        $isSurvival = preg_match('/^\[Survival R(\d+)\]/', $title, $match) === 1;
        $round = $isSurvival ? (int) $match[1] : null;
        $hasScoredAnswers = $attempt->answers->whereNotNull('is_correct')->count() > 0;
        $failed = $attempt->answers->where('is_correct', false)->count() > 0;
        $passed = $hasScoredAnswers && ! $failed;
        $reviewParts = $this->buildReviewParts($attempt);
        $retrainKeys = collect($reviewParts)
            ->filter(fn (array $part) => $part['wrong'] > 0)
            ->pluck('training_key')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $reviewStartPartId = collect($reviewParts)
            ->firstWhere('wrong', '>', 0)['part_id']
            ?? ($reviewParts[0]['part_id'] ?? null);

        return view('student.attempts.finished', [
            'attempt' => $attempt,
            'isSurvival' => $isSurvival,
            'survivalRound' => $round,
            'survivalPassed' => $passed,
            'reviewParts' => $reviewParts,
            'retrainKeys' => $retrainKeys,
            'reviewStartPartId' => $reviewStartPartId,
        ]);
    }

    private function buildReviewParts(ExamAttempt $attempt): array
    {
        $answersByPart = $attempt->answers->keyBy('exam_part_id');
        $parts = $attempt->exam->sections
            ->sortBy('sort_order')
            ->flatMap(fn ($section) => $section->parts->sortBy('sort_order'))
            ->values();

        $result = [];
        foreach ($parts as $part) {
            $answerJson = (array) ($answersByPart[$part->id]->answer_json ?? []);
            $items = [];

            if ($part->part_type === ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS) {
                $optionMap = $part->lesenMatchingOptions->keyBy('id');
                $correctMap = $part->lesenMatchingAnswers->pluck('correct_option_id', 'lesen_matching_text_id');
                $givenMap = (array) ($answerJson['assignments'] ?? []);
                foreach ($part->lesenMatchingTexts as $text) {
                    $givenId = (int) ($givenMap[$text->id] ?? 0);
                    $correctId = (int) ($correctMap[$text->id] ?? 0);
                    $given = $givenId > 0 ? $this->matchingOptionLabel($optionMap[$givenId] ?? null) : '-';
                    $correct = $correctId > 0 ? $this->matchingOptionLabel($optionMap[$correctId] ?? null) : '-';
                    $items[] = [
                        'label' => 'Text '.$text->label,
                        'your' => $given,
                        'correct' => $correct,
                        'ok' => $givenId > 0 && $givenId === $correctId,
                    ];
                }
            } elseif ($part->part_type === ExamPart::TYPE_READING_TEXT_MCQ) {
                $givenMap = (array) ($answerJson['choices'] ?? []);
                foreach ($part->lesenMcqQuestions as $index => $question) {
                    $givenId = (int) ($givenMap[$question->id] ?? 0);
                    $correctOption = $question->options->firstWhere('is_correct', true);
                    $givenOption = $question->options->firstWhere('id', $givenId);
                    $items[] = [
                        'label' => ($index + 1).'. '.str($question->question_text)->limit(90),
                        'your' => $givenOption?->option_text ?? '-',
                        'correct' => $correctOption?->option_text ?? '-',
                        'ok' => $givenId > 0 && $correctOption && $givenId === (int) $correctOption->id,
                    ];
                }
            } elseif ($part->part_type === ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X) {
                $givenMap = (array) ($answerJson['situation_assignments'] ?? []);
                $ads = $part->lesenSituationAds->keyBy('id');
                $correctRows = $part->lesenSituationAnswers->keyBy('lesen_situation_id');
                foreach ($part->lesenSituations as $index => $situation) {
                    $givenRaw = $givenMap[$situation->id] ?? null;
                    $given = $givenRaw === 'X'
                        ? 'X'
                        : (($ad = $ads->get((int) $givenRaw)) ? ($ad->label.'. '.$ad->title) : '-');
                    $correctRow = $correctRows->get($situation->id);
                    $correct = '-';
                    $ok = false;
                    if ($correctRow) {
                        if ($correctRow->is_no_match) {
                            $correct = 'X';
                            $ok = $givenRaw === 'X';
                        } else {
                            $correctAd = $ads->get((int) $correctRow->correct_ad_id);
                            $correct = $correctAd ? ($correctAd->label.'. '.$correctAd->title) : '-';
                            $ok = (int) $givenRaw === (int) $correctRow->correct_ad_id;
                        }
                    }
                    $items[] = [
                        'label' => ($index + 1).'. '.str($situation->situation_text)->limit(90),
                        'your' => $given,
                        'correct' => $correct,
                        'ok' => $ok,
                    ];
                }
            } elseif ($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ) {
                $givenMap = (array) ($answerJson['gap_choices'] ?? []);
                foreach ($part->sprachGapQuestions as $question) {
                    $givenId = (int) ($givenMap[$question->id] ?? 0);
                    $correctOption = $question->options->firstWhere('is_correct', true);
                    $givenOption = $question->options->firstWhere('id', $givenId);
                    $items[] = [
                        'label' => 'Lucke '.$question->gap_number,
                        'your' => $givenOption?->option_text ?? '-',
                        'correct' => $correctOption?->option_text ?? '-',
                        'ok' => $givenId > 0 && $correctOption && $givenId === (int) $correctOption->id,
                    ];
                }
            } elseif ($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH) {
                $givenMap = (array) ($answerJson['pool_assignments'] ?? []);
                $optionMap = $part->sprachPoolOptions->keyBy('id');
                $correctMap = $part->sprachPoolAnswers->pluck('correct_option_id', 'sprach_pool_gap_id');
                foreach ($part->sprachPoolGaps as $gap) {
                    $givenId = (int) ($givenMap[$gap->id] ?? 0);
                    $correctId = (int) ($correctMap[$gap->id] ?? 0);
                    $items[] = [
                        'label' => 'Lucke '.$gap->label,
                        'your' => $this->poolOptionLabel($optionMap[$givenId] ?? null),
                        'correct' => $this->poolOptionLabel($optionMap[$correctId] ?? null),
                        'ok' => $givenId > 0 && $givenId === $correctId,
                    ];
                }
            } elseif ($part->part_type === ExamPart::TYPE_HOEREN_TRUE_FALSE) {
                $givenMap = (array) ($answerJson['tf_choices'] ?? []);
                foreach ($part->hoerenTrueFalseQuestions as $index => $question) {
                    $givenRaw = $givenMap[$question->id] ?? null;
                    $given = $givenRaw === 'true' ? 'Richtig' : ($givenRaw === 'false' ? 'Falsch' : '-');
                    $correct = $question->is_true_correct ? 'Richtig' : 'Falsch';
                    $items[] = [
                        'label' => ($index + 1).'. '.str($question->statement_text)->limit(90),
                        'your' => $given,
                        'correct' => $correct,
                        'ok' => (($givenRaw === 'true') === (bool) $question->is_true_correct) && in_array($givenRaw, ['true', 'false'], true),
                    ];
                }
            }

            if ($items !== []) {
                $result[] = [
                    'part_id' => $part->id,
                    'title' => $part->section->title.' - '.$part->title,
                    'correct' => collect($items)->where('ok', true)->count(),
                    'total' => count($items),
                    'wrong' => collect($items)->where('ok', false)->count(),
                    'items' => $items,
                    'training_key' => self::PART_TYPE_TO_TRAINING_KEY[$part->part_type] ?? null,
                ];
            }
        }

        return $result;
    }

    private function matchingOptionLabel($option): string
    {
        if (! $option) {
            return '-';
        }

        return trim(($option->option_key ?? '').'. '.($option->option_text ?? ''));
    }

    private function poolOptionLabel($option): string
    {
        if (! $option) {
            return '-';
        }

        return trim(($option->option_key ?? '').'. '.($option->option_text ?? ''));
    }
}
