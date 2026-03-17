<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLesenTeil3Request;
use App\Models\ExamPart;
use App\Models\LesenSituation;
use App\Models\LesenSituationAd;
use App\Models\LesenSituationAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LesenTeil3Controller extends Controller
{
    public function edit(ExamPart $part): View
    {
        abort_unless($part->part_type === ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X, 404);
        $part->load(['section.exam', 'lesenSituationAds', 'lesenSituations', 'lesenSituationAnswers']);
        $correctBySituation = $part->lesenSituationAnswers()
            ->with('correctAd')
            ->get()
            ->mapWithKeys(function (LesenSituationAnswer $row) {
                return [$row->lesen_situation_id => $row->is_no_match ? 'X' : optional($row->correctAd)->label];
            })
            ->toArray();

        return view('admin.parts.lesen-teil3', [
            'part' => $part,
            'ads' => $part->lesenSituationAds->sortBy('sort_order')->values(),
            'situations' => $part->lesenSituations->sortBy('sort_order')->values(),
            'correctBySituation' => $correctBySituation,
        ]);
    }

    public function update(StoreLesenTeil3Request $request, ExamPart $part): RedirectResponse
    {
        abort_unless($part->part_type === ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X, 404);
        $validated = $request->validated();

        DB::transaction(function () use ($part, $validated): void {
            $part->update([
                'title' => $validated['title'],
                'instruction_text' => $validated['instruction_text'],
                'points' => $validated['points'],
            ]);

            $adMap = [];
            $adIds = [];
            foreach ($validated['ads'] as $index => $adData) {
                $ad = $part->lesenSituationAds()->updateOrCreate(
                    ['sort_order' => $index + 1],
                    [
                        'label' => strtoupper($adData['label']),
                        'title' => $adData['title'] ?? null,
                        'body_text' => $adData['body_text'],
                    ]
                );
                $adMap[strtoupper($adData['label'])] = $ad->id;
                $adIds[] = $ad->id;
            }
            $part->lesenSituationAds()->whereNotIn('id', $adIds)->delete();

            $situationMap = [];
            $situationIds = [];
            foreach ($validated['situations'] as $index => $situationData) {
                $situation = $part->lesenSituations()->updateOrCreate(
                    ['sort_order' => $index + 1],
                    [
                        'label' => (string) $situationData['label'],
                        'situation_text' => $situationData['situation_text'],
                    ]
                );
                $situationMap[(string) $situationData['label']] = $situation->id;
                $situationIds[] = $situation->id;
            }
            $part->lesenSituations()->whereNotIn('id', $situationIds)->delete();

            $resolved = [];
            foreach ($validated['correct_answers'] as $situationLabel => $answerLabel) {
                if (! isset($situationMap[$situationLabel])) {
                    throw ValidationException::withMessages(['correct_answers' => "Unknown situation label {$situationLabel}."]);
                }

                $answerLabel = strtoupper((string) $answerLabel);
                if ($answerLabel === 'X') {
                    $resolved[] = [
                        'exam_part_id' => $part->id,
                        'lesen_situation_id' => $situationMap[$situationLabel],
                        'correct_ad_id' => null,
                        'is_no_match' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    continue;
                }

                if (! isset($adMap[$answerLabel])) {
                    throw ValidationException::withMessages(['correct_answers' => "Unknown ad label {$answerLabel}."]);
                }

                $resolved[] = [
                    'exam_part_id' => $part->id,
                    'lesen_situation_id' => $situationMap[$situationLabel],
                    'correct_ad_id' => $adMap[$answerLabel],
                    'is_no_match' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $usedAds = array_filter(array_column($resolved, 'correct_ad_id'));
            if (count($usedAds) !== count(array_unique($usedAds))) {
                throw ValidationException::withMessages(['correct_answers' => 'Each Anzeige can be used only once.']);
            }

            $part->lesenSituationAnswers()->delete();
            LesenSituationAnswer::query()->insert($resolved);
        });

        return back()->with('status', 'Lesen Teil 3 content saved.');
    }
}
