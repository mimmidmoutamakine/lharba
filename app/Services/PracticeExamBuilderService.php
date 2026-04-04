<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamPart;
use App\Models\ExamSection;
use App\Models\PartBankItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PracticeExamBuilderService
{
    public function __construct(private readonly PartContentSyncService $partContentSyncService)
    {
    }

    /**
     * @param Collection<int,PartBankItem>|array<int,PartBankItem> $bankItems
     */
    public function createFromBankItems(User $user, Collection|array $bankItems, string $modeLabel): Exam
    {
        $items = $bankItems instanceof Collection ? $bankItems->values() : collect($bankItems)->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'training' => 'No models available for this training selection.',
            ]);
        }

        $level = strtolower((string) ($items->first()->level ?? 'b2'));
        $duration = max(20, min(120, $items->count() * 12));
        $title = '['.$modeLabel.'] '.now()->format('Y-m-d H:i:s').' - '.$user->name;

        return DB::transaction(function () use ($items, $title, $level, $duration): Exam {
            $exam = Exam::query()->create([
                'title' => $title,
                'level' => $level,
                'total_duration_minutes' => $duration,
                'is_published' => true,
            ]);

            $sectionOrder = [
                ExamSection::TYPE_LESEN => 1,
                ExamSection::TYPE_SPRACHBAUSTEINE => 2,
                ExamSection::TYPE_HOEREN => 3,
                ExamSection::TYPE_SCHREIBEN => 4,
            ];

            $sectionTitle = [
                ExamSection::TYPE_LESEN => 'Leseverstehen',
                ExamSection::TYPE_SPRACHBAUSTEINE => 'Sprachbausteine',
                ExamSection::TYPE_HOEREN => 'Horverstehen',
                ExamSection::TYPE_SCHREIBEN => 'Schreiben',
            ];

            $sections = [];
            foreach ($items as $item) {
                if (! isset($sections[$item->section_type])) {
                    $sections[$item->section_type] = $exam->sections()->create([
                        'type' => $item->section_type,
                        'title' => $sectionTitle[$item->section_type] ?? ucfirst((string) $item->section_type),
                        'sort_order' => $sectionOrder[$item->section_type] ?? 99,
                    ]);
                }
            }

            $sectionPartOrder = [];

            foreach ($items as $item) {
                $section = $sections[$item->section_type];
                $nextSort = ($sectionPartOrder[$section->id] ?? 0) + 1;
                $sectionPartOrder[$section->id] = $nextSort;

                $part = $section->parts()->create([
                    'part_bank_item_id' => $item->id,
                    'title' => $item->part_title ?: $item->title,
                    'slug' => Str::slug(($item->part_title ?: $item->title).'-'.$item->id.'-'.$nextSort),
                    'instruction_text' => $item->instruction_text,
                    'part_type' => $item->part_type,
                    'schema_version' => 'v2',
                    'entry_mode' => 'normalized',
                    'points' => $item->points,
                    'sort_order' => $nextSort,
                    'config_json' => $item->config_json,
                    'meta_json' => [
                        'source' => 'part_bank_items',
                        'bank_item_id' => $item->id,
                        'bank_title' => $item->title,
                        'section_type' => $item->section_type,
                        'level' => $item->level,
                    ],
                ]);

                $rawContent = $item->content_json ?? [];

                $payload = [
                    'examId' => 'bank-item-'.$item->id,
                    'examTitle' => $item->title,
                    'arabic_title' => null,
                    'level' => strtoupper((string) $item->level),
                    'pro' => false,
                    'visibility' => 'public',
                    'order' => $nextSort,
                    'partId' => $item->part_type,
                    'partTitle' => $item->part_title ?: $item->title,
                    'partSubtitle' => null,
                    'maxPoints' => (int) $item->points,
                    'weight' => 1,
                    'note' => null,
                    'modifiedVersions' => [],
                ] + $this->normalizeLegacyContentForSync($item->part_type, $rawContent);

                $this->partContentSyncService->replaceContent($part, $payload);
            }

            return $exam;
        });
    }

    private function normalizeLegacyContentForSync(string $partType, array $content): array
    {
        return match ($partType) {
            ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS => $this->normalizeLesenTeil1($content),
            ExamPart::TYPE_READING_TEXT_MCQ => $this->normalizeLesenTeil2($content),
            ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X => $this->normalizeLesenTeil3($content),
            ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ => $this->normalizeSprachTeil1($content),
            ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH => $this->normalizeSprachTeil2($content),
            ExamPart::TYPE_HOEREN_TRUE_FALSE => $this->normalizeHoerenTeil1($content),
            default => $content,
        };
    }

    private function normalizeLesenTeil1(array $content): array
    {
        $headlines = collect((array) ($content['options'] ?? []))
            ->map(fn ($row, $i) => [
                'id' => strtolower((string) ($row['option_key'] ?? chr(97 + $i))),
                'text' => (string) ($row['option_text'] ?? ''),
                'highlights' => [],
            ])
            ->values()
            ->all();

        $texts = collect((array) ($content['texts'] ?? []))
            ->map(fn ($row, $i) => [
                'id' => (string) ($row['label'] ?? ($i + 1)),
                'content' => (string) ($row['body_text'] ?? ''),
                'summary' => (string) ($row['summary'] ?? ''),
                'highlights' => (array) ($row['highlights'] ?? []),
            ])
            ->values()
            ->all();

        $correctAnswers = [];
        foreach ((array) ($content['correct_answers'] ?? []) as $row) {
            $textLabel = (string) ($row['text_label'] ?? '');
            $optionKey = strtolower((string) ($row['option_key'] ?? ''));
            if ($textLabel !== '' && $optionKey !== '') {
                $correctAnswers[$textLabel] = $optionKey;
            }
        }

        return [
            'textTitle' => '',
            'headlines' => $headlines,
            'texts' => $texts,
            'correctAnswers' => $correctAnswers,
        ];
    }

    private function normalizeLesenTeil2(array $content): array
    {
        $questions = collect((array) ($content['questions'] ?? []))
            ->map(function ($row, $i) {
                $options = collect((array) ($row['options'] ?? []))->values();

                $correctIndex = $options->search(function ($option) {
                    return (bool) ($option['is_correct'] ?? false) === true;
                });

                return [
                    'id' => $i + 1,
                    'text' => (string) ($row['question_text'] ?? ''),
                    'options' => $options->map(fn ($opt) => (string) ($opt['option_text'] ?? ''))->all(),
                    'correct' => $correctIndex === false ? -1 : $correctIndex,
                ];
            })
            ->values()
            ->all();

        return [
            'textTitle' => (string) ($content['passage']['title'] ?? ''),
            'textContent' => (string) ($content['passage']['body_text'] ?? ''),
            'questions' => $questions,
        ];
    }

    private function normalizeLesenTeil3(array $content): array
    {
        $ads = collect((array) ($content['ads'] ?? []))
            ->map(fn ($row, $i) => [
                'id' => strtolower((string) ($row['label'] ?? chr(97 + $i))),
                'title' => (string) ($row['title'] ?? ''),
                'text' => (string) ($row['body_text'] ?? ''),
                'summary' => null,
                'highlights' => [],
            ])
            ->values()
            ->all();

        $situations = collect((array) ($content['situations'] ?? []))
            ->map(fn ($row, $i) => [
                'id' => (string) ($row['label'] ?? ($i + 1)),
                'text' => (string) ($row['situation_text'] ?? ''),
                'highlights' => [],
            ])
            ->values()
            ->all();

        $correctAnswers = [];
        foreach ((array) ($content['correct_answers'] ?? []) as $row) {
            $situationLabel = (string) ($row['situation_label'] ?? '');
            $adLabel = strtoupper((string) ($row['correct_ad_label'] ?? ''));
            if ($situationLabel !== '' && $adLabel !== '') {
                $correctAnswers[$situationLabel] = strtolower($adLabel) === 'x' ? 'X' : strtolower($adLabel);
            }
        }

        return [
            'textTitle' => '',
            'ads' => $ads,
            'situations' => $situations,
            'correctAnswers' => $correctAnswers,
        ];
    }

    private function normalizeSprachTeil1(array $content): array
    {
        if (isset($content['segments']) && is_array($content['segments'])) {
            $questions = [];
            $bodyText = '';
            $gapNumber = 1;

            foreach ($content['segments'] as $segment) {
                if (is_string($segment)) {
                    $bodyText .= $segment;
                    continue;
                }

                if (! is_array($segment)) {
                    continue;
                }

                $bodyText .= '[['.$gapNumber.']]';

                $questions[] = [
                    'id' => (int) ($segment['id'] ?? $gapNumber),
                    'gap_number' => $gapNumber,
                    'text' => '',
                    'options' => array_values((array) ($segment['options'] ?? [])),
                    'correct' => collect((array) ($segment['options'] ?? []))
                        ->search((string) ($segment['correct'] ?? '')),
                    'explanation' => (string) ($segment['explanation'] ?? ''),
                ];

                $gapNumber++;
            }

            return [
                'textTitle' => (string) ($content['textTitle'] ?? ''),
                'textContent' => $bodyText,
                'questions' => $questions,
                'note' => (string) ($content['note'] ?? ''),
                'modifiedVersions' => (array) ($content['modifiedVersions'] ?? []),
            ];
        }

        $questions = collect((array) ($content['questions'] ?? []))
            ->map(function ($row, $i) {
                $options = collect((array) ($row['options'] ?? []))->values();

                $correctIndex = $options->search(function ($option) {
                    return (bool) ($option['is_correct'] ?? false) === true;
                });

                return [
                    'id' => $i + 1,
                    'gap_number' => (int) ($row['gap_number'] ?? ($i + 1)),
                    'text' => '',
                    'options' => $options->map(fn ($opt) => (string) ($opt['option_text'] ?? ''))->all(),
                    'correct' => $correctIndex === false ? -1 : $correctIndex,
                    'explanation' => (string) ($row['explanation'] ?? ''),
                ];
            })
            ->values()
            ->all();

        return [
            'textTitle' => (string) ($content['passage']['title'] ?? ''),
            'textContent' => (string) ($content['passage']['body_text'] ?? ''),
            'questions' => $questions,
            'note' => (string) ($content['passage']['note'] ?? ''),
            'modifiedVersions' => (array) ($content['modified_versions'] ?? []),
        ];
    }

    private function normalizeSprachTeil2(array $content): array
    {
        $options = collect((array) ($content['options'] ?? []))
            ->map(fn ($row, $i) => [
                'id' => strtolower((string) ($row['option_key'] ?? chr(97 + $i))),
                'text' => (string) ($row['option_text'] ?? ''),
            ])
            ->values()
            ->all();

        $gaps = collect((array) ($content['gaps'] ?? []))
            ->map(fn ($row, $i) => [
                'id' => (string) ($row['label'] ?? ($i + 1)),
                'text' => '',
            ])
            ->values()
            ->all();

        $correctAnswers = [];
        foreach ((array) ($content['correct_answers'] ?? []) as $row) {
            $gapLabel = (string) ($row['gap_label'] ?? '');
            $optionKey = strtolower((string) ($row['option_key'] ?? ''));
            if ($gapLabel !== '' && $optionKey !== '') {
                $correctAnswers[$gapLabel] = $optionKey;
            }
        }

        return [
            'textTitle' => (string) ($content['passage']['title'] ?? ''),
            'textContent' => (string) ($content['passage']['body_text'] ?? ''),
            'options' => $options,
            'gaps' => $gaps,
            'correctAnswers' => $correctAnswers,
        ];
    }

    private function normalizeHoerenTeil1(array $content): array
    {
        $questions = collect((array) ($content['questions'] ?? []))
            ->map(fn ($row, $i) => [
                'id' => $i + 1,
                'statement_text' => (string) ($row['statement_text'] ?? ''),
                'is_true_correct' => (bool) ($row['is_true_correct'] ?? false),
            ])
            ->values()
            ->all();

        return [
            'audio_url' => $content['audio_url'] ?? null,
            'audio_duration_seconds' => $content['audio_duration_seconds'] ?? null,
            'questions' => $questions,
        ];
    }
}