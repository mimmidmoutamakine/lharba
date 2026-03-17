<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExamPartRequest;
use App\Http\Requests\Admin\UpdateExamPartRequest;
use App\Models\ExamPart;
use App\Models\ExamSection;
use App\Models\PartBankItem;
use App\Services\PartContentSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamPartController extends Controller
{
    public function __construct(private readonly PartContentSyncService $partContentSyncService)
    {
    }

    public function store(StoreExamPartRequest $request, ExamSection $section): RedirectResponse
    {
        $data = $request->validated();
        $bankItem = null;
        $sortOrder = (int) $data['sort_order'];
        $existingPartAtSort = $section->parts()->where('sort_order', $sortOrder)->first();

        if ($request->boolean('random_from_bank')) {
            $bankItem = PartBankItem::query()
                ->active()
                ->where('section_type', $section->type)
                ->where('part_type', (string) $data['part_type'])
                ->where(function ($query) use ($section): void {
                    $query->whereNull('level')
                        ->orWhere('level', strtolower((string) $section->exam->level));
                })
                ->inRandomOrder()
                ->first();

            if (! $bankItem) {
                throw ValidationException::withMessages([
                    'part_type' => 'No active Teil found in bank for this section/type.',
                ]);
            }
        } elseif (! empty($data['part_bank_item_id'])) {
            $bankItem = PartBankItem::query()
                ->active()
                ->where('id', (int) $data['part_bank_item_id'])
                ->where('section_type', $section->type)
                ->where(function ($query) use ($section): void {
                    $query->whereNull('level')
                        ->orWhere('level', strtolower((string) $section->exam->level));
                })
                ->first();

            if (! $bankItem) {
                throw ValidationException::withMessages([
                    'part_bank_item_id' => 'Selected Teil bank item is invalid for this section.',
                ]);
            }
        }

        if ($bankItem) {
            $payload = [
                'part_bank_item_id' => $bankItem->id,
                'title' => $bankItem->part_title,
                'instruction_text' => $bankItem->instruction_text,
                'part_type' => $bankItem->part_type,
                'points' => $bankItem->points,
                'sort_order' => $sortOrder,
                'config_json' => $bankItem->config_json,
            ];

            if ($existingPartAtSort) {
                $existingPartAtSort->update($payload);
                $part = $existingPartAtSort->refresh();
            } else {
                $part = $section->parts()->create($payload);
            }

            $this->partContentSyncService->replaceContent($part, $bankItem->content_json ?? []);
        } else {
            $payload = [
                'title' => (string) $data['title'],
                'instruction_text' => $data['instruction_text'] ?? null,
                'part_type' => (string) $data['part_type'],
                'points' => (int) ($data['points'] ?? 0),
                'sort_order' => $sortOrder,
                'config_json' => $data['config_json'] ?? null,
            ];

            if ($existingPartAtSort) {
                $existingPartAtSort->update($payload);
                $part = $existingPartAtSort->refresh();
            } else {
                $part = $section->parts()->create($payload);
            }
        }

        return redirect()
            ->route('admin.parts.edit', $part)
            ->with('status', 'Part created.');
    }

    public function edit(ExamPart $part): View
    {
        $part->load([
            'section.exam',
            'lesenMatchingTexts',
            'lesenMatchingOptions',
            'lesenMatchingAnswers',
            'lesenMcqPassages',
            'lesenMcqQuestions.options',
            'lesenSituationAds',
            'lesenSituations',
            'lesenSituationAnswers',
            'sprachGapPassages',
            'sprachGapQuestions.options',
            'sprachPoolPassages',
            'sprachPoolGaps',
            'sprachPoolOptions',
            'sprachPoolAnswers',
            'hoerenTrueFalseQuestions',
        ]);

        return view('admin.parts.edit', compact('part'));
    }

    public function update(UpdateExamPartRequest $request, ExamPart $part): RedirectResponse
    {
        $part->update($request->validated());

        return back()->with('status', 'Part updated.');
    }

    public function destroy(ExamPart $part): RedirectResponse
    {
        $exam = $part->section->exam;
        $part->delete();

        return redirect()
            ->route('admin.exams.edit', $exam)
            ->with('status', 'Part deleted.');
    }
}
