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

        $showAnswers = $request->boolean('answers');

        $entry = null;
        $version = null;

        if ($model->part_type && method_exists($model, 'examPartEntries')) {
            $entry = $model->examPartEntries()->latest('id')->first();
            $version = $entry?->versions()->where('is_active', true)->latest('id')->first()
                ?? $entry?->versions()->latest('id')->first();
        }

        if ($version) {
            return match ($model->part_type) {
                ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS => $this->showNormalizedLesenTeil1($model, $version, $showAnswers),
                ExamPart::TYPE_READING_TEXT_MCQ => $this->showNormalizedLesenTeil2($model, $version, $showAnswers),
                ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X => $this->showNormalizedLesenTeil3($model, $version, $showAnswers),
                ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ => $this->showNormalizedSprachTeil1($model, $version, $showAnswers),
                ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH => $this->showNormalizedSprachTeil2($model, $version, $showAnswers),
                default => $this->showLegacy($model, $showAnswers),
            };
        }

        return $this->showLegacy($model, $showAnswers);
    }

    private function showLegacy(PartBankItem $model, bool $showAnswers): View
    {
        $content = $model->content_json ?? [];

        return match ($model->part_type) {
            ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS => $this->showLesenTeil1($model, $content, $showAnswers),
            ExamPart::TYPE_READING_TEXT_MCQ => $this->showLesenTeil2($model, $content, $showAnswers),
            ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X => $this->showLesenTeil3($model, $content, $showAnswers),
            ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ => $this->showSprachTeil1($model, $content, $showAnswers),
            ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH => $this->showSprachTeil2($model, $content, $showAnswers),
            default => abort(404),
        };
    }

    private function showNormalizedLesenTeil1(PartBankItem $model, $version, bool $showAnswers): View
    {
        $texts = $version->blocks()->where('block_group', 'texts')->orderBy('sort_order')->get()->map(function ($row) {
            return [
                'label' => $row->label,
                'body_text' => $row->body_text,
                'sort_order' => $row->sort_order,
            ];
        })->values();

        $options = $version->blocks()->where('block_group', 'headlines')->orderBy('sort_order')->get()->map(function ($row) {
            return [
                'option_key' => $row->label,
                'option_text' => $row->body_text,
                'sort_order' => $row->sort_order,
            ];
        })->values();

        $answers = $version->mappings()->where('mapping_type', 'text_to_headline')->get()->map(function ($row) {
            return [
                'text_label' => str_replace('text_', '', $row->from_block_key),
                'option_key' => $row->answer_value,
            ];
        })->keyBy('text_label');

        return view('student.print.lesen-teil1', [
            'item' => $model,
            'texts' => $texts,
            'options' => $options,
            'answers' => $answers,
            'showAnswers' => $showAnswers,
        ]);
    }

    private function showNormalizedLesenTeil2(PartBankItem $model, $version, bool $showAnswers): View
    {
        $passageBlock = $version->blocks()->where('block_group', 'passage')->orderBy('sort_order')->first();

        $passage = [
            'title' => $passageBlock?->title ?? '',
            'body_text' => $passageBlock?->body_text ?? '',
            'sort_order' => 1,
        ];

        $questions = $version->blocks()->where('block_group', 'questions')->orderBy('sort_order')->get()->map(function ($question) use ($version) {
            $options = $version->blocks()
                ->where('block_group', 'options')
                ->where('parent_block_key', $question->block_key)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($opt) => [
                    'option_key' => $opt->label,
                    'option_text' => $opt->body_text,
                    'sort_order' => $opt->sort_order,
                ])
                ->values()
                ->all();

            $mapping = $version->mappings()
                ->where('mapping_type', 'question_to_correct_option')
                ->where('from_block_key', $question->block_key)
                ->first();

            return [
                'question_text' => $question->body_text,
                'sort_order' => $question->sort_order,
                'options' => $options,
                'correct_option_key' => $mapping?->answer_value,
            ];
        })->values();

        return view('student.print.lesen-teil2', [
            'item' => $model,
            'passage' => $passage,
            'questions' => $questions,
            'showAnswers' => $showAnswers,
        ]);
    }

    private function showNormalizedLesenTeil3(PartBankItem $model, $version, bool $showAnswers): View
    {
        $situations = $version->blocks()->where('block_group', 'situations')->orderBy('sort_order')->get()->map(function ($row) {
            return [
                'label' => $row->label,
                'situation_text' => $row->body_text,
                'sort_order' => $row->sort_order,
            ];
        })->values();

        $ads = $version->blocks()->where('block_group', 'ads')->orderBy('sort_order')->get()->map(function ($row) {
            return [
                'label' => $row->label,
                'title' => $row->title,
                'body_text' => $row->body_text,
                'sort_order' => $row->sort_order,
            ];
        })->values();

        $answers = $version->mappings()->where('mapping_type', 'situation_to_ad')->get()->map(function ($row) {
            return [
                'situation_label' => str_replace('situation_', '', $row->from_block_key),
                'correct_ad_label' => $row->answer_value,
            ];
        })->keyBy('situation_label');

        return view('student.print.lesen-teil3', [
            'item' => $model,
            'situations' => $situations,
            'ads' => $ads,
            'answers' => $answers,
            'showAnswers' => $showAnswers,
        ]);
    }

    private function showNormalizedSprachTeil1(PartBankItem $model, $version, bool $showAnswers): View
    {
        $passageBlock = $version->blocks()->where('block_group', 'passage')->orderBy('sort_order')->first();

        $passage = [
            'title' => $passageBlock?->title ?? '',
            'body_text' => $passageBlock?->body_text ?? '',
            'sort_order' => 1,
        ];

        $questions = $version->blocks()->where('block_group', 'gaps')->orderBy('sort_order')->get()->map(function ($gap) use ($version) {
            $options = $version->blocks()
                ->where('block_group', 'options')
                ->where('parent_block_key', $gap->block_key)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($opt) => [
                    'option_key' => $opt->label,
                    'option_text' => $opt->body_text,
                    'sort_order' => $opt->sort_order,
                ])
                ->values()
                ->all();

            $mapping = $version->mappings()
                ->where('mapping_type', 'gap_to_option')
                ->where('from_block_key', $gap->block_key)
                ->first();

            return [
                'gap_number' => (int) $gap->label,
                'sort_order' => $gap->sort_order,
                'options' => $options,
                'correct_option_key' => $mapping?->answer_value,
            ];
        })->values();

        return view('student.print.sprach-teil1', [
            'item' => $model,
            'passage' => $passage,
            'questions' => $questions,
            'showAnswers' => $showAnswers,
        ]);
    }

    private function showNormalizedSprachTeil2(PartBankItem $model, $version, bool $showAnswers): View
    {
        $passageBlock = $version->blocks()->where('block_group', 'passage')->orderBy('sort_order')->first();

        $passage = [
            'title' => $passageBlock?->title ?? '',
            'body_text' => $passageBlock?->body_text ?? '',
            'sort_order' => 1,
        ];

        $gaps = $version->blocks()->where('block_group', 'gaps')->orderBy('sort_order')->get()->map(function ($gap) {
            return [
                'label' => $gap->label,
                'sort_order' => $gap->sort_order,
            ];
        })->values();

        $options = $version->blocks()->where('block_group', 'pool_options')->orderBy('sort_order')->get()->map(function ($opt) {
            return [
                'option_key' => $opt->label,
                'option_text' => $opt->body_text,
                'sort_order' => $opt->sort_order,
            ];
        })->values();

        $answers = $version->mappings()->where('mapping_type', 'gap_to_pool_option')->get()->map(function ($row) {
            return [
                'gap_label' => str_replace('gap_', '', $row->from_block_key),
                'option_key' => $row->answer_value,
            ];
        })->keyBy('gap_label');

        return view('student.print.sprach-teil2', [
            'item' => $model,
            'passage' => $passage,
            'gaps' => $gaps,
            'options' => $options,
            'answers' => $answers,
            'showAnswers' => $showAnswers,
        ]);
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