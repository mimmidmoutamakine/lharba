<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSprachbausteineTeil1Request;
use App\Models\ExamPart;
use App\Models\SprachGapOption;
use App\Models\SprachGapQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SprachbausteineTeil1Controller extends Controller
{
    public function edit(ExamPart $part): View
    {
        abort_unless($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ, 404);
        $part->load(['section.exam', 'sprachGapPassages', 'sprachGapQuestions.options']);

        return view('admin.parts.sprachbausteine-teil1', [
            'part' => $part,
            'passage' => $part->sprachGapPassages->sortBy('sort_order')->first(),
            'questions' => $part->sprachGapQuestions->sortBy('sort_order')->values(),
        ]);
    }

    public function update(StoreSprachbausteineTeil1Request $request, ExamPart $part): RedirectResponse
    {
        abort_unless($part->part_type === ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ, 404);
        $validated = $request->validated();
        $gapNumbers = collect($validated['questions'])->pluck('gap_number')->map(static fn ($n): int => (int) $n);
        if ($gapNumbers->unique()->count() !== $gapNumbers->count()) {
            throw ValidationException::withMessages([
                'questions' => 'Each gap number can appear only once.',
            ]);
        }

        DB::transaction(function () use ($part, $validated): void {
            $part->update([
                'title' => $validated['title'],
                'instruction_text' => $validated['instruction_text'],
                'points' => $validated['points'],
            ]);

            $passage = $part->sprachGapPassages()->firstOrNew(['sort_order' => 1]);
            $passage->fill([
                'title' => $validated['passage']['title'] ?? null,
                'body_text' => $validated['passage']['body_text'],
                'sort_order' => 1,
            ])->save();

            $questionIds = [];
            foreach ($validated['questions'] as $questionIndex => $questionData) {
                $question = $part->sprachGapQuestions()->updateOrCreate(
                    ['sort_order' => $questionIndex + 1],
                    ['gap_number' => (int) $questionData['gap_number']]
                );
                $questionIds[] = $question->id;

                $optionRows = [];
                foreach ($questionData['options'] as $optionIndex => $optionData) {
                    $key = strtoupper((string) $optionData['option_key']);
                    $optionRows[] = [
                        'sprach_gap_question_id' => $question->id,
                        'option_key' => $key,
                        'option_text' => $optionData['option_text'],
                        'is_correct' => $key === strtoupper((string) $questionData['correct_option_key']),
                        'sort_order' => $optionIndex + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $correctCount = collect($optionRows)->where('is_correct', true)->count();
                if ($correctCount !== 1) {
                    throw ValidationException::withMessages([
                        'questions' => 'Each gap must have exactly one correct option.',
                    ]);
                }

                SprachGapOption::query()->where('sprach_gap_question_id', $question->id)->delete();
                SprachGapOption::query()->insert($optionRows);
            }

            SprachGapQuestion::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('id', $questionIds)
                ->delete();
        });

        return back()->with('status', 'Sprachbausteine Teil 1 content saved.');
    }
}
