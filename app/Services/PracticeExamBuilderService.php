<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamPart;
use App\Models\ExamSection;
use App\Models\PartBankItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        /** @var Exam $exam */
        $exam = DB::transaction(function () use ($items, $title, $level, $duration): Exam {
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
                        'title' => $sectionTitle[$item->section_type] ?? ucfirst($item->section_type),
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
                    'title' => $item->part_title,
                    'instruction_text' => $item->instruction_text,
                    'part_type' => $item->part_type,
                    'points' => $item->points,
                    'sort_order' => $nextSort,
                    'config_json' => $item->config_json,
                ]);

                $this->partContentSyncService->replaceContent($part, $item->content_json ?? []);
            }

            return $exam;
        });

        return $exam;
    }
}
