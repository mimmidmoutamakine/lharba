<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLesenTeil2Request;
use App\Models\ExamPart;
use App\Models\LesenMcqOption;
use App\Models\LesenMcqPassage;
use App\Models\LesenMcqQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LesenTeil2Controller extends Controller
{
    public function edit(ExamPart $part): View
    {
        abort_unless($part->part_type === ExamPart::TYPE_READING_TEXT_MCQ, 404);
        $part->load(['section.exam', 'lesenMcqPassages', 'lesenMcqQuestions.options']);

        return view('admin.parts.lesen-teil2', [
            'part' => $part,
            'passage' => $part->lesenMcqPassages->sortBy('sort_order')->first(),
            'questions' => $part->lesenMcqQuestions->sortBy('sort_order')->values(),
        ]);
    }

    public function update(StoreLesenTeil2Request $request, ExamPart $part): RedirectResponse
    {
        abort_unless($part->part_type === ExamPart::TYPE_READING_TEXT_MCQ, 404);
        $validated = $request->validated();

        DB::transaction(function () use ($part, $validated): void {
            $part->update([
                'title' => $validated['title'],
                'instruction_text' => $validated['instruction_text'],
                'points' => $validated['points'],
            ]);

            $passage = $part->lesenMcqPassages()->firstOrNew(['sort_order' => 1]);
            $passage->fill([
                'title' => $validated['passage']['title'] ?? null,
                'body_text' => $validated['passage']['body_text'],
                'sort_order' => 1,
            ])->save();

            $questionIds = [];
            foreach ($validated['questions'] as $questionIndex => $questionData) {
                $question = $part->lesenMcqQuestions()->updateOrCreate(
                    ['sort_order' => $questionIndex + 1],
                    ['question_text' => $questionData['question_text']]
                );
                $questionIds[] = $question->id;

                $optionRows = [];
                foreach ($questionData['options'] as $optionIndex => $optionData) {
                    $key = strtoupper($optionData['option_key']);
                    $optionRows[] = [
                        'lesen_mcq_question_id' => $question->id,
                        'option_key' => $key,
                        'option_text' => $optionData['option_text'],
                        'is_correct' => $key === strtoupper($questionData['correct_option_key']),
                        'sort_order' => $optionIndex + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $correctCount = collect($optionRows)->where('is_correct', true)->count();
                if ($correctCount !== 1) {
                    throw ValidationException::withMessages([
                        'questions' => 'Each question must have exactly one correct option.',
                    ]);
                }

                LesenMcqOption::query()->where('lesen_mcq_question_id', $question->id)->delete();
                LesenMcqOption::query()->insert($optionRows);
            }

            LesenMcqQuestion::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('id', $questionIds)
                ->delete();
        });

        return back()->with('status', 'Lesen Teil 2 content saved.');
    }
}
