<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHoerenTeil1Request;
use App\Models\ExamPart;
use App\Models\HoerenTrueFalseQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HoerenTeil1Controller extends Controller
{
    public function edit(ExamPart $part): View
    {
        abort_unless($part->part_type === ExamPart::TYPE_HOEREN_TRUE_FALSE, 404);
        $part->load(['section.exam', 'hoerenTrueFalseQuestions']);

        return view('admin.parts.hoeren-teil1', [
            'part' => $part,
            'questions' => $part->hoerenTrueFalseQuestions->sortBy('sort_order')->values(),
            'audioUrl' => $part->config_json['audio_url'] ?? null,
            'audioDurationSeconds' => $part->config_json['audio_duration_seconds'] ?? null,
        ]);
    }

    public function update(StoreHoerenTeil1Request $request, ExamPart $part): RedirectResponse
    {
        abort_unless($part->part_type === ExamPart::TYPE_HOEREN_TRUE_FALSE, 404);
        $validated = $request->validated();

        DB::transaction(function () use ($part, $validated): void {
            $part->update([
                'title' => $validated['title'],
                'instruction_text' => $validated['instruction_text'],
                'points' => $validated['points'],
                'config_json' => array_filter([
                    'audio_url' => $validated['audio_url'] ?? null,
                    'audio_duration_seconds' => $validated['audio_duration_seconds'] ?? null,
                ], static fn ($v) => ! is_null($v) && $v !== ''),
            ]);

            $questionIds = [];
            foreach ($validated['questions'] as $index => $questionData) {
                $q = $part->hoerenTrueFalseQuestions()->updateOrCreate(
                    ['sort_order' => $index + 1],
                    [
                        'statement_text' => $questionData['statement_text'],
                        'is_true_correct' => (bool) $questionData['is_true_correct'],
                    ]
                );
                $questionIds[] = $q->id;
            }

            HoerenTrueFalseQuestion::query()
                ->where('exam_part_id', $part->id)
                ->whereNotIn('id', $questionIds)
                ->delete();
        });

        return back()->with('status', 'Horen Teil 1 content saved.');
    }
}

