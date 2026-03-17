<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamPart;
use App\Models\PartBankItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartPrintController extends Controller
{
    public function show(Request $request, PartBankItem $item): View
    {
        $content = $item->content_json ?? [];
        $showAnswers = $request->boolean('answers');

        return match ($item->part_type) {
            ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS => $this->showLesenTeil1($item, $content, $showAnswers),
            default => abort(404),
        };
    }

    private function showLesenTeil1(PartBankItem $item, array $content, bool $showAnswers): View
    {
        $texts = collect($content['texts'] ?? [])->sortBy('sort_order')->values();
        $options = collect($content['options'] ?? [])->sortBy('sort_order')->values();
        $answers = collect($content['correct_answers'] ?? [])->keyBy('text_label');

        return view('admin.print.lesen-teil1', [
            'item' => $item,
            'texts' => $texts,
            'options' => $options,
            'answers' => $answers,
            'showAnswers' => $showAnswers,
        ]);
    }
}
