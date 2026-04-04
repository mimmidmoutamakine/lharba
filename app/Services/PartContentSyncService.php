<?php

namespace App\Services;

use App\Models\ExamPart;
use Illuminate\Support\Facades\DB;

class PartContentSyncService
{
    public function replaceContent(ExamPart $part, array $content): void
    {
        DB::transaction(function () use ($part, $content): void {
            $part->loadMissing(['section.exam']);

            // normalized
            $part->examPartEntries()->delete();

            $entry = $part->examPartEntries()->create([
                'legacy_part_bank_item_id' => $part->part_bank_item_id,
                'external_exam_id' => $content['examId'] ?? null,
                'external_part_id' => $content['partId'] ?? null,
                'source_label' => $content['source_label'] ?? $content['textTitle'] ?? $part->title,
                'exam_title' => $content['examTitle'] ?? $part->section?->exam?->title,
                'entry_title' => $content['textTitle'] ?? $content['partTitle'] ?? $part->title,
                'arabic_title' => $content['arabic_title'] ?? null,
                'level' => $content['level'] ?? $part->section?->exam?->level,
                'visibility' => $content['visibility'] ?? 'public',
                'is_pro' => (bool) ($content['pro'] ?? false),
                'import_order' => (int) ($content['order'] ?? 1),
                'max_points' => (int) ($content['maxPoints'] ?? $part->points ?? 0),
                'weight' => (float) ($content['weight'] ?? 1),
                'note_text' => $content['note'] ?? null,
                'status' => 'published',
                'meta_json' => [
                    'part_type' => $part->part_type,
                    'part_title' => $content['partTitle'] ?? $part->title,
                    'part_subtitle' => $content['partSubtitle'] ?? null,
                ],
            ]);

            $versions = [];

            $versions[] = [
                'version_name' => 'original',
                'version_kind' => 'original',
                'is_active' => empty($content['modifiedVersions']),
                'source_payload_json' => $content,
                'normalized_payload_json' => null,
            ];

            foreach ((array) ($content['modifiedVersions'] ?? []) as $index => $modified) {
                $versions[] = [
                    'version_name' => 'modified_'.($index + 1),
                    'version_kind' => 'modified',
                    'is_active' => $index === array_key_last((array) ($content['modifiedVersions'] ?? [])),
                    'source_payload_json' => $modified,
                    'normalized_payload_json' => null,
                ];
            }

            foreach ($versions as $versionRow) {
                $version = $entry->versions()->create($versionRow);
                $payload = (array) $versionRow['source_payload_json'];

                match ($part->part_type) {
                    ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS => $this->syncLesenTeil1Normalized($version, $payload),
                    ExamPart::TYPE_READING_TEXT_MCQ => $this->syncLesenTeil2Normalized($version, $payload),
                    ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X => $this->syncLesenTeil3Normalized($version, $payload),
                    ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ => $this->syncSprachTeil1Normalized($version, $payload),
                    ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH => $this->syncSprachTeil2Normalized($version, $payload),
                    ExamPart::TYPE_HOEREN_TRUE_FALSE => $this->syncHoerenTeil1Normalized($version, $payload),
                    default => null,
                };
            }

            // legacy runtime sync
            $legacyContent = $this->toLegacyContent($part->part_type, $content);
            $this->replaceLegacyContent($part, $legacyContent);
        });
    }

    private function toLegacyContent(string $partType, array $content): array
    {
        return match ($partType) {
            ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS => $this->legacyLesenTeil1($content),
            ExamPart::TYPE_READING_TEXT_MCQ => $this->legacyLesenTeil2($content),
            ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X => $this->legacyLesenTeil3($content),
            ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ => $this->legacySprachTeil1($content),
            ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH => $this->legacySprachTeil2($content),
            ExamPart::TYPE_HOEREN_TRUE_FALSE => $this->legacyHoerenTeil1($content),
            default => $content,
        };
    }

    private function legacyLesenTeil1(array $content): array
    {
        if (isset($content['texts'], $content['options'], $content['correct_answers'])) {
            return $content;
        }

        $texts = collect((array) ($content['texts'] ?? []))->map(fn ($row, $i) => [
            'label' => (string) ($row['id'] ?? ($i + 1)),
            'body_text' => (string) ($row['content'] ?? $row['text'] ?? ''),
            'sort_order' => $i + 1,
        ])->values()->all();

        $options = collect((array) ($content['headlines'] ?? []))->map(fn ($row, $i) => [
            'option_key' => strtoupper((string) ($row['id'] ?? chr(97 + $i))),
            'option_text' => (string) ($row['text'] ?? ''),
            'sort_order' => $i + 1,
        ])->values()->all();

        $correctAnswers = [];
        foreach ((array) ($content['correctAnswers'] ?? []) as $textLabel => $optionKey) {
            $correctAnswers[] = [
                'text_label' => (string) $textLabel,
                'option_key' => strtoupper((string) $optionKey),
            ];
        }

        return [
            'texts' => $texts,
            'options' => $options,
            'correct_answers' => $correctAnswers,
        ];
    }

    private function legacyLesenTeil2(array $content): array
    {
        if (isset($content['passage'], $content['questions'])) {
            return $content;
        }

        $questions = collect((array) ($content['questions'] ?? []))->map(function ($row, $i) {
            $options = collect((array) ($row['options'] ?? []))->values()->map(
                fn ($text, $idx) => [
                    'option_key' => chr(65 + $idx),
                    'option_text' => (string) $text,
                    'is_correct' => ((int) ($row['correct'] ?? -1) === $idx),
                    'sort_order' => $idx + 1,
                ]
            )->all();

            return [
                'question_text' => (string) ($row['text'] ?? ''),
                'sort_order' => $i + 1,
                'options' => $options,
            ];
        })->values()->all();

        return [
            'passage' => [
                'title' => (string) ($content['textTitle'] ?? ''),
                'body_text' => (string) ($content['textContent'] ?? ''),
                'sort_order' => 1,
            ],
            'questions' => $questions,
        ];
    }

    private function legacyLesenTeil3(array $content): array
    {
        if (isset($content['ads'], $content['situations'], $content['correct_answers'])) {
            return $content;
        }

        $ads = collect((array) ($content['ads'] ?? []))->map(fn ($row, $i) => [
            'label' => strtoupper((string) ($row['id'] ?? chr(97 + $i))),
            'title' => (string) ($row['title'] ?? ''),
            'body_text' => (string) ($row['text'] ?? ''),
            'sort_order' => $i + 1,
        ])->values()->all();

        $situations = collect((array) ($content['situations'] ?? []))->map(fn ($row, $i) => [
            'label' => (string) ($row['id'] ?? ($i + 1)),
            'situation_text' => (string) ($row['text'] ?? ''),
            'sort_order' => $i + 1,
        ])->values()->all();

        $correctAnswers = [];
        foreach ((array) ($content['correctAnswers'] ?? []) as $situationLabel => $adLabel) {
            $correctAnswers[] = [
                'situation_label' => (string) $situationLabel,
                'correct_ad_label' => strtoupper((string) $adLabel),
            ];
        }

        return [
            'ads' => $ads,
            'situations' => $situations,
            'correct_answers' => $correctAnswers,
        ];
    }

    private function legacySprachTeil1(array $content): array
    {
        if (isset($content['passage'], $content['questions'])) {
            $questions = collect((array) ($content['questions'] ?? []))
                ->map(function ($row, $i) {
                    $options = collect((array) ($row['options'] ?? []))
                        ->values()
                        ->map(function ($option, $idx) use ($row) {
                            if (is_array($option)) {
                                return [
                                    'option_key' => (string) ($option['option_key'] ?? chr(65 + $idx)),
                                    'option_text' => (string) ($option['option_text'] ?? ''),
                                    'is_correct' => (bool) ($option['is_correct'] ?? false),
                                    'sort_order' => (int) ($option['sort_order'] ?? ($idx + 1)),
                                ];
                            }

                            $letter = chr(65 + $idx);

                            return [
                                'option_key' => $letter,
                                'option_text' => (string) $option,
                                'is_correct' => ((int) ($row['correct'] ?? -1) === $idx),
                                'sort_order' => $idx + 1,
                            ];
                        })
                        ->all();

                    return [
                        'gap_number' => (int) ($row['gap_number'] ?? ($i + 1)),
                        'sort_order' => (int) ($row['sort_order'] ?? ($i + 1)),
                        'explanation' => (string) ($row['explanation'] ?? ''),
                        'options' => $options,
                    ];
                })
                ->values()
                ->all();

            return [
                'passage' => [
                    'title' => (string) ($content['passage']['title'] ?? ''),
                    'body_text' => (string) ($content['passage']['body_text'] ?? ''),
                    'note' => (string) ($content['passage']['note'] ?? ''),
                    'sort_order' => 1,
                ],
                'questions' => $questions,
            ];
        }

        $questions = collect((array) ($content['questions'] ?? []))
            ->map(function ($row, $i) {
                $options = collect((array) ($row['options'] ?? []))->values()->map(
                    fn ($text, $idx) => [
                        'option_key' => chr(65 + $idx),
                        'option_text' => (string) $text,
                        'is_correct' => ((int) ($row['correct'] ?? -1) === $idx),
                        'sort_order' => $idx + 1,
                    ]
                )->all();

                return [
                    'gap_number' => (int) ($row['gap_number'] ?? ($i + 1)),
                    'sort_order' => $i + 1,
                    'explanation' => (string) ($row['explanation'] ?? ''),
                    'options' => $options,
                ];
            })
            ->values()
            ->all();

        return [
            'passage' => [
                'title' => (string) ($content['textTitle'] ?? ''),
                'body_text' => (string) ($content['textContent'] ?? ''),
                'note' => (string) ($content['note'] ?? ''),
                'sort_order' => 1,
            ],
            'questions' => $questions,
        ];
    }

    private function legacySprachTeil2(array $content): array
    {
        if (isset($content['passage'], $content['gaps'], $content['options'], $content['correct_answers'])) {
            return $content;
        }

        $options = collect((array) ($content['options'] ?? []))->map(fn ($row, $i) => [
            'option_key' => strtoupper((string) ($row['id'] ?? chr(97 + $i))),
            'option_text' => (string) ($row['text'] ?? ''),
            'sort_order' => $i + 1,
        ])->values()->all();

        $gaps = collect((array) ($content['gaps'] ?? []))->map(fn ($row, $i) => [
            'label' => (string) ($row['id'] ?? ($i + 1)),
            'sort_order' => $i + 1,
        ])->values()->all();

        $correctAnswers = [];
        foreach ((array) ($content['correctAnswers'] ?? []) as $gapLabel => $optionKey) {
            $correctAnswers[] = [
                'gap_label' => (string) $gapLabel,
                'option_key' => strtoupper((string) $optionKey),
            ];
        }

        return [
            'passage' => [
                'title' => (string) ($content['textTitle'] ?? ''),
                'body_text' => (string) ($content['textContent'] ?? ''),
                'sort_order' => 1,
            ],
            'gaps' => $gaps,
            'options' => $options,
            'correct_answers' => $correctAnswers,
        ];
    }

    private function legacyHoerenTeil1(array $content): array
    {
        return [
            'audio_url' => $content['audio_url'] ?? null,
            'audio_duration_seconds' => $content['audio_duration_seconds'] ?? null,
            'questions' => collect((array) ($content['questions'] ?? []))->map(fn ($row, $i) => [
                'statement_text' => (string) ($row['statement_text'] ?? $row['text'] ?? ''),
                'is_true_correct' => (bool) ($row['is_true_correct'] ?? false),
                'sort_order' => $i + 1,
            ])->values()->all(),
        ];
    }

    private function replaceLegacyContent(ExamPart $part, array $content): void
    {
        if ($part->part_type === ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS) {
            $part->lesenMatchingAnswers()->delete();
            $part->lesenMatchingTexts()->delete();
            $part->lesenMatchingOptions()->delete();

            $textMap = [];
            foreach (($content['texts'] ?? []) as $index => $text) {
                $model = $part->lesenMatchingTexts()->create([
                    'label' => (string) ($text['label'] ?? ($index + 1)),
                    'body_text' => (string) ($text['body_text'] ?? ''),
                    'sort_order' => (int) ($text['sort_order'] ?? ($index + 1)),
                ]);
                $textMap[(string) $model->label] = $model->id;
            }

            $optionMap = [];
            foreach (($content['options'] ?? []) as $index => $option) {
                $model = $part->lesenMatchingOptions()->create([
                    'option_key' => (string) ($option['option_key'] ?? ''),
                    'option_text' => (string) ($option['option_text'] ?? ''),
                    'sort_order' => (int) ($option['sort_order'] ?? ($index + 1)),
                ]);
                $optionMap[(string) $model->option_key] = $model->id;
            }

            foreach (($content['correct_answers'] ?? []) as $answer) {
                $textLabel = (string) ($answer['text_label'] ?? '');
                $optionKey = (string) ($answer['option_key'] ?? '');
                if (! isset($textMap[$textLabel], $optionMap[$optionKey])) {
                    continue;
                }
                $part->lesenMatchingAnswers()->create([
                    'lesen_matching_text_id' => $textMap[$textLabel],
                    'correct_option_id' => $optionMap[$optionKey],
                ]);
            }

            return;
        }

        if ($part->part_type === ExamPart::TYPE_READING_TEXT_MCQ) {
            $part->lesenMcqQuestions()->each(fn ($q) => $q->options()->delete());
            $part->lesenMcqQuestions()->delete();
            $part->lesenMcqPassages()->delete();

            if (isset($content['passage']) && is_array($content['passage'])) {
                $part->lesenMcqPassages()->create([
                    'title' => (string) ($content['passage']['title'] ?? ''),
                    'body_text' => (string) ($content['passage']['body_text'] ?? ''),
                    'sort_order' => (int) ($content['passage']['sort_order'] ?? 1),
                ]);
            }

            foreach (($content['questions'] ?? []) as $index => $question) {
                $questionModel = $part->lesenMcqQuestions()->create([
                    'question_text' => (string) ($question['question_text'] ?? ''),
                    'sort_order' => (int) ($question['sort_order'] ?? ($index + 1)),
                ]);
                foreach (($question['options'] ?? []) as $optionIndex => $option) {
                    \App\Models\SprachGapOption::create([
                        'sprach_gap_question_id' => $questionModel->id,
                        'option_key' => (string) ($option['option_key'] ?? ''),
                        'option_text' => (string) ($option['option_text'] ?? ''),
                        'is_correct' => (bool) ($option['is_correct'] ?? false),
                        'sort_order' => (int) ($option['sort_order'] ?? ($optionIndex + 1)),
                    ]);
                }
            }

            return;
        }

        if ($part->part_type === ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X) {
            $part->lesenSituationAnswers()->delete();
            $part->lesenSituations()->delete();
            $part->lesenSituationAds()->delete();

            $adsMap = [];
            foreach (($content['ads'] ?? []) as $index => $ad) {
                $model = $part->lesenSituationAds()->create([
                    'label' => (string) ($ad['label'] ?? ''),
                    'title' => (string) ($ad['title'] ?? ''),
                    'body_text' => (string) ($ad['body_text'] ?? ''),
                    'sort_order' => (int) ($ad['sort_order'] ?? ($index + 1)),
                ]);
                $adsMap[(string) $model->label] = $model->id;
            }

            $situationsMap = [];
            foreach (($content['situations'] ?? []) as $index => $situation) {
                $model = $part->lesenSituations()->create([
                    'label' => (string) ($situation['label'] ?? ($index + 1)),
                    'situation_text' => (string) ($situation['situation_text'] ?? ''),
                    'sort_order' => (int) ($situation['sort_order'] ?? ($index + 1)),
                ]);
                $situationsMap[(string) $model->label] = $model->id;
            }

            foreach (($content['correct_answers'] ?? []) as $answer) {
                $situationLabel = (string) ($answer['situation_label'] ?? '');
                $adLabel = (string) ($answer['correct_ad_label'] ?? '');
                if (! isset($situationsMap[$situationLabel])) {
                    continue;
                }
                $part->lesenSituationAnswers()->create([
                    'lesen_situation_id' => $situationsMap[$situationLabel],
                    'correct_ad_id' => $adLabel === 'X' ? null : ($adsMap[$adLabel] ?? null),
                    'is_no_match' => $adLabel === 'X',
                ]);
            }

            return;
        }

        if ($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ) {
            $part->sprachGapQuestions()->each(fn ($q) => $q->options()->delete());
            $part->sprachGapQuestions()->delete();
            $part->sprachGapPassages()->delete();

            if (isset($content['passage']) && is_array($content['passage'])) {
                $part->sprachGapPassages()->create([
                    'title' => (string) ($content['passage']['title'] ?? ''),
                    'body_text' => (string) ($content['passage']['body_text'] ?? ''),
                    'sort_order' => (int) ($content['passage']['sort_order'] ?? 1),
                ]);
            }

            foreach (($content['questions'] ?? []) as $index => $question) {
                $questionModel = $part->sprachGapQuestions()->create([
                    'gap_number' => (int) ($question['gap_number'] ?? ($index + 1)),
                    'sort_order' => (int) ($question['sort_order'] ?? ($index + 1)),
                ]);
                foreach (($question['options'] ?? []) as $optionIndex => $option) {
                    \App\Models\SprachGapOption::create([
                        'sprach_gap_question_id' => $questionModel->id,
                        'option_key' => (string) ($option['option_key'] ?? ''),
                        'option_text' => (string) ($option['option_text'] ?? ''),
                        'is_correct' => (bool) ($option['is_correct'] ?? false),
                        'sort_order' => (int) ($option['sort_order'] ?? ($optionIndex + 1)),
                    ]);
                }
            }

            return;
        }

        if ($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH) {
            $part->sprachPoolAnswers()->delete();
            $part->sprachPoolGaps()->delete();
            $part->sprachPoolOptions()->delete();
            $part->sprachPoolPassages()->delete();

            if (isset($content['passage']) && is_array($content['passage'])) {
                $part->sprachPoolPassages()->create([
                    'title' => (string) ($content['passage']['title'] ?? ''),
                    'body_text' => (string) ($content['passage']['body_text'] ?? ''),
                    'sort_order' => (int) ($content['passage']['sort_order'] ?? 1),
                ]);
            }

            $gapMap = [];
            foreach (($content['gaps'] ?? []) as $index => $gap) {
                $model = $part->sprachPoolGaps()->create([
                    'label' => (string) ($gap['label'] ?? ($index + 1)),
                    'sort_order' => (int) ($gap['sort_order'] ?? ($index + 1)),
                ]);
                $gapMap[(string) $model->label] = $model->id;
            }

            $optionMap = [];
            foreach (($content['options'] ?? []) as $index => $option) {
                $model = $part->sprachPoolOptions()->create([
                    'option_key' => (string) ($option['option_key'] ?? ''),
                    'option_text' => (string) ($option['option_text'] ?? ''),
                    'sort_order' => (int) ($option['sort_order'] ?? ($index + 1)),
                ]);
                $optionMap[(string) $model->option_key] = $model->id;
            }

            foreach (($content['correct_answers'] ?? []) as $answer) {
                $gapLabel = (string) ($answer['gap_label'] ?? '');
                $optionKey = (string) ($answer['option_key'] ?? '');
                if (! isset($gapMap[$gapLabel], $optionMap[$optionKey])) {
                    continue;
                }
                $part->sprachPoolAnswers()->create([
                    'sprach_pool_gap_id' => $gapMap[$gapLabel],
                    'correct_option_id' => $optionMap[$optionKey],
                ]);
            }

            return;
        }

        if ($part->part_type === ExamPart::TYPE_HOEREN_TRUE_FALSE) {
            $part->hoerenTrueFalseQuestions()->delete();
            foreach (($content['questions'] ?? []) as $index => $question) {
                $part->hoerenTrueFalseQuestions()->create([
                    'statement_text' => (string) ($question['statement_text'] ?? ''),
                    'is_true_correct' => (bool) ($question['is_true_correct'] ?? false),
                    'sort_order' => (int) ($question['sort_order'] ?? ($index + 1)),
                ]);
            }
        }
    }

    private function syncLesenTeil1Normalized($version, array $payload): void
    {
        foreach ((array) ($payload['headlines'] ?? $payload['options'] ?? []) as $index => $headline) {
            $key = 'headline_'.strtolower((string) ($headline['id'] ?? $headline['option_key'] ?? chr(97 + $index)));

            $version->blocks()->create([
                'block_group' => 'headlines',
                'block_type' => 'headline',
                'block_key' => $key,
                'label' => strtoupper((string) ($headline['id'] ?? $headline['option_key'] ?? chr(97 + $index))),
                'title' => null,
                'body_text' => (string) ($headline['text'] ?? $headline['option_text'] ?? ''),
                'extra_json' => [
                    'highlights' => $headline['highlights'] ?? [],
                    'summary' => $headline['summary'] ?? null,
                ],
                'sort_order' => (int) ($headline['sort_order'] ?? ($index + 1)),
            ]);
        }

        foreach ((array) ($payload['texts'] ?? []) as $index => $text) {
            $key = 'text_'.((string) ($text['id'] ?? $text['label'] ?? ($index + 1)));

            $version->blocks()->create([
                'block_group' => 'texts',
                'block_type' => 'text',
                'block_key' => $key,
                'label' => (string) ($text['id'] ?? $text['label'] ?? ($index + 1)),
                'title' => null,
                'body_text' => (string) ($text['content'] ?? $text['body_text'] ?? $text['text'] ?? ''),
                'extra_json' => [
                    'highlights' => $text['highlights'] ?? [],
                    'summary' => $text['summary'] ?? null,
                ],
                'sort_order' => (int) ($text['sort_order'] ?? ($index + 1)),
            ]);
        }

        $answers = (array) ($payload['correctAnswers'] ?? []);
        foreach ($answers as $textLabel => $headlineLabel) {
            $version->mappings()->create([
                'mapping_type' => 'text_to_headline',
                'from_block_key' => 'text_'.$textLabel,
                'to_block_key' => 'headline_'.strtolower((string) $headlineLabel),
                'answer_value' => strtoupper((string) $headlineLabel),
                'is_correct' => true,
                'sort_order' => (int) $textLabel,
            ]);
        }
    }

    private function syncLesenTeil2Normalized($version, array $payload): void
    {
        $version->blocks()->create([
            'block_group' => 'passage',
            'block_type' => 'passage',
            'block_key' => 'passage_main',
            'label' => null,
            'title' => (string) ($payload['textTitle'] ?? $payload['passage']['title'] ?? ''),
            'body_text' => (string) ($payload['textContent'] ?? $payload['passage']['body_text'] ?? ''),
            'extra_json' => null,
            'sort_order' => 1,
        ]);

        foreach ((array) ($payload['questions'] ?? []) as $qIndex => $question) {
            $qKey = 'question_'.((string) ($question['id'] ?? ($qIndex + 1)));

            $version->blocks()->create([
                'block_group' => 'questions',
                'block_type' => 'question',
                'block_key' => $qKey,
                'label' => (string) ($question['id'] ?? ($qIndex + 1)),
                'title' => null,
                'body_text' => (string) ($question['text'] ?? $question['question_text'] ?? ''),
                'extra_json' => [
                    'explanation' => $question['explanation'] ?? null,
                ],
                'sort_order' => $qIndex + 1,
            ]);

            foreach ((array) ($question['options'] ?? []) as $oIndex => $optionText) {
                $optionLetter = chr(65 + $oIndex);
                $oKey = $qKey.'_option_'.strtolower($optionLetter);

                $version->blocks()->create([
                    'block_group' => 'options',
                    'block_type' => 'option',
                    'block_key' => $oKey,
                    'parent_block_key' => $qKey,
                    'label' => $optionLetter,
                    'title' => null,
                    'body_text' => (string) $optionText,
                    'extra_json' => null,
                    'sort_order' => $oIndex + 1,
                ]);

                if ((int) ($question['correct'] ?? -1) === $oIndex) {
                    $version->mappings()->create([
                        'mapping_type' => 'question_to_correct_option',
                        'from_block_key' => $qKey,
                        'to_block_key' => $oKey,
                        'answer_value' => $optionLetter,
                        'is_correct' => true,
                        'sort_order' => $qIndex + 1,
                    ]);
                }
            }
        }
    }

    private function syncLesenTeil3Normalized($version, array $payload): void
    {
        foreach ((array) ($payload['ads'] ?? []) as $index => $ad) {
            $label = strtolower((string) ($ad['id'] ?? $ad['label'] ?? chr(97 + $index)));

            $version->blocks()->create([
                'block_group' => 'ads',
                'block_type' => 'ad',
                'block_key' => 'ad_'.$label,
                'label' => strtoupper($label),
                'title' => (string) ($ad['title'] ?? ''),
                'body_text' => (string) ($ad['text'] ?? $ad['body_text'] ?? ''),
                'extra_json' => [
                    'highlights' => $ad['highlights'] ?? [],
                    'summary' => $ad['summary'] ?? null,
                ],
                'sort_order' => (int) ($ad['sort_order'] ?? ($index + 1)),
            ]);
        }

        foreach ((array) ($payload['situations'] ?? []) as $index => $situation) {
            $label = (string) ($situation['id'] ?? $situation['label'] ?? ($index + 1));

            $version->blocks()->create([
                'block_group' => 'situations',
                'block_type' => 'situation',
                'block_key' => 'situation_'.$label,
                'label' => $label,
                'title' => null,
                'body_text' => (string) ($situation['text'] ?? $situation['situation_text'] ?? ''),
                'extra_json' => [
                    'highlights' => $situation['highlights'] ?? [],
                    'summary' => $situation['summary'] ?? null,
                ],
                'sort_order' => (int) ($situation['sort_order'] ?? ($index + 1)),
            ]);
        }

        foreach ((array) ($payload['correctAnswers'] ?? []) as $situationLabel => $adLabel) {
            $adValue = strtoupper((string) $adLabel);

            $version->mappings()->create([
                'mapping_type' => 'situation_to_ad',
                'from_block_key' => 'situation_'.$situationLabel,
                'to_block_key' => $adValue === 'X' ? null : 'ad_'.strtolower($adValue),
                'answer_value' => $adValue,
                'is_correct' => true,
                'extra_json' => ['is_no_match' => $adValue === 'X'],
                'sort_order' => (int) $situationLabel,
            ]);
        }
    }

    private function syncSprachTeil1Normalized($version, array $payload): void
    {
        $version->blocks()->create([
            'block_group' => 'passage',
            'block_type' => 'passage',
            'block_key' => 'passage_main',
            'title' => (string) ($payload['textTitle'] ?? $payload['passage']['title'] ?? ''),
            'body_text' => (string) ($payload['textContent'] ?? $payload['passage']['body_text'] ?? ''),
            'extra_json' => [
                'note' => (string) ($payload['note'] ?? ''),
            ],
            'sort_order' => 1,
        ]);

        foreach ((array) ($payload['questions'] ?? []) as $qIndex => $question) {
            $qKey = 'gap_'.((string) ($question['id'] ?? $question['gap_number'] ?? ($qIndex + 1)));

            $version->blocks()->create([
                'block_group' => 'gaps',
                'block_type' => 'gap',
                'block_key' => $qKey,
                'label' => (string) ($question['gap_number'] ?? ($qIndex + 1)),
                'body_text' => (string) ($question['text'] ?? ''),
                'extra_json' => [
                    'explanation' => (string) ($question['explanation'] ?? ''),
                ],
                'sort_order' => $qIndex + 1,
            ]);

            foreach ((array) ($question['options'] ?? []) as $oIndex => $optionText) {
                $letter = chr(65 + $oIndex);
                $oKey = $qKey.'_option_'.strtolower($letter);

                $version->blocks()->create([
                    'block_group' => 'options',
                    'block_type' => 'option',
                    'block_key' => $oKey,
                    'parent_block_key' => $qKey,
                    'label' => $letter,
                    'body_text' => (string) $optionText,
                    'sort_order' => $oIndex + 1,
                ]);

                if ((int) ($question['correct'] ?? -1) === $oIndex) {
                    $version->mappings()->create([
                        'mapping_type' => 'gap_to_option',
                        'from_block_key' => $qKey,
                        'to_block_key' => $oKey,
                        'answer_value' => $letter,
                        'is_correct' => true,
                        'sort_order' => $qIndex + 1,
                    ]);
                }
            }
        }
    }

    private function syncSprachTeil2Normalized($version, array $payload): void
    {
        $version->blocks()->create([
            'block_group' => 'passage',
            'block_type' => 'passage',
            'block_key' => 'passage_main',
            'title' => (string) ($payload['textTitle'] ?? $payload['passage']['title'] ?? ''),
            'body_text' => (string) ($payload['textContent'] ?? $payload['passage']['body_text'] ?? ''),
            'sort_order' => 1,
        ]);

        foreach ((array) ($payload['options'] ?? []) as $oIndex => $option) {
            $label = strtolower((string) ($option['id'] ?? $option['option_key'] ?? chr(97 + $oIndex)));

            $version->blocks()->create([
                'block_group' => 'pool_options',
                'block_type' => 'option',
                'block_key' => 'pool_option_'.$label,
                'label' => strtoupper($label),
                'body_text' => (string) ($option['text'] ?? $option['option_text'] ?? ''),
                'sort_order' => $oIndex + 1,
            ]);
        }

        foreach ((array) ($payload['gaps'] ?? []) as $gIndex => $gap) {
            $label = (string) ($gap['id'] ?? $gap['label'] ?? ($gIndex + 1));

            $version->blocks()->create([
                'block_group' => 'gaps',
                'block_type' => 'gap',
                'block_key' => 'gap_'.$label,
                'label' => $label,
                'body_text' => (string) ($gap['text'] ?? ''),
                'sort_order' => $gIndex + 1,
            ]);
        }

        foreach ((array) ($payload['correctAnswers'] ?? []) as $gapLabel => $optionLabel) {
            $version->mappings()->create([
                'mapping_type' => 'gap_to_pool_option',
                'from_block_key' => 'gap_'.$gapLabel,
                'to_block_key' => 'pool_option_'.strtolower((string) $optionLabel),
                'answer_value' => strtoupper((string) $optionLabel),
                'is_correct' => true,
                'sort_order' => (int) $gapLabel,
            ]);
        }
    }

    private function syncHoerenTeil1Normalized($version, array $payload): void
    {
        if (!empty($payload['audio_url'])) {
            $version->assets()->create([
                'asset_type' => 'audio',
                'label' => 'main_audio',
                'path_or_url' => (string) $payload['audio_url'],
                'meta_json' => [
                    'duration_seconds' => $payload['audio_duration_seconds'] ?? null,
                ],
                'sort_order' => 1,
            ]);
        }

        foreach ((array) ($payload['questions'] ?? []) as $index => $question) {
            $qKey = 'question_'.((string) ($question['id'] ?? ($index + 1)));

            $version->blocks()->create([
                'block_group' => 'questions',
                'block_type' => 'true_false_question',
                'block_key' => $qKey,
                'label' => (string) ($index + 1),
                'body_text' => (string) ($question['statement_text'] ?? $question['text'] ?? ''),
                'sort_order' => $index + 1,
            ]);

            $version->mappings()->create([
                'mapping_type' => 'question_to_boolean',
                'from_block_key' => $qKey,
                'answer_value' => !empty($question['is_true_correct']) ? 'true' : 'false',
                'is_correct' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}