<?php

namespace App\Services;

use App\Models\ExamPart;

class PartContentSyncService
{
    public function replaceContent(ExamPart $part, array $content): void
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
                    $questionModel->options()->create([
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
                    $questionModel->options()->create([
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
}

