<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSprachbausteineTeil2Request;
use App\Models\ExamPart;
use App\Models\SprachPoolAnswer;
use App\Models\SprachPoolGap;
use App\Models\SprachPoolOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SprachbausteineTeil2Controller extends Controller
{
    public function edit(ExamPart $part): View
    {
        abort_unless($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH, 404);
        $part->load(['section.exam', 'sprachPoolPassages', 'sprachPoolGaps', 'sprachPoolOptions', 'sprachPoolAnswers']);

        $correctByGap = $part->sprachPoolAnswers()
            ->pluck('correct_option_id', 'sprach_pool_gap_id')
            ->toArray();

        return view('admin.parts.sprachbausteine-teil2', [
            'part' => $part,
            'correctByGap' => $correctByGap,
        ]);
    }

    public function update(StoreSprachbausteineTeil2Request $request, ExamPart $part): RedirectResponse
    {
        abort_unless($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH, 404);
        $validated = $request->validated();

        DB::transaction(function () use ($part, $validated): void {
            $part->update([
                'title' => $validated['title'],
                'instruction_text' => $validated['instruction_text'],
                'points' => $validated['points'],
            ]);

            $passage = $part->sprachPoolPassages()->firstOrNew(['sort_order' => 1]);
            $passage->fill([
                'title' => $validated['passage']['title'] ?? null,
                'body_text' => $validated['passage']['body_text'],
                'sort_order' => 1,
            ])->save();

            $gapIdMap = [];
            foreach ($validated['gaps'] as $gapData) {
                $gap = isset($gapData['id'])
                    ? SprachPoolGap::query()->where('exam_part_id', $part->id)->findOrFail($gapData['id'])
                    : new SprachPoolGap(['exam_part_id' => $part->id]);

                $gap->fill([
                    'label' => (string) $gapData['label'],
                    'sort_order' => (int) $gapData['sort_order'],
                ])->save();

                $gapIdMap[(string) $gapData['label']] = $gap->id;
            }

            $optionIdMap = [];
            foreach ($validated['options'] as $optionData) {
                $option = isset($optionData['id'])
                    ? SprachPoolOption::query()->where('exam_part_id', $part->id)->findOrFail($optionData['id'])
                    : new SprachPoolOption(['exam_part_id' => $part->id]);

                $option->fill([
                    'option_key' => strtoupper((string) $optionData['option_key']),
                    'option_text' => $optionData['option_text'],
                    'sort_order' => (int) $optionData['sort_order'],
                ])->save();

                $optionIdMap[strtoupper((string) $optionData['option_key'])] = $option->id;
            }

            $gapIdsToKeep = array_values($gapIdMap);
            $optionIdsToKeep = array_values($optionIdMap);

            SprachPoolAnswer::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('sprach_pool_gap_id', $gapIdsToKeep)
                ->delete();
            SprachPoolGap::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('id', $gapIdsToKeep)
                ->delete();
            SprachPoolAnswer::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('correct_option_id', $optionIdsToKeep)
                ->delete();
            SprachPoolOption::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('id', $optionIdsToKeep)
                ->delete();

            $rows = [];
            foreach ($validated['correct_answers'] as $gapLabel => $optionKey) {
                $normalizedOptionKey = strtoupper((string) $optionKey);
                if (! isset($gapIdMap[$gapLabel], $optionIdMap[$normalizedOptionKey])) {
                    throw ValidationException::withMessages([
                        'correct_answers' => "Invalid correct answer mapping for gap {$gapLabel}.",
                    ]);
                }

                $rows[] = [
                    'exam_part_id' => $part->id,
                    'sprach_pool_gap_id' => $gapIdMap[$gapLabel],
                    'correct_option_id' => $optionIdMap[$normalizedOptionKey],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $optionIds = array_column($rows, 'correct_option_id');
            if (count($optionIds) !== count(array_unique($optionIds))) {
                throw ValidationException::withMessages([
                    'correct_answers' => 'Each gap must map to a unique option.',
                ]);
            }

            SprachPoolAnswer::query()->where('exam_part_id', $part->id)->delete();
            SprachPoolAnswer::query()->insert($rows);
        });

        return back()->with('status', 'Sprachbausteine Teil 2 content saved.');
    }
}

