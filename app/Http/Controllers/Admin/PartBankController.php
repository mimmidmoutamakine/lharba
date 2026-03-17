<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamPart;
use App\Models\ExamSection;
use App\Models\PartBankItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PartBankController extends Controller
{
    public function index(): View
    {
        $items = PartBankItem::query()
            ->latest()
            ->paginate(20);

        return view('admin.part-bank.index', compact('items'));
    }

    public function importLesenTeil1(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
            'level' => ['nullable', 'string', 'max:10'],
            'points' => ['nullable', 'integer', 'min:0', 'max:200'],
            'instruction_text' => ['nullable', 'string'],
        ])->validate();

        $rows = $this->parseRowsByPosition($validated['csv_file']->getRealPath());
        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'No data rows found in CSV.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedLines = [];
        foreach ($rows as $index => $columns) {
            if (count($columns) < 21) {
                $skipped++;
                $skippedLines[] = (string) $index;
                continue;
            }

            $sourceLabel = trim((string) ($columns[0] ?? ''));
            if ($sourceLabel === '') {
                $skipped++;
                $skippedLines[] = (string) $index;
                continue;
            }

            $optionKeys = ['A','B','C','D','E','F','G','H','I','J'];
            $options = [];
            foreach ($optionKeys as $i => $key) {
                $options[] = [
                    'option_key' => $key,
                    'option_text' => trim((string) ($columns[$i + 1] ?? '')),
                    'sort_order' => $i + 1,
                ];
            }

            $texts = [];
            for ($i = 0; $i < 5; $i++) {
                $texts[] = [
                    'label' => (string) ($i + 1),
                    'body_text' => trim((string) ($columns[$i + 11] ?? '')),
                    'sort_order' => $i + 1,
                ];
            }

            $answers = [];
            for ($i = 0; $i < 5; $i++) {
                $answerKey = strtoupper(trim((string) ($columns[$i + 16] ?? '')));
                if ($answerKey === '' || ! in_array($answerKey, $optionKeys, true)) {
                    continue;
                }
                $answers[] = [
                    'text_label' => (string) ($i + 1),
                    'option_key' => $answerKey,
                ];
            }

            $level = strtolower((string) ($validated['level'] ?? 'b2'));
            $duplicateSet = PartBankItem::query()
                ->where('source_label', $sourceLabel)
                ->where('level', $level)
                ->where('section_type', ExamSection::TYPE_LESEN)
                ->where('part_type', ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS)
                ->orderBy('id')
                ->get();

            $existing = $duplicateSet->first();
            if ($duplicateSet->count() > 1) {
                PartBankItem::query()
                    ->whereIn('id', $duplicateSet->slice(1)->pluck('id')->all())
                    ->delete();
            }

            $payload = [
                'title' => 'Lesen Teil 1 - '.$sourceLabel,
                'source_label' => $sourceLabel,
                'level' => $level,
                'section_type' => ExamSection::TYPE_LESEN,
                'part_type' => ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS,
                'part_title' => 'Teil 1',
                'instruction_text' => (string) ($validated['instruction_text'] ?? 'Lesen Sie die Uberschriften a-j und die Texte 1-5 und entscheiden Sie, welche Uberschrift am besten zu welchem Text passt.'),
                'points' => (int) ($validated['points'] ?? 25),
                'content_json' => [
                    'texts' => $texts,
                    'options' => $options,
                    'correct_answers' => $answers,
                ],
                'config_json' => null,
                'is_active' => true,
            ];

            if ($existing) {
                $existing->update($payload);
                $item = $existing->refresh();
            } else {
                $item = PartBankItem::query()->create($payload);
            }

            if ($item->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $message = "Teil bank import complete. Created {$created}, updated {$updated}, skipped {$skipped}.";
        if ($skipped > 0) {
            $message .= ' Skipped rows: '.implode(', ', array_slice($skippedLines, 0, 20)).(count($skippedLines) > 20 ? ', ...' : '').'.';
        }

        return redirect()
            ->route('admin.part-bank.index')
            ->with('status', $message);
    }

    public function importLesenTeil2(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
            'level' => ['nullable', 'string', 'max:10'],
            'points' => ['nullable', 'integer', 'min:0', 'max:200'],
            'instruction_text' => ['nullable', 'string'],
        ])->validate();

        $rows = $this->parseRowsByPosition($validated['csv_file']->getRealPath());
        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'No data rows found in CSV.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedLines = [];

        foreach ($rows as $index => $columns) {
            if (count($columns) < 28) {
                $skipped++;
                $skippedLines[] = (string) $index;
                continue;
            }

            $sourceLabel = trim((string) ($columns[0] ?? ''));
            $passageTitle = trim((string) ($columns[1] ?? ''));
            $passageText = trim((string) ($columns[2] ?? ''));
            if ($sourceLabel === '' || $passageText === '') {
                $skipped++;
                $skippedLines[] = (string) $index;
                continue;
            }

            $questions = [];
            for ($i = 1; $i <= 5; $i++) {
                $base = 3 + (($i - 1) * 4);
                $questionText = trim((string) ($columns[$base] ?? ''));
                $optionA = trim((string) ($columns[$base + 1] ?? ''));
                $optionB = trim((string) ($columns[$base + 2] ?? ''));
                $optionC = trim((string) ($columns[$base + 3] ?? ''));
                $correctKey = strtoupper(trim((string) ($columns[23 + ($i - 1)] ?? '')));

                if ($questionText === '' || $optionA === '' || $optionB === '' || $optionC === '') {
                    $skipped++;
                    $skippedLines[] = (string) $index;
                    continue 2;
                }

                $questions[] = [
                    'question_text' => $questionText,
                    'sort_order' => $i,
                    'options' => [
                        [
                            'option_key' => 'A',
                            'option_text' => $optionA,
                            'is_correct' => $correctKey === 'A',
                            'sort_order' => 1,
                        ],
                        [
                            'option_key' => 'B',
                            'option_text' => $optionB,
                            'is_correct' => $correctKey === 'B',
                            'sort_order' => 2,
                        ],
                        [
                            'option_key' => 'C',
                            'option_text' => $optionC,
                            'is_correct' => $correctKey === 'C',
                            'sort_order' => 3,
                        ],
                    ],
                ];
            }

            $level = strtolower((string) ($validated['level'] ?? 'b2'));
            $duplicateSet = PartBankItem::query()
                ->where('source_label', $sourceLabel)
                ->where('level', $level)
                ->where('section_type', ExamSection::TYPE_LESEN)
                ->where('part_type', ExamPart::TYPE_READING_TEXT_MCQ)
                ->orderBy('id')
                ->get();

            $existing = $duplicateSet->first();
            if ($duplicateSet->count() > 1) {
                PartBankItem::query()
                    ->whereIn('id', $duplicateSet->slice(1)->pluck('id')->all())
                    ->delete();
            }

            $payload = [
                'title' => 'Lesen Teil 2 - '.$sourceLabel,
                'source_label' => $sourceLabel,
                'level' => $level,
                'section_type' => ExamSection::TYPE_LESEN,
                'part_type' => ExamPart::TYPE_READING_TEXT_MCQ,
                'part_title' => 'Teil 2',
                'instruction_text' => (string) ($validated['instruction_text'] ?? 'Lesen Sie den Text und die Aufgaben. Entscheiden Sie anhand des Textes, welche Losung richtig ist.'),
                'points' => (int) ($validated['points'] ?? 25),
                'content_json' => [
                    'passage' => [
                        'title' => $passageTitle !== '' ? $passageTitle : $sourceLabel,
                        'body_text' => $passageText,
                        'sort_order' => 1,
                    ],
                    'questions' => $questions,
                ],
                'config_json' => null,
                'is_active' => true,
            ];

            if ($existing) {
                $existing->update($payload);
                $item = $existing->refresh();
            } else {
                $item = PartBankItem::query()->create($payload);
            }

            if ($item->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $message = "Lesen Teil 2 import complete. Created {$created}, updated {$updated}, skipped {$skipped}.";
        if ($skipped > 0) {
            $message .= ' Skipped rows: '.implode(', ', array_slice($skippedLines, 0, 20)).(count($skippedLines) > 20 ? ', ...' : '').'.';
        }

        return redirect()
            ->route('admin.part-bank.index')
            ->with('status', $message);
    }

    public function importLesenTeil3(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
            'level' => ['nullable', 'string', 'max:10'],
            'points' => ['nullable', 'integer', 'min:0', 'max:200'],
            'instruction_text' => ['nullable', 'string'],
        ])->validate();

        $rows = $this->parseRowsByPosition($validated['csv_file']->getRealPath());
        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'No data rows found in CSV.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedLines = [];
        $adLabels = range('A', 'L');

        foreach ($rows as $index => $columns) {
            if (count($columns) < 45) {
                $skipped++;
                $skippedLines[] = (string) $index;
                continue;
            }

            $sourceLabel = trim((string) ($columns[0] ?? ''));
            if ($sourceLabel === '') {
                $skipped++;
                $skippedLines[] = (string) $index;
                continue;
            }

            $situations = [];
            for ($i = 1; $i <= 10; $i++) {
                $text = trim((string) ($columns[$i] ?? ''));
                if ($text === '') {
                    $skipped++;
                    $skippedLines[] = (string) $index;
                    continue 2;
                }

                $situations[] = [
                    'label' => (string) $i,
                    'situation_text' => $text,
                    'sort_order' => $i,
                ];
            }

            $ads = [];
            foreach ($adLabels as $adIndex => $label) {
                $base = 11 + ($adIndex * 2);
                $adTitle = trim((string) ($columns[$base] ?? ''));
                $adText = trim((string) ($columns[$base + 1] ?? ''));
                if ($adTitle === '' || $adText === '') {
                    $skipped++;
                    $skippedLines[] = (string) $index;
                    continue 2;
                }

                $ads[] = [
                    'label' => $label,
                    'title' => $adTitle,
                    'body_text' => $adText,
                    'sort_order' => $adIndex + 1,
                ];
            }

            $correctAnswers = [];
            for ($i = 1; $i <= 10; $i++) {
                $raw = strtoupper(trim((string) ($columns[34 + $i] ?? '')));
                if ($raw === '' || ($raw !== 'X' && ! in_array($raw, $adLabels, true))) {
                    $skipped++;
                    $skippedLines[] = (string) $index;
                    continue 2;
                }

                $correctAnswers[] = [
                    'situation_label' => (string) $i,
                    'correct_ad_label' => $raw,
                ];
            }

            $level = strtolower((string) ($validated['level'] ?? 'b2'));
            $duplicateSet = PartBankItem::query()
                ->where('source_label', $sourceLabel)
                ->where('level', $level)
                ->where('section_type', ExamSection::TYPE_LESEN)
                ->where('part_type', ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X)
                ->orderBy('id')
                ->get();

            $existing = $duplicateSet->first();
            if ($duplicateSet->count() > 1) {
                PartBankItem::query()
                    ->whereIn('id', $duplicateSet->slice(1)->pluck('id')->all())
                    ->delete();
            }

            $payload = [
                'title' => 'Lesen Teil 3 - '.$sourceLabel,
                'source_label' => $sourceLabel,
                'level' => $level,
                'section_type' => ExamSection::TYPE_LESEN,
                'part_type' => ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X,
                'part_title' => 'Teil 3',
                'instruction_text' => (string) ($validated['instruction_text'] ?? 'Lesen Sie die zehn Situationen (1-10) und die zwolf Texte (a-l). Welcher Text passt zu welcher Situation? Sie konnen jeden Text nur einmal verwenden. Manchmal passt kein Text. Wahlen Sie dann X.'),
                'points' => (int) ($validated['points'] ?? 25),
                'content_json' => [
                    'ads' => $ads,
                    'situations' => $situations,
                    'correct_answers' => $correctAnswers,
                ],
                'config_json' => null,
                'is_active' => true,
            ];

            if ($existing) {
                $existing->update($payload);
                $item = $existing->refresh();
            } else {
                $item = PartBankItem::query()->create($payload);
            }

            if ($item->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $message = "Lesen Teil 3 import complete. Created {$created}, updated {$updated}, skipped {$skipped}.";
        if ($skipped > 0) {
            $message .= ' Skipped rows: '.implode(', ', array_slice($skippedLines, 0, 20)).(count($skippedLines) > 20 ? ', ...' : '').'.';
        }

        return redirect()
            ->route('admin.part-bank.index')
            ->with('status', $message);
    }

    public function importSprachbausteineTeil1(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
            'level' => ['nullable', 'string', 'max:10'],
            'points' => ['nullable', 'integer', 'min:0', 'max:200'],
            'instruction_text' => ['nullable', 'string'],
        ])->validate();

        $rows = $this->parseRowsWithHeaders($validated['csv_file']->getRealPath());
        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'No data rows found in CSV.',
            ]);
        }

        $requiredHeaders = ['source_label', 'passage_title', 'passage_text', 'gap_number', 'a', 'b', 'c', 'correct'];
        if (array_diff($requiredHeaders, array_keys($rows[0]))) {
            throw ValidationException::withMessages([
                'csv_file' => 'Invalid headers. Expected: '.implode(', ', $requiredHeaders),
            ]);
        }

        $grouped = [];
        foreach ($rows as $row) {
            $sourceLabel = trim((string) ($row['source_label'] ?? ''));
            if ($sourceLabel === '') {
                continue;
            }
            $grouped[$sourceLabel][] = $row;
        }

        if ($grouped === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'No valid source_label rows found.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedLabels = [];

        foreach ($grouped as $sourceLabel => $items) {
            $first = $items[0];
            $passageTitle = trim((string) ($first['passage_title'] ?? ''));
            $passageText = trim((string) ($first['passage_text'] ?? ''));
            if ($passageText === '') {
                $skipped++;
                $skippedLabels[] = $sourceLabel;
                continue;
            }

            $byGap = [];
            foreach ($items as $item) {
                $gap = (int) ($item['gap_number'] ?? 0);
                if ($gap < 1 || $gap > 10) {
                    continue;
                }
                $byGap[$gap] = $item;
            }

            if (count($byGap) !== 10) {
                $skipped++;
                $skippedLabels[] = $sourceLabel;
                continue;
            }

            $questions = [];
            $invalid = false;
            for ($gap = 1; $gap <= 10; $gap++) {
                $row = $byGap[$gap];
                $optionA = trim((string) ($row['a'] ?? ''));
                $optionB = trim((string) ($row['b'] ?? ''));
                $optionC = trim((string) ($row['c'] ?? ''));
                $correct = strtoupper(trim((string) ($row['correct'] ?? '')));

                if ($optionA === '' || $optionB === '' || $optionC === '' || ! in_array($correct, ['A', 'B', 'C'], true)) {
                    $invalid = true;
                    break;
                }

                $questions[] = [
                    'gap_number' => $gap,
                    'sort_order' => $gap,
                    'options' => [
                        ['option_key' => 'A', 'option_text' => $optionA, 'is_correct' => $correct === 'A', 'sort_order' => 1],
                        ['option_key' => 'B', 'option_text' => $optionB, 'is_correct' => $correct === 'B', 'sort_order' => 2],
                        ['option_key' => 'C', 'option_text' => $optionC, 'is_correct' => $correct === 'C', 'sort_order' => 3],
                    ],
                ];
            }

            if ($invalid) {
                $skipped++;
                $skippedLabels[] = $sourceLabel;
                continue;
            }

            $level = strtolower((string) ($validated['level'] ?? 'b2'));
            $duplicateSet = PartBankItem::query()
                ->where('source_label', $sourceLabel)
                ->where('level', $level)
                ->where('section_type', ExamSection::TYPE_SPRACHBAUSTEINE)
                ->where('part_type', ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ)
                ->orderBy('id')
                ->get();

            $existing = $duplicateSet->first();
            if ($duplicateSet->count() > 1) {
                PartBankItem::query()
                    ->whereIn('id', $duplicateSet->slice(1)->pluck('id')->all())
                    ->delete();
            }

            $payload = [
                'title' => 'Sprachbausteine Teil 1 - '.$sourceLabel,
                'source_label' => $sourceLabel,
                'level' => $level,
                'section_type' => ExamSection::TYPE_SPRACHBAUSTEINE,
                'part_type' => ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ,
                'part_title' => 'Teil 1',
                'instruction_text' => (string) ($validated['instruction_text'] ?? 'Lesen Sie den Text und entscheiden Sie, welches Wort in die jeweilige Lucke passt.'),
                'points' => (int) ($validated['points'] ?? 15),
                'content_json' => [
                    'passage' => [
                        'title' => $passageTitle,
                        'body_text' => $passageText,
                        'sort_order' => 1,
                    ],
                    'questions' => $questions,
                ],
                'config_json' => null,
                'is_active' => true,
            ];

            if ($existing) {
                $existing->update($payload);
                $item = $existing->refresh();
            } else {
                $item = PartBankItem::query()->create($payload);
            }

            if ($item->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $message = "Sprachbausteine Teil 1 import complete. Created {$created}, updated {$updated}, skipped {$skipped}.";
        if ($skipped > 0) {
            $message .= ' Skipped labels: '.implode(', ', array_slice($skippedLabels, 0, 20)).(count($skippedLabels) > 20 ? ', ...' : '').'.';
        }

        return redirect()
            ->route('admin.part-bank.index')
            ->with('status', $message);
    }

    public function importSprachbausteineTeil2(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
            'level' => ['nullable', 'string', 'max:10'],
            'points' => ['nullable', 'integer', 'min:0', 'max:200'],
            'instruction_text' => ['nullable', 'string'],
        ])->validate();

        $rows = $this->parseRowsWithHeaders($validated['csv_file']->getRealPath());
        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'No data rows found in CSV.',
            ]);
        }

        $requiredHeaders = [
            'source_label',
            'passage_title',
            'passage_text',
            'gap_label',
            'option_a', 'option_b', 'option_c', 'option_d', 'option_e',
            'option_f', 'option_g', 'option_h', 'option_i', 'option_j',
            'option_k', 'option_l', 'option_m', 'option_n', 'option_o',
            'correct_option_key',
        ];
        if (array_diff($requiredHeaders, array_keys($rows[0]))) {
            throw ValidationException::withMessages([
                'csv_file' => 'Invalid headers. Expected: '.implode(', ', $requiredHeaders),
            ]);
        }

        $grouped = [];
        foreach ($rows as $row) {
            $sourceLabel = trim((string) ($row['source_label'] ?? ''));
            if ($sourceLabel === '') {
                continue;
            }
            $grouped[$sourceLabel][] = $row;
        }

        if ($grouped === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'No valid source_label rows found.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedLabels = [];
        $optionKeys = range('A', 'O');

        foreach ($grouped as $sourceLabel => $items) {
            $first = $items[0];
            $passageTitle = trim((string) ($first['passage_title'] ?? ''));
            $passageText = trim((string) ($first['passage_text'] ?? ''));
            if ($passageText === '') {
                $skipped++;
                $skippedLabels[] = $sourceLabel;
                continue;
            }

            $options = [];
            foreach ($optionKeys as $key) {
                $header = 'option_'.strtolower($key);
                $text = trim((string) ($first[$header] ?? ''));
                if ($text === '') {
                    $skipped++;
                    $skippedLabels[] = $sourceLabel;
                    continue 2;
                }
                $options[] = [
                    'option_key' => $key,
                    'option_text' => $text,
                    'sort_order' => (ord($key) - ord('A')) + 1,
                ];
            }

            $byGap = [];
            foreach ($items as $item) {
                $gap = (int) ($item['gap_label'] ?? 0);
                if ($gap < 1 || $gap > 10) {
                    continue;
                }
                $byGap[$gap] = $item;
            }

            if (count($byGap) !== 10) {
                $skipped++;
                $skippedLabels[] = $sourceLabel;
                continue;
            }

            $gaps = [];
            $correctAnswers = [];
            $invalid = false;
            for ($gap = 1; $gap <= 10; $gap++) {
                $row = $byGap[$gap];
                $correct = strtoupper(trim((string) ($row['correct_option_key'] ?? '')));
                if ($correct === '' || ! in_array($correct, $optionKeys, true)) {
                    $invalid = true;
                    break;
                }
                $gaps[] = [
                    'label' => (string) $gap,
                    'sort_order' => $gap,
                ];
                $correctAnswers[] = [
                    'gap_label' => (string) $gap,
                    'option_key' => $correct,
                ];
            }

            if ($invalid) {
                $skipped++;
                $skippedLabels[] = $sourceLabel;
                continue;
            }

            $level = strtolower((string) ($validated['level'] ?? 'b2'));
            $duplicateSet = PartBankItem::query()
                ->where('source_label', $sourceLabel)
                ->where('level', $level)
                ->where('section_type', ExamSection::TYPE_SPRACHBAUSTEINE)
                ->where('part_type', ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH)
                ->orderBy('id')
                ->get();

            $existing = $duplicateSet->first();
            if ($duplicateSet->count() > 1) {
                PartBankItem::query()
                    ->whereIn('id', $duplicateSet->slice(1)->pluck('id')->all())
                    ->delete();
            }

            $payload = [
                'title' => 'Sprachbausteine Teil 2 - '.$sourceLabel,
                'source_label' => $sourceLabel,
                'level' => $level,
                'section_type' => ExamSection::TYPE_SPRACHBAUSTEINE,
                'part_type' => ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH,
                'part_title' => 'Teil 2',
                'instruction_text' => (string) ($validated['instruction_text'] ?? 'Lesen Sie den Text und entscheiden Sie, welches Wort in welche Lucke passt. Sie konnen jedes Wort nur einmal verwenden. Nicht alle Worter passen in den Text.'),
                'points' => (int) ($validated['points'] ?? 15),
                'content_json' => [
                    'passage' => [
                        'title' => $passageTitle,
                        'body_text' => $passageText,
                        'sort_order' => 1,
                    ],
                    'gaps' => $gaps,
                    'options' => $options,
                    'correct_answers' => $correctAnswers,
                ],
                'config_json' => null,
                'is_active' => true,
            ];

            if ($existing) {
                $existing->update($payload);
                $item = $existing->refresh();
            } else {
                $item = PartBankItem::query()->create($payload);
            }

            if ($item->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $message = "Sprachbausteine Teil 2 import complete. Created {$created}, updated {$updated}, skipped {$skipped}.";
        if ($skipped > 0) {
            $message .= ' Skipped labels: '.implode(', ', array_slice($skippedLabels, 0, 20)).(count($skippedLabels) > 20 ? ', ...' : '').'.';
        }

        return redirect()
            ->route('admin.part-bank.index')
            ->with('status', $message);
    }

    public function downloadLesenTeil1Template(): Response
    {
        $templatePath = 'templates/lesen_teil1_bank_template.csv';
        if (Storage::disk('local')->exists($templatePath)) {
            $content = Storage::disk('local')->get($templatePath);

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="lesen_teil1_bank_template.csv"',
            ]);
        }

        $headers = array_merge(
            ['Titel'],
            ['A','B','C','D','E','F','G','H','I','J'],
            ['1','2','3','4','5'],
            ['1','2','3','4','5']
        );

        $exampleRow = [
            'SCHULE',
            'Titel A',
            'Titel B',
            'Titel C',
            'Titel D',
            'Titel E',
            'Titel F',
            'Titel G',
            'Titel H',
            'Titel I',
            'Titel J',
            'Text 1 ...',
            'Text 2 ...',
            'Text 3 ...',
            'Text 4 ...',
            'Text 5 ...',
            'F',
            'G',
            'E',
            'C',
            'A',
        ];

        $csv = implode(',', $headers)."\n".implode(',', array_map(static fn (string $cell) => '"'.str_replace('"', '""', $cell).'"', $exampleRow))."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="lesen_teil1_bank_template.csv"',
        ]);
    }

    public function downloadLesenTeil2Template(): Response
    {
        $templatePath = 'templates/lesen_teil2_bank_template.csv';
        if (Storage::disk('local')->exists($templatePath)) {
            $content = Storage::disk('local')->get($templatePath);

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="lesen_teil2_bank_template.csv"',
            ]);
        }

        $headers = ['Titel buch', 'Titel_Text', 'text'];
        for ($i = 1; $i <= 5; $i++) {
            $headers[] = 'F'.$i;
            $headers[] = 'A'.$i;
            $headers[] = 'B'.$i;
            $headers[] = 'C'.$i;
        }
        for ($i = 1; $i <= 5; $i++) {
            $headers[] = 'frage'.$i;
        }

        $exampleRow = [
            'Hausperson',
            'Geschichte',
            'Langer Text...',
            'Frage 1',
            'Option A1',
            'Option B1',
            'Option C1',
            'Frage 2',
            'Option A2',
            'Option B2',
            'Option C2',
            'Frage 3',
            'Option A3',
            'Option B3',
            'Option C3',
            'Frage 4',
            'Option A4',
            'Option B4',
            'Option C4',
            'Frage 5',
            'Option A5',
            'Option B5',
            'Option C5',
            'C',
            'B',
            'C',
            'A',
            'A',
        ];

        $csv = implode(',', $headers)."\n".implode(',', array_map(static fn (string $cell) => '"'.str_replace('"', '""', $cell).'"', $exampleRow))."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="lesen_teil2_bank_template.csv"',
        ]);
    }

    public function downloadLesenTeil3Template(): Response
    {
        $templatePath = 'templates/lesen_teil3_bank_template.csv';
        if (Storage::disk('local')->exists($templatePath)) {
            $content = Storage::disk('local')->get($templatePath);

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="lesen_teil3_bank_template.csv"',
            ]);
        }

        $headers = ['Title'];
        for ($i = 1; $i <= 10; $i++) {
            $headers[] = (string) $i;
        }
        foreach (range('A', 'L') as $label) {
            $headers[] = $label.'titel';
            $headers[] = $label;
        }
        for ($i = 1; $i <= 10; $i++) {
            $headers[] = (string) $i;
        }

        $exampleRow = ['Urlaub'];
        for ($i = 1; $i <= 10; $i++) {
            $exampleRow[] = 'Situation '.$i;
        }
        foreach (range('A', 'L') as $label) {
            $exampleRow[] = 'Anzeige '.$label.' Titel';
            $exampleRow[] = 'Anzeige '.$label.' Text ...';
        }
        $exampleRow = array_merge($exampleRow, ['A', 'K', 'D', 'J', 'X', 'F', 'C', 'E', 'L', 'B']);

        $csv = implode(',', $headers)."\n".implode(',', array_map(static fn (string $cell) => '"'.str_replace('"', '""', $cell).'"', $exampleRow))."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="lesen_teil3_bank_template.csv"',
        ]);
    }

    public function downloadSprachbausteineTeil1Template(): Response
    {
        $templatePath = 'templates/sprachbausteine_teil1_bank_template.csv';
        if (Storage::disk('local')->exists($templatePath)) {
            $content = Storage::disk('local')->get($templatePath);

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="sprachbausteine_teil1_bank_template.csv"',
            ]);
        }

        $headers = ['source_label', 'passage_title', 'passage_text', 'gap_number', 'A', 'B', 'C', 'correct'];
        $exampleText = 'Sehr geehrter Herr Martini, ... [[1]] ... [[2]] ... [[10]] ...';
        $rows = [
            ['martini', 'Sehr geehrter Herr Martini', $exampleText, '1', 'an', 'von', 'zu', 'C'],
            ['martini', 'Sehr geehrter Herr Martini', $exampleText, '2', 'ankommenden', 'gekommenen', 'kommenden', 'C'],
        ];

        $csv = implode(',', $headers)."\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(static fn (string $cell) => '"'.str_replace('"', '""', $cell).'"', $row))."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sprachbausteine_teil1_bank_template.csv"',
        ]);
    }

    public function downloadSprachbausteineTeil2Template(): Response
    {
        $templatePath = 'templates/sprachbausteine_teil2_bank_template.csv';
        if (Storage::disk('local')->exists($templatePath)) {
            $content = Storage::disk('local')->get($templatePath);

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="sprachbausteine_teil2_bank_template.csv"',
            ]);
        }

        $headers = [
            'source_label', 'passage_title', 'passage_text', 'gap_label',
            'option_A', 'option_B', 'option_C', 'option_D', 'option_E',
            'option_F', 'option_G', 'option_H', 'option_I', 'option_J',
            'option_K', 'option_L', 'option_M', 'option_N', 'option_O',
            'correct_option_key',
        ];
        $text = 'Deutschland ... [[1]] ... [[2]] ... [[10]] ...';
        $rows = [
            ['kinder_paradies', 'Deutschland - ein Paradies fur Kinder?', $text, '1', 'AUF', 'BEI', 'DABEI', 'DAFUR', 'DAS', 'DASS', 'DAVON', 'DENNOCH', 'DOCH', 'JEDOCH', 'MIT', 'OBWOHL', 'SONDERN', 'VON', 'WEIL', 'K'],
            ['kinder_paradies', 'Deutschland - ein Paradies fur Kinder?', $text, '2', 'AUF', 'BEI', 'DABEI', 'DAFUR', 'DAS', 'DASS', 'DAVON', 'DENNOCH', 'DOCH', 'JEDOCH', 'MIT', 'OBWOHL', 'SONDERN', 'VON', 'WEIL', 'B'],
        ];

        $csv = implode(',', $headers)."\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(static fn (string $cell) => '"'.str_replace('"', '""', $cell).'"', $row))."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sprachbausteine_teil2_bank_template.csv"',
        ]);
    }

    public function cleanupDuplicates(): RedirectResponse
    {
        $removed = 0;

        $groups = PartBankItem::query()
            ->selectRaw('MIN(id) as keep_id, source_label, level, section_type, part_type, COUNT(*) as total')
            ->groupBy('source_label', 'level', 'section_type', 'part_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $idsToDelete = PartBankItem::query()
                ->where('source_label', $group->source_label)
                ->where('level', $group->level)
                ->where('section_type', $group->section_type)
                ->where('part_type', $group->part_type)
                ->where('id', '!=', $group->keep_id)
                ->pluck('id')
                ->all();

            if ($idsToDelete !== []) {
                $removed += count($idsToDelete);
                PartBankItem::query()->whereIn('id', $idsToDelete)->delete();
            }
        }

        return redirect()
            ->route('admin.part-bank.index')
            ->with('status', "Teil bank duplicates cleanup done. Removed {$removed} duplicate rows.");
    }

    private function parseRowsByPosition(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (! is_array($lines) || count($lines) < 1) {
            return [];
        }

        $delimiter = $this->detectDelimiter($lines[0]);
        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($delimiter);

        $rows = [];
        $headerRead = false;
        $rowNumber = 1;
        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }
            if (! $headerRead) {
                $headerRead = true;
                continue;
            }

            $values = array_values(array_map(static fn ($value) => trim((string) $value), $row));
            if (count($values) === 1 && $values[0] === '') {
                continue;
            }

            $rows[$rowNumber + 1] = $values;
            $rowNumber++;
        }

        return $rows;
    }

    private function detectDelimiter(string $headerLine): string
    {
        $semicolonCount = count(str_getcsv($headerLine, ';'));
        $commaCount = count(str_getcsv($headerLine, ','));

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    private function parseRowsWithHeaders(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (! is_array($lines) || count($lines) < 2) {
            return [];
        }

        $delimiter = $this->detectDelimiter($lines[0]);
        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($delimiter);

        $headers = null;
        $rows = [];

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            $values = array_values(array_map(static fn ($value) => trim((string) $value), $row));
            if ($headers === null) {
                $headers = array_map(static fn ($h) => strtolower((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)), $values);
                continue;
            }

            if (count($values) === 1 && $values[0] === '') {
                continue;
            }

            $values = array_pad($values, count($headers), '');
            $rows[] = array_combine($headers, $values);
        }

        return $rows;
    }
}
