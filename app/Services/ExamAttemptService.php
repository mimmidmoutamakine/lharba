<?php

namespace App\Services;

use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPart;
use App\Models\ExamSection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAttemptService
{
    public function syncSchreibenRemainingSeconds(ExamAttempt $attempt): ExamAttempt
    {
        if ($attempt->isClosed() || is_null($attempt->schreiben_remaining_seconds)) {
            return $attempt;
        }

        $lastSynced = $attempt->schreiben_last_synced_at ?? $attempt->created_at ?? now();
        $elapsed = max(0, $lastSynced->diffInSeconds(now()));
        if ($elapsed <= 0) {
            return $attempt;
        }

        $attempt->schreiben_remaining_seconds = max(0, (int) $attempt->schreiben_remaining_seconds - $elapsed);
        $attempt->schreiben_last_synced_at = now();
        $attempt->save();

        return $attempt->refresh();
    }

    public function syncHoerenRemainingSeconds(ExamAttempt $attempt): ExamAttempt
    {
        if ($attempt->isClosed() || is_null($attempt->hoeren_remaining_seconds)) {
            return $attempt;
        }

        $lastSynced = $attempt->hoeren_last_synced_at ?? $attempt->created_at ?? now();
        $elapsed = max(0, $lastSynced->diffInSeconds(now()));
        if ($elapsed <= 0) {
            return $attempt;
        }

        $attempt->hoeren_remaining_seconds = max(0, (int) $attempt->hoeren_remaining_seconds - $elapsed);
        $attempt->hoeren_last_synced_at = now();
        $attempt->save();

        return $attempt->refresh();
    }

    public function syncRemainingSeconds(ExamAttempt $attempt): ExamAttempt
    {
        if ($attempt->isClosed()) {
            return $attempt;
        }

        $lastTouchedAt = $attempt->updated_at ?? $attempt->created_at ?? now();
        $elapsed = max(0, $lastTouchedAt->diffInSeconds(now()));
        if ($elapsed <= 0) {
            return $attempt;
        }

        $attempt->remaining_seconds = max(0, $attempt->remaining_seconds - $elapsed);
        if ($attempt->remaining_seconds === 0 && $attempt->status === ExamAttempt::STATUS_IN_PROGRESS) {
            $attempt->status = ExamAttempt::STATUS_EXPIRED;
            $attempt->submitted_at = now();
        }

        $attempt->save();

        return $attempt->refresh();
    }

    public function getPartAssignments(ExamAttempt $attempt, ExamPart $part): array
    {
        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        return $answer?->answer_json['assignments'] ?? [];
    }

    public function getPartChoices(ExamAttempt $attempt, ExamPart $part): array
    {
        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        return $answer?->answer_json['choices'] ?? [];
    }

    public function getSituationAssignments(ExamAttempt $attempt, ExamPart $part): array
    {
        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        return $answer?->answer_json['situation_assignments'] ?? [];
    }

    public function getSprachGapChoices(ExamAttempt $attempt, ExamPart $part): array
    {
        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        return $answer?->answer_json['gap_choices'] ?? [];
    }

    public function getSprachPoolAssignments(ExamAttempt $attempt, ExamPart $part): array
    {
        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        return $answer?->answer_json['pool_assignments'] ?? [];
    }

    public function getHoerenTrueFalseChoices(ExamAttempt $attempt, ExamPart $part): array
    {
        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        return $answer?->answer_json['tf_choices'] ?? [];
    }

    public function getWritingResponse(ExamAttempt $attempt, ExamPart $part): array
    {
        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        return $answer?->answer_json['writing_response'] ?? [];
    }

    public function saveMatchingAssignments(ExamAttempt $attempt, ExamPart $part, array $payload): AttemptAnswer
    {
        $this->syncRemainingSeconds($attempt);
        $attempt->refresh();
        $this->assertAttemptWritable($attempt);
        $this->assertSupportedPartType($part, ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS);

        $assignments = $payload['assignments'] ?? [];
        $textIds = $part->lesenMatchingTexts()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $optionIds = $part->lesenMatchingOptions()->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $assignments = collect($assignments)
            ->filter(function ($optionId, $textId) use ($textIds, $optionIds): bool {
                $textId = (int) $textId;
                if (! in_array($textId, $textIds, true)) {
                    return false;
                }

                return is_null($optionId) || in_array((int) $optionId, $optionIds, true);
            })
            ->mapWithKeys(fn ($optionId, $textId) => [(int) $textId => is_null($optionId) ? null : (int) $optionId])
            ->toArray();

        $usedOptionIds = array_filter($assignments, static fn ($value): bool => ! is_null($value));
        if (count($usedOptionIds) !== count(array_unique($usedOptionIds))) {
            throw ValidationException::withMessages(['answer_json' => 'One title cannot be assigned to multiple texts.']);
        }

        return DB::transaction(function () use ($attempt, $part, $assignments): AttemptAnswer {
            $answer = AttemptAnswer::query()->updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'exam_part_id' => $part->id,
                    'question_reference_type' => null,
                    'question_reference_id' => null,
                ],
                [
                    'answer_json' => ['assignments' => $assignments],
                ]
            );

            $attempt->touch();

            return $answer;
        });
    }

    public function saveReadingMcqChoices(ExamAttempt $attempt, ExamPart $part, array $payload): AttemptAnswer
    {
        $this->syncRemainingSeconds($attempt);
        $attempt->refresh();
        $this->assertAttemptWritable($attempt);
        $this->assertSupportedPartType($part, ExamPart::TYPE_READING_TEXT_MCQ);

        $choices = $payload['choices'] ?? [];
        $questionIds = $part->lesenMcqQuestions()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $validOptionIds = $part->lesenMcqQuestions()->with('options:id,lesen_mcq_question_id')->get()
            ->mapWithKeys(function ($question) {
                return [(int) $question->id => $question->options->pluck('id')->map(static fn ($id): int => (int) $id)->all()];
            })
            ->toArray();

        $choices = collect($choices)
            ->filter(function ($optionId, $questionId) use ($questionIds, $validOptionIds): bool {
                $questionId = (int) $questionId;
                if (! in_array($questionId, $questionIds, true)) {
                    return false;
                }

                return is_null($optionId) || in_array((int) $optionId, $validOptionIds[$questionId] ?? [], true);
            })
            ->mapWithKeys(fn ($optionId, $questionId) => [(int) $questionId => is_null($optionId) ? null : (int) $optionId])
            ->toArray();

        return DB::transaction(function () use ($attempt, $part, $choices): AttemptAnswer {
            $answer = AttemptAnswer::query()->updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'exam_part_id' => $part->id,
                    'question_reference_type' => null,
                    'question_reference_id' => null,
                ],
                [
                    'answer_json' => ['choices' => $choices],
                ]
            );
            $attempt->touch();

            return $answer;
        });
    }

    public function saveSituationAssignments(ExamAttempt $attempt, ExamPart $part, array $payload): AttemptAnswer
    {
        $this->syncRemainingSeconds($attempt);
        $attempt->refresh();
        $this->assertAttemptWritable($attempt);
        $this->assertSupportedPartType($part, ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X);

        $assignments = $payload['situation_assignments'] ?? [];
        $situationIds = $part->lesenSituations()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $adIds = $part->lesenSituationAds()->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $assignments = collect($assignments)
            ->filter(function ($value, $situationId) use ($situationIds, $adIds): bool {
                $situationId = (int) $situationId;
                if (! in_array($situationId, $situationIds, true)) {
                    return false;
                }
                if ($value === 'X' || $value === null || $value === '') {
                    return true;
                }

                return in_array((int) $value, $adIds, true);
            })
            ->mapWithKeys(function ($value, $situationId) {
                $normalized = $value;
                if ($value !== 'X' && $value !== null && $value !== '') {
                    $normalized = (int) $value;
                }

                return [(int) $situationId => $normalized];
            })
            ->toArray();

        $usedAds = array_filter($assignments, static fn ($v) => ! in_array($v, [null, '', 'X'], true));
        if (count($usedAds) !== count(array_unique($usedAds))) {
            throw ValidationException::withMessages(['answer_json' => 'Each Anzeige can be used only once.']);
        }

        return DB::transaction(function () use ($attempt, $part, $assignments): AttemptAnswer {
            $answer = AttemptAnswer::query()->updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'exam_part_id' => $part->id,
                    'question_reference_type' => null,
                    'question_reference_id' => null,
                ],
                [
                    'answer_json' => ['situation_assignments' => $assignments],
                ]
            );
            $attempt->touch();

            return $answer;
        });
    }

    public function saveSprachGapChoices(ExamAttempt $attempt, ExamPart $part, array $payload): AttemptAnswer
    {
        $this->syncRemainingSeconds($attempt);
        $attempt->refresh();
        $this->assertAttemptWritable($attempt);
        $this->assertSupportedPartType($part, ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ);

        $choices = $payload['gap_choices'] ?? [];
        $questionIds = $part->sprachGapQuestions()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $validOptionIds = $part->sprachGapQuestions()->with('options:id,sprach_gap_question_id')->get()
            ->mapWithKeys(function ($question) {
                return [(int) $question->id => $question->options->pluck('id')->map(static fn ($id): int => (int) $id)->all()];
            })
            ->toArray();

        $choices = collect($choices)
            ->filter(function ($optionId, $questionId) use ($questionIds, $validOptionIds): bool {
                $questionId = (int) $questionId;
                if (! in_array($questionId, $questionIds, true)) {
                    return false;
                }

                return is_null($optionId) || in_array((int) $optionId, $validOptionIds[$questionId] ?? [], true);
            })
            ->mapWithKeys(fn ($optionId, $questionId) => [(int) $questionId => is_null($optionId) ? null : (int) $optionId])
            ->toArray();

        return DB::transaction(function () use ($attempt, $part, $choices): AttemptAnswer {
            $answer = AttemptAnswer::query()->updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'exam_part_id' => $part->id,
                    'question_reference_type' => null,
                    'question_reference_id' => null,
                ],
                [
                    'answer_json' => ['gap_choices' => $choices],
                ]
            );
            $attempt->touch();

            return $answer;
        });
    }

    public function saveSprachPoolAssignments(ExamAttempt $attempt, ExamPart $part, array $payload): AttemptAnswer
    {
        $this->syncRemainingSeconds($attempt);
        $attempt->refresh();
        $this->assertAttemptWritable($attempt);
        $this->assertSupportedPartType($part, ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH);

        $assignments = $payload['pool_assignments'] ?? [];
        $gapIds = $part->sprachPoolGaps()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $optionIds = $part->sprachPoolOptions()->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $assignments = collect($assignments)
            ->filter(function ($optionId, $gapId) use ($gapIds, $optionIds): bool {
                $gapId = (int) $gapId;
                if (! in_array($gapId, $gapIds, true)) {
                    return false;
                }

                return is_null($optionId) || in_array((int) $optionId, $optionIds, true);
            })
            ->mapWithKeys(fn ($optionId, $gapId) => [(int) $gapId => is_null($optionId) ? null : (int) $optionId])
            ->toArray();

        $usedOptionIds = array_filter($assignments, static fn ($value): bool => ! is_null($value));
        if (count($usedOptionIds) !== count(array_unique($usedOptionIds))) {
            throw ValidationException::withMessages(['answer_json' => 'One option cannot be assigned to multiple gaps.']);
        }

        return DB::transaction(function () use ($attempt, $part, $assignments): AttemptAnswer {
            $answer = AttemptAnswer::query()->updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'exam_part_id' => $part->id,
                    'question_reference_type' => null,
                    'question_reference_id' => null,
                ],
                [
                    'answer_json' => ['pool_assignments' => $assignments],
                ]
            );

            $attempt->touch();

            return $answer;
        });
    }

    public function saveHoerenTrueFalseChoices(ExamAttempt $attempt, ExamPart $part, array $payload): AttemptAnswer
    {
        $this->syncHoerenRemainingSeconds($attempt);
        $attempt->refresh();
        $this->assertAttemptWritable($attempt, ExamSection::TYPE_HOEREN);
        $this->assertSupportedPartType($part, ExamPart::TYPE_HOEREN_TRUE_FALSE);

        $choices = $payload['tf_choices'] ?? [];
        $questionIds = $part->hoerenTrueFalseQuestions()->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $choices = collect($choices)
            ->filter(function ($value, $questionId) use ($questionIds): bool {
                $questionId = (int) $questionId;
                if (! in_array($questionId, $questionIds, true)) {
                    return false;
                }

                return in_array($value, ['true', 'false', null], true);
            })
            ->mapWithKeys(fn ($value, $questionId) => [(int) $questionId => $value])
            ->toArray();

        return DB::transaction(function () use ($attempt, $part, $choices): AttemptAnswer {
            $answer = AttemptAnswer::query()->updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'exam_part_id' => $part->id,
                    'question_reference_type' => null,
                    'question_reference_id' => null,
                ],
                [
                    'answer_json' => ['tf_choices' => $choices],
                ]
            );

            return $answer;
        });
    }

    public function saveWritingResponse(ExamAttempt $attempt, ExamPart $part, array $payload): AttemptAnswer
    {
        $this->syncSchreibenRemainingSeconds($attempt);
        $attempt->refresh();
        $this->assertAttemptWritable($attempt, ExamSection::TYPE_SCHREIBEN);
        $this->assertSupportedPartType($part, ExamPart::TYPE_WRITING_TASK);

        $response = $payload['writing_response'] ?? [];
        $text = trim((string) ($response['text'] ?? ''));
        $selectedTaskKey = $response['selected_task_key'] ?? null;

        if (! is_null($selectedTaskKey) && ! is_string($selectedTaskKey)) {
            throw ValidationException::withMessages(['answer_json' => 'Invalid selected task key.']);
        }

        if (mb_strlen($text) > 20000) {
            throw ValidationException::withMessages(['answer_json' => 'Writing text is too long.']);
        }

        return DB::transaction(function () use ($attempt, $part, $text, $selectedTaskKey): AttemptAnswer {
            return AttemptAnswer::query()->updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'exam_part_id' => $part->id,
                    'question_reference_type' => null,
                    'question_reference_id' => null,
                ],
                [
                    'answer_json' => [
                        'writing_response' => [
                            'selected_task_key' => $selectedTaskKey,
                            'text' => $text,
                        ],
                    ],
                ]
            );
        });
    }

    public function submitAttempt(ExamAttempt $attempt, bool $forceExpired = false): array
    {
        $attempt->refresh()->load('exam.sections.parts');

        if ($attempt->isClosed() && $attempt->submitted_at !== null) {
            return $this->buildSummary($attempt);
        }

        $result = DB::transaction(function () use ($attempt, $forceExpired): array {
            $parts = $attempt->exam->sections->flatMap->parts;
            $partScores = [];
            $totalEarned = 0;
            $totalPossible = 0;

            foreach ($parts as $part) {
                if ($part->part_type === ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS) {
                    $partResult = $this->scoreMatchingPart($attempt, $part);
                    $partScores[] = $partResult;
                    $totalEarned += $partResult['earned'];
                    $totalPossible += $partResult['possible'];
                    continue;
                }
                if ($part->part_type === ExamPart::TYPE_READING_TEXT_MCQ) {
                    $partResult = $this->scoreReadingTextMcqPart($attempt, $part);
                    $partScores[] = $partResult;
                    $totalEarned += $partResult['earned'];
                    $totalPossible += $partResult['possible'];
                    continue;
                }
                if ($part->part_type === ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X) {
                    $partResult = $this->scoreSituationAdsPart($attempt, $part);
                    $partScores[] = $partResult;
                    $totalEarned += $partResult['earned'];
                    $totalPossible += $partResult['possible'];
                    continue;
                }
                if ($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ) {
                    $partResult = $this->scoreSprachGapPart($attempt, $part);
                    $partScores[] = $partResult;
                    $totalEarned += $partResult['earned'];
                    $totalPossible += $partResult['possible'];
                    continue;
                }
                if ($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH) {
                    $partResult = $this->scoreSprachPoolPart($attempt, $part);
                    $partScores[] = $partResult;
                    $totalEarned += $partResult['earned'];
                    $totalPossible += $partResult['possible'];
                    continue;
                }
                if ($part->part_type === ExamPart::TYPE_HOEREN_TRUE_FALSE) {
                    $partResult = $this->scoreHoerenTrueFalsePart($attempt, $part);
                    $partScores[] = $partResult;
                    $totalEarned += $partResult['earned'];
                    $totalPossible += $partResult['possible'];
                    continue;
                }

                $partScores[] = [
                    'part_id' => $part->id,
                    'part_title' => $part->title,
                    'earned' => 0,
                    'possible' => (int) $part->points,
                ];
                $totalPossible += (int) $part->points;
            }

            $attempt->status = $forceExpired || $attempt->remaining_seconds === 0
                ? ExamAttempt::STATUS_EXPIRED
                : ExamAttempt::STATUS_SUBMITTED;
            $attempt->submitted_at = Carbon::now();
            $attempt->save();

            return [
                'attempt_id' => $attempt->id,
                'status' => $attempt->status,
                'remaining_seconds' => $attempt->remaining_seconds,
                'total_earned' => $totalEarned,
                'total_possible' => $totalPossible,
                'part_scores' => $partScores,
            ];
        });

        return $result;
    }

    private function scoreReadingTextMcqPart(ExamAttempt $attempt, ExamPart $part): array
    {
        $questions = $part->lesenMcqQuestions()->with('options')->get();

        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        $choices = $answer?->answer_json['choices'] ?? [];
        $correct = 0;
        foreach ($questions as $question) {
            $correctOptionId = (int) optional($question->options->firstWhere('is_correct', true))->id;
            $givenOptionId = (int) ($choices[$question->id] ?? 0);
            if ($correctOptionId !== 0 && $correctOptionId === $givenOptionId) {
                $correct++;
            }
        }

        $possible = $questions->count();
        if ($answer) {
            $answer->is_correct = $possible > 0 ? $correct === $possible : null;
            $answer->save();
        }

        return [
            'part_id' => $part->id,
            'part_title' => $part->title,
            'earned' => $correct,
            'possible' => $possible,
        ];
    }

    private function scoreSituationAdsPart(ExamAttempt $attempt, ExamPart $part): array
    {
        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();
        $given = $answer?->answer_json['situation_assignments'] ?? [];

        $correctRows = $part->lesenSituationAnswers()->get();
        $correct = 0;
        foreach ($correctRows as $row) {
            $selected = $given[$row->lesen_situation_id] ?? null;
            if ($row->is_no_match) {
                if ($selected === 'X') {
                    $correct++;
                }
                continue;
            }

            if ((int) $selected === (int) $row->correct_ad_id) {
                $correct++;
            }
        }

        $possible = $correctRows->count();
        if ($answer) {
            $answer->is_correct = $possible > 0 ? $correct === $possible : null;
            $answer->save();
        }

        return [
            'part_id' => $part->id,
            'part_title' => $part->title,
            'earned' => $correct,
            'possible' => $possible,
        ];
    }

    private function scoreMatchingPart(ExamAttempt $attempt, ExamPart $part): array
    {
        $texts = $part->lesenMatchingTexts()->pluck('id')->all();
        $correctMap = $part->lesenMatchingAnswers()
            ->pluck('correct_option_id', 'lesen_matching_text_id')
            ->toArray();

        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        $assignments = $answer?->answer_json['assignments'] ?? [];
        $correct = 0;
        foreach ($texts as $textId) {
            $expected = (int) ($correctMap[$textId] ?? 0);
            $given = (int) ($assignments[$textId] ?? 0);
            if ($expected !== 0 && $expected === $given) {
                $correct++;
            }
        }

        $possible = count($texts);
        if ($answer) {
            $answer->is_correct = $possible > 0 ? $correct === $possible : null;
            $answer->save();
        }

        return [
            'part_id' => $part->id,
            'part_title' => $part->title,
            'earned' => $correct,
            'possible' => $possible,
        ];
    }

    private function scoreSprachGapPart(ExamAttempt $attempt, ExamPart $part): array
    {
        $questions = $part->sprachGapQuestions()->with('options')->get();

        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        $choices = $answer?->answer_json['gap_choices'] ?? [];
        $correct = 0;
        foreach ($questions as $question) {
            $correctOptionId = (int) optional($question->options->firstWhere('is_correct', true))->id;
            $givenOptionId = (int) ($choices[$question->id] ?? 0);
            if ($correctOptionId !== 0 && $correctOptionId === $givenOptionId) {
                $correct++;
            }
        }

        $possible = $questions->count();
        if ($answer) {
            $answer->is_correct = $possible > 0 ? $correct === $possible : null;
            $answer->save();
        }

        return [
            'part_id' => $part->id,
            'part_title' => $part->title,
            'earned' => $correct,
            'possible' => $possible,
        ];
    }

    private function scoreSprachPoolPart(ExamAttempt $attempt, ExamPart $part): array
    {
        $gaps = $part->sprachPoolGaps()->pluck('id')->all();
        $correctMap = $part->sprachPoolAnswers()
            ->pluck('correct_option_id', 'sprach_pool_gap_id')
            ->toArray();

        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();

        $assignments = $answer?->answer_json['pool_assignments'] ?? [];
        $correct = 0;
        foreach ($gaps as $gapId) {
            $expected = (int) ($correctMap[$gapId] ?? 0);
            $given = (int) ($assignments[$gapId] ?? 0);
            if ($expected !== 0 && $expected === $given) {
                $correct++;
            }
        }

        $possible = count($gaps);
        if ($answer) {
            $answer->is_correct = $possible > 0 ? $correct === $possible : null;
            $answer->save();
        }

        return [
            'part_id' => $part->id,
            'part_title' => $part->title,
            'earned' => $correct,
            'possible' => $possible,
        ];
    }

    private function scoreHoerenTrueFalsePart(ExamAttempt $attempt, ExamPart $part): array
    {
        $questions = $part->hoerenTrueFalseQuestions()->get();
        $answer = $attempt->answers()
            ->where('exam_part_id', $part->id)
            ->whereNull('question_reference_type')
            ->whereNull('question_reference_id')
            ->first();
        $choices = $answer?->answer_json['tf_choices'] ?? [];

        $correct = 0;
        foreach ($questions as $question) {
            $selected = $choices[$question->id] ?? null;
            if ($selected === null) {
                continue;
            }
            $isTrue = $selected === 'true';
            if ($isTrue === (bool) $question->is_true_correct) {
                $correct++;
            }
        }

        $possible = $questions->count();
        if ($answer) {
            $answer->is_correct = $possible > 0 ? $correct === $possible : null;
            $answer->save();
        }

        return [
            'part_id' => $part->id,
            'part_title' => $part->title,
            'earned' => $correct,
            'possible' => $possible,
        ];
    }

    private function assertAttemptWritable(ExamAttempt $attempt, ?string $sectionType = null): void
    {
        if ($attempt->status !== ExamAttempt::STATUS_IN_PROGRESS) {
            throw ValidationException::withMessages([
                'attempt' => 'Attempt is already closed.',
            ]);
        }

        $remaining = match ($sectionType) {
            ExamSection::TYPE_HOEREN => (int) ($attempt->hoeren_remaining_seconds ?? 0),
            ExamSection::TYPE_SCHREIBEN => (int) ($attempt->schreiben_remaining_seconds ?? 0),
            default => (int) $attempt->remaining_seconds,
        };

        if ($remaining <= 0) {
            $attempt->status = ExamAttempt::STATUS_EXPIRED;
            $attempt->submitted_at = now();
            $attempt->save();
            throw ValidationException::withMessages([
                'attempt' => 'Attempt has expired.',
            ]);
        }
    }

    private function assertSupportedPartType(ExamPart $part, string $expectedType): void
    {
        if ($part->part_type !== $expectedType) {
            throw ValidationException::withMessages([
                'exam_part_id' => 'Unsupported part type for this endpoint.',
            ]);
        }
    }

    private function buildSummary(ExamAttempt $attempt): array
    {
        $attempt->load('exam.sections.parts');
        $scores = [];
        $earned = 0;
        $possible = 0;

        foreach ($attempt->exam->sections->flatMap->parts as $part) {
            $partScore = $part->part_type === ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS
                ? $this->scoreMatchingPart($attempt, $part)
                : ($part->part_type === ExamPart::TYPE_READING_TEXT_MCQ
                    ? $this->scoreReadingTextMcqPart($attempt, $part)
                    : ($part->part_type === ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X
                        ? $this->scoreSituationAdsPart($attempt, $part)
                        : ($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ
                        ? $this->scoreSprachGapPart($attempt, $part)
                            : ($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH
                                ? $this->scoreSprachPoolPart($attempt, $part)
                                : ($part->part_type === ExamPart::TYPE_HOEREN_TRUE_FALSE
                                    ? $this->scoreHoerenTrueFalsePart($attempt, $part)
                        : [
                            'part_id' => $part->id,
                            'part_title' => $part->title,
                            'earned' => 0,
                            'possible' => (int) $part->points,
                        ])))));

            $scores[] = $partScore;
            $earned += $partScore['earned'];
            $possible += $partScore['possible'];
        }

        return [
            'attempt_id' => $attempt->id,
            'status' => $attempt->status,
            'remaining_seconds' => $attempt->remaining_seconds,
            'total_earned' => $earned,
            'total_possible' => $possible,
            'part_scores' => $scores,
        ];
    }
}
