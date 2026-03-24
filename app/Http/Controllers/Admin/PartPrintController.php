<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamPart;
use App\Models\PartBankItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartPrintController extends Controller
{
    public function show(Request $request, PartBankItem $model): View
    {
        abort_unless($model->is_active, 404);

        $content = $model->content_json ?? [];
        $showAnswers = $request->boolean('answers');

        return match ($model->part_type) {
            ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS => $this->showLesenTeil1($model, $content, $showAnswers),
            ExamPart::TYPE_READING_TEXT_MCQ => $this->showLesenTeil2($model, $content, $showAnswers),
            default => abort(404),
        };
    }

    private function showLesenTeil1(PartBankItem $model, array $content, bool $showAnswers): View
    {
        $texts = collect($content['texts'] ?? [])->sortBy('sort_order')->values();
        $options = collect($content['options'] ?? [])->sortBy('sort_order')->values();
        $answers = collect($content['correct_answers'] ?? [])->keyBy('text_label');

        return view('student.print.lesen-teil1', [
            'item' => $model,
            'texts' => $texts,
            'options' => $options,
            'answers' => $answers,
            'showAnswers' => $showAnswers,
        ]);
    }

    private function showLesenTeil2(PartBankItem $model, array $content, bool $showAnswers): View
    {
        $passage = $content['passage'] ?? [];

        $questions = collect($content['questions'] ?? [])
            ->sortBy('sort_order')
            ->map(function (array $question) {
                $question['options'] = collect($question['options'] ?? [])
                    ->sortBy('sort_order')
                    ->values()
                    ->all();

                return $question;
            })
            ->values();

        return view('student.print.lesen-teil2', [
            'item' => $model,
            'passage' => $passage,
            'questions' => $questions,
            'showAnswers' => $showAnswers,
        ]);
    }
}