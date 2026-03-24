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
            ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X => $this->showLesenTeil3($model, $content, $showAnswers),
            ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ => $this->showSprachTeil1($model, $content, $showAnswers),
            ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH => $this->showSprachTeil2($model, $content, $showAnswers),
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

    private function showLesenTeil3(PartBankItem $model, array $content, bool $showAnswers): View
    {
        $situations = collect($content['situations'] ?? [])->sortBy('sort_order')->values();
        $ads = collect($content['ads'] ?? [])->sortBy('sort_order')->values();
        $answers = collect($content['correct_answers'] ?? [])->keyBy('situation_label');

        return view('student.print.lesen-teil3', [
            'item' => $model,
            'situations' => $situations,
            'ads' => $ads,
            'answers' => $answers,
            'showAnswers' => $showAnswers,
        ]);
    }

    private function showSprachTeil1(PartBankItem $model, array $content, bool $showAnswers): View
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

        return view('student.print.sprach-teil1', [
            'item' => $model,
            'passage' => $passage,
            'questions' => $questions,
            'showAnswers' => $showAnswers,
        ]);
    }

    private function showSprachTeil2(PartBankItem $model, array $content, bool $showAnswers): View
    {
        $passage = $content['passage'] ?? [];

        $gaps = collect($content['gaps'] ?? [])
            ->sortBy('sort_order')
            ->values();

        $options = collect($content['options'] ?? [])
            ->sortBy('sort_order')
            ->values();

        $answers = collect($content['correct_answers'] ?? [])
            ->keyBy('gap_label');

        return view('student.print.sprach-teil2', [
            'item' => $model,
            'passage' => $passage,
            'gaps' => $gaps,
            'options' => $options,
            'answers' => $answers,
            'showAnswers' => $showAnswers,
        ]);
    }
}