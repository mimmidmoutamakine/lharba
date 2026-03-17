<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLesenTeil1Request;
use App\Models\ExamPart;
use App\Models\LesenMatchingAnswer;
use App\Models\LesenMatchingOption;
use App\Models\LesenMatchingText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LesenTeil1Controller extends Controller
{
    public function edit(ExamPart $part): View
    {
        abort_unless($part->part_type === ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS, 404);

        $part->load(['section.exam', 'lesenMatchingTexts', 'lesenMatchingOptions', 'lesenMatchingAnswers']);
        $correctByText = $part->lesenMatchingAnswers()
            ->pluck('correct_option_id', 'lesen_matching_text_id')
            ->toArray();

        return view('admin.parts.lesen-teil1', [
            'part' => $part,
            'correctByText' => $correctByText,
        ]);
    }

    public function update(StoreLesenTeil1Request $request, ExamPart $part): RedirectResponse
    {
        abort_unless($part->part_type === ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS, 404);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $part): void {
            $part->update([
                'title' => $validated['title'],
                'instruction_text' => $validated['instruction_text'],
                'points' => $validated['points'],
            ]);

            $textIdMap = [];
            foreach ($validated['texts'] as $textData) {
                $text = isset($textData['id'])
                    ? LesenMatchingText::query()->where('exam_part_id', $part->id)->findOrFail($textData['id'])
                    : new LesenMatchingText(['exam_part_id' => $part->id]);

                $text->fill([
                    'label' => $textData['label'],
                    'body_text' => $textData['body_text'],
                    'sort_order' => $textData['sort_order'],
                ])->save();

                $textIdMap[$textData['label']] = $text->id;
            }

            $optionIdMap = [];
            foreach ($validated['options'] as $optionData) {
                $option = isset($optionData['id'])
                    ? LesenMatchingOption::query()->where('exam_part_id', $part->id)->findOrFail($optionData['id'])
                    : new LesenMatchingOption(['exam_part_id' => $part->id]);

                $option->fill([
                    'option_key' => strtoupper($optionData['option_key']),
                    'option_text' => $optionData['option_text'],
                    'sort_order' => $optionData['sort_order'],
                ])->save();

                $optionIdMap[strtoupper($optionData['option_key'])] = $option->id;
            }

            $textIdsToKeep = array_values($textIdMap);
            $optionIdsToKeep = array_values($optionIdMap);

            LesenMatchingAnswer::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('lesen_matching_text_id', $textIdsToKeep)
                ->delete();
            LesenMatchingText::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('id', $textIdsToKeep)
                ->delete();
            LesenMatchingAnswer::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('correct_option_id', $optionIdsToKeep)
                ->delete();
            LesenMatchingOption::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('id', $optionIdsToKeep)
                ->delete();

            $resolvedAnswers = [];
            foreach ($validated['correct_answers'] as $textLabel => $optionKey) {
                $normalizedOptionKey = strtoupper((string) $optionKey);
                if (! isset($textIdMap[$textLabel], $optionIdMap[$normalizedOptionKey])) {
                    throw ValidationException::withMessages([
                        'correct_answers' => "Invalid correct answer mapping for text {$textLabel}.",
                    ]);
                }

                $resolvedAnswers[] = [
                    'exam_part_id' => $part->id,
                    'lesen_matching_text_id' => $textIdMap[$textLabel],
                    'correct_option_id' => $optionIdMap[$normalizedOptionKey],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $optionIds = array_column($resolvedAnswers, 'correct_option_id');
            if (count($optionIds) !== count(array_unique($optionIds))) {
                throw ValidationException::withMessages([
                    'correct_answers' => 'Each text must map to a unique option.',
                ]);
            }

            LesenMatchingAnswer::query()->where('exam_part_id', $part->id)->delete();
            LesenMatchingAnswer::query()->insert($resolvedAnswers);
        });

        return back()->with('status', 'Lesen Teil 1 content saved.');
    }
}
