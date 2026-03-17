<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExamCsvImportRequest;
use App\Http\Requests\Admin\StoreExamPackageImportRequest;
use App\Http\Requests\Admin\StoreExamRequest;
use App\Http\Requests\Admin\UpdateExamRequest;
use App\Models\Exam;
use App\Models\ExamPart;
use App\Models\ExamSection;
use App\Models\PartBankItem;
use App\Services\PartContentSyncService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(private readonly PartContentSyncService $partContentSyncService)
    {
    }

    private const IMPORT_HEADERS = [
        'exam_code',
        'exam_title',
        'exam_level',
        'total_duration_minutes',
        'is_published',
        'section_type',
        'section_title',
        'section_sort_order',
        'part_title',
        'part_type',
        'part_points',
        'part_sort_order',
        'part_instruction',
    ];

    public function index(): View
    {
        $exams = Exam::query()
            ->withCount('sections')
            ->latest()
            ->paginate(12);

        return view('admin.exams.index', compact('exams'));
    }

    public function create(): View
    {
        return view('admin.exams.create');
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $exam = Exam::query()->create([
            ...$request->validated(),
            'is_published' => (bool) $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('admin.exams.edit', $exam)
            ->with('status', 'Exam created successfully.');
    }

    public function edit(Exam $exam): View
    {
        $exam->load(['sections.parts']);
        $bankItems = PartBankItem::query()
            ->active()
            ->orderBy('section_type')
            ->orderBy('title')
            ->get(['id', 'title', 'level', 'section_type', 'part_type']);

        return view('admin.exams.edit', compact('exam', 'bankItems'));
    }

    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $exam->update([
            ...$request->validated(),
            'is_published' => (bool) $request->boolean('is_published'),
        ]);

        return back()->with('status', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->delete();

        return redirect()
            ->route('admin.exams.index')
            ->with('status', 'Exam deleted successfully.');
    }

    public function importCsv(StoreExamCsvImportRequest $request): RedirectResponse
    {
        $rows = $this->parseCsvRows($request->file('csv_file')->getRealPath());
        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'CSV file has no data rows.',
            ]);
        }

        $examCache = [];
        $createdExams = 0;
        $createdSections = 0;
        $createdParts = 0;

        foreach ($rows as $line => $row) {
            $code = trim((string) ($row['exam_code'] ?? ''));
            $title = trim((string) ($row['exam_title'] ?? ''));
            $level = strtolower(trim((string) ($row['exam_level'] ?? '')));
            $duration = (int) ($row['total_duration_minutes'] ?? 0);
            $isPublished = in_array(strtolower(trim((string) ($row['is_published'] ?? '0'))), ['1', 'true', 'yes'], true);

            $sectionType = strtolower(trim((string) ($row['section_type'] ?? '')));
            $sectionTitle = trim((string) ($row['section_title'] ?? ''));
            $sectionSort = (int) ($row['section_sort_order'] ?? 0);

            if ($title === '' || $level === '' || $duration <= 0 || $sectionType === '' || $sectionTitle === '' || $sectionSort <= 0) {
                throw ValidationException::withMessages([
                    'csv_file' => "Invalid required values at CSV line {$line}.",
                ]);
            }

            if (! array_key_exists($sectionType, ExamSection::types())) {
                throw ValidationException::withMessages([
                    'csv_file' => "Invalid section_type '{$sectionType}' at CSV line {$line}.",
                ]);
            }

            $examKey = $code !== '' ? $code : Str::slug($title.'-'.$level.'-'.$duration);
            if (! isset($examCache[$examKey])) {
                $existingExam = Exam::query()
                    ->where('title', $title)
                    ->where('level', $level)
                    ->where('total_duration_minutes', $duration)
                    ->first();

                if ($existingExam) {
                    $existingExam->update([
                        'is_published' => $isPublished,
                    ]);
                    $exam = $existingExam->refresh();
                } else {
                    $exam = Exam::query()->create([
                        'title' => $title,
                        'level' => $level,
                        'total_duration_minutes' => $duration,
                        'is_published' => $isPublished,
                    ]);
                    $createdExams++;
                }

                $examCache[$examKey] = $exam;
            }

            /** @var Exam $exam */
            $exam = $examCache[$examKey];

            $existingSection = $exam->sections()
                ->where('type', $sectionType)
                ->where('sort_order', $sectionSort)
                ->first();

            $section = $exam->sections()->updateOrCreate(
                [
                    'type' => $sectionType,
                    'sort_order' => $sectionSort,
                ],
                [
                    'title' => $sectionTitle,
                ]
            );
            if (! $existingSection) {
                $createdSections++;
            }

            $partTitle = trim((string) ($row['part_title'] ?? ''));
            $partSort = (int) ($row['part_sort_order'] ?? 0);
            if ($partTitle === '' || $partSort <= 0) {
                continue;
            }

            $partType = $this->resolvePartType(
                $sectionType,
                $partSort,
                strtolower(trim((string) ($row['part_type'] ?? '')))
            );

            if (! array_key_exists($partType, ExamPart::types())) {
                throw ValidationException::withMessages([
                    'csv_file' => "Invalid part_type '{$partType}' at CSV line {$line}.",
                ]);
            }

            $partPoints = (int) ($row['part_points'] ?? 0);
            $instruction = trim((string) ($row['part_instruction'] ?? ''));

            $existingPart = $section->parts()
                ->where('sort_order', $partSort)
                ->first();

            $section->parts()->updateOrCreate(
                [
                    'sort_order' => $partSort,
                ],
                [
                    'title' => $partTitle,
                    'instruction_text' => $instruction,
                    'part_type' => $partType,
                    'points' => $partPoints > 0 ? $partPoints : 0,
                ]
            );
            if (! $existingPart) {
                $createdParts++;
            }
        }

        return redirect()
            ->route('admin.exams.index')
            ->with('status', "CSV imported. Exams created: {$createdExams}, sections created: {$createdSections}, parts created: {$createdParts}.");
    }

    public function downloadCsvTemplate(): Response
    {
        $headers = implode(',', self::IMPORT_HEADERS);
        $rows = [
            'exam_2026_b2_01,Deutsch B2 Probeprufung 2,b2,90,0,lesen,Leseverstehen,1,Teil 1,matching_titles_to_texts,25,1,"Lesen Sie die Uberschriften a-j..."',
            'exam_2026_b2_01,Deutsch B2 Probeprufung 2,b2,90,0,lesen,Leseverstehen,1,Teil 2,reading_text_mcq,25,2,"Lesen Sie den Text und die Aufgaben..."',
            'exam_2026_b2_01,Deutsch B2 Probeprufung 2,b2,90,0,lesen,Leseverstehen,1,Teil 3,situations_to_ads_with_x,25,3,"Lesen Sie die zehn Situationen..."',
            'exam_2026_b2_01,Deutsch B2 Probeprufung 2,b2,90,0,sprachbausteine,Sprachbausteine,2,Teil 1,sprachbausteine_email_gap_mcq,15,1,"Lesen Sie den Text und entscheiden Sie..."',
            'exam_2026_b2_01,Deutsch B2 Probeprufung 2,b2,90,0,sprachbausteine,Sprachbausteine,2,Teil 2,sprachbausteine_pool_gap_match,15,2,"Lesen Sie den Text und entscheiden Sie..."',
            'exam_2026_b2_01,Deutsch B2 Probeprufung 2,b2,90,0,hoeren,Horen,3,Teil 1,hoeren_true_false,25,1,"Sie horen die Nachrichten..."',
            'exam_2026_b2_01,Deutsch B2 Probeprufung 2,b2,90,0,schreiben,Schreiben,4,Teil 1,writing_task,45,1,"Entscheiden Sie schnell..."',
        ];

        $csv = $headers."\n".implode("\n", $rows)."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="exam_import_template.csv"',
        ]);
    }

    public function importPackage(StoreExamPackageImportRequest $request): RedirectResponse
    {
        $raw = file_get_contents($request->file('package_file')->getRealPath());
        $payload = json_decode($raw, true);

        if (! is_array($payload) || ! isset($payload['exams']) || ! is_array($payload['exams'])) {
            throw ValidationException::withMessages([
                'package_file' => 'Invalid package format. Root must contain exams array.',
            ]);
        }

        $createdExams = 0;
        $createdSections = 0;
        $createdParts = 0;

        DB::transaction(function () use ($payload, &$createdExams, &$createdSections, &$createdParts): void {
            foreach ($payload['exams'] as $examIndex => $examData) {
                $title = trim((string) ($examData['title'] ?? ''));
                $level = strtolower(trim((string) ($examData['level'] ?? '')));
                $duration = (int) ($examData['total_duration_minutes'] ?? 0);
                $isPublished = (bool) ($examData['is_published'] ?? false);

                if ($title === '' || $level === '' || $duration <= 0) {
                    throw ValidationException::withMessages([
                        'package_file' => "Invalid exam metadata at exams[{$examIndex}].",
                    ]);
                }

                $existingExam = Exam::query()
                    ->where('title', $title)
                    ->where('level', $level)
                    ->where('total_duration_minutes', $duration)
                    ->first();

                if ($existingExam) {
                    $existingExam->update(['is_published' => $isPublished]);
                    $exam = $existingExam->refresh();
                } else {
                    $exam = Exam::query()->create([
                        'title' => $title,
                        'level' => $level,
                        'total_duration_minutes' => $duration,
                        'is_published' => $isPublished,
                    ]);
                    $createdExams++;
                }

                foreach (($examData['sections'] ?? []) as $sectionIndex => $sectionData) {
                    $sectionType = strtolower(trim((string) ($sectionData['type'] ?? '')));
                    $sectionTitle = trim((string) ($sectionData['title'] ?? ''));
                    $sectionSort = (int) ($sectionData['sort_order'] ?? 0);

                    if (! array_key_exists($sectionType, ExamSection::types()) || $sectionTitle === '' || $sectionSort <= 0) {
                        throw ValidationException::withMessages([
                            'package_file' => "Invalid section at exams[{$examIndex}].sections[{$sectionIndex}].",
                        ]);
                    }

                    $existingSection = $exam->sections()
                        ->where('type', $sectionType)
                        ->where('sort_order', $sectionSort)
                        ->first();

                    $section = $exam->sections()->updateOrCreate(
                        ['type' => $sectionType, 'sort_order' => $sectionSort],
                        ['title' => $sectionTitle]
                    );
                    if (! $existingSection) {
                        $createdSections++;
                    }

                    foreach (($sectionData['parts'] ?? []) as $partIndex => $partData) {
                        $partTitle = trim((string) ($partData['title'] ?? ''));
                        $partType = strtolower(trim((string) ($partData['part_type'] ?? '')));
                        $partSort = (int) ($partData['sort_order'] ?? 0);
                        $points = (int) ($partData['points'] ?? 0);
                        $instruction = trim((string) ($partData['instruction_text'] ?? ''));

                        if ($partTitle === '' || $partSort <= 0 || ! array_key_exists($partType, ExamPart::types())) {
                            throw ValidationException::withMessages([
                                'package_file' => "Invalid part at exams[{$examIndex}].sections[{$sectionIndex}].parts[{$partIndex}].",
                            ]);
                        }

                        $existingPart = $section->parts()->where('sort_order', $partSort)->first();
                        $part = $section->parts()->updateOrCreate(
                            ['sort_order' => $partSort],
                            [
                                'title' => $partTitle,
                                'part_type' => $partType,
                                'instruction_text' => $instruction,
                                'points' => $points,
                                'config_json' => is_array($partData['config_json'] ?? null) ? $partData['config_json'] : null,
                            ]
                        );
                        if (! $existingPart) {
                            $createdParts++;
                        }

                        $this->partContentSyncService->replaceContent($part, is_array($partData['content'] ?? null) ? $partData['content'] : []);
                    }
                }
            }
        });

        return redirect()
            ->route('admin.exams.index')
            ->with('status', "Package imported. Exams created: {$createdExams}, sections created: {$createdSections}, parts created: {$createdParts}.");
    }

    public function downloadPackageTemplate(): Response
    {
        $templatePath = 'templates/exam_full_import_template.json';
        if (Storage::disk('local')->exists($templatePath)) {
            $content = Storage::disk('local')->get($templatePath);

            return response($content, 200, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="exam_full_import_template.json"',
            ]);
        }

        $template = [
            'exams' => [
                [
                    'title' => 'Deutsch B2 Probeprufung Full Import',
                    'level' => 'b2',
                    'total_duration_minutes' => 90,
                    'is_published' => false,
                    'sections' => [
                        [
                            'type' => 'lesen',
                            'title' => 'Leseverstehen',
                            'sort_order' => 1,
                            'parts' => [
                                [
                                    'title' => 'Teil 1',
                                    'part_type' => 'matching_titles_to_texts',
                                    'points' => 25,
                                    'sort_order' => 1,
                                    'instruction_text' => 'Lesen Sie die Uberschriften a-j...',
                                    'content' => [
                                        'texts' => [
                                            ['label' => '1', 'body_text' => 'Text 1', 'sort_order' => 1],
                                            ['label' => '2', 'body_text' => 'Text 2', 'sort_order' => 2],
                                        ],
                                        'options' => [
                                            ['option_key' => 'A', 'option_text' => 'Titel A', 'sort_order' => 1],
                                            ['option_key' => 'B', 'option_text' => 'Titel B', 'sort_order' => 2],
                                            ['option_key' => 'C', 'option_text' => 'Titel C', 'sort_order' => 3],
                                        ],
                                        'correct_answers' => [
                                            ['text_label' => '1', 'option_key' => 'B'],
                                            ['text_label' => '2', 'option_key' => 'A'],
                                        ],
                                    ],
                                ],
                                [
                                    'title' => 'Teil 2',
                                    'part_type' => 'reading_text_mcq',
                                    'points' => 25,
                                    'sort_order' => 2,
                                    'instruction_text' => 'Lesen Sie den Text und die Aufgaben...',
                                    'content' => [
                                        'passage' => ['title' => 'Freizeitbegriff', 'body_text' => "Langer Text...", 'sort_order' => 1],
                                        'questions' => [
                                            [
                                                'question_text' => 'Frage 1',
                                                'sort_order' => 1,
                                                'options' => [
                                                    ['option_key' => 'A', 'option_text' => 'Option A', 'is_correct' => true, 'sort_order' => 1],
                                                    ['option_key' => 'B', 'option_text' => 'Option B', 'is_correct' => false, 'sort_order' => 2],
                                                    ['option_key' => 'C', 'option_text' => 'Option C', 'is_correct' => false, 'sort_order' => 3],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return response(json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="exam_full_import_template.json"',
        ]);
    }

    private function parseCsvRows(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! is_array($lines) || count($lines) === 0) {
            return [];
        }

        $delimiter = $this->detectCsvDelimiter($lines[0]);
        $headers = null;
        $rows = [];

        foreach ($lines as $lineNumber => $line) {
            $row = str_getcsv($line, $delimiter);
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(function ($value) {
                    return strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)));
                }, $row);

                if ($headers !== self::IMPORT_HEADERS) {
                    throw ValidationException::withMessages([
                        'csv_file' => 'CSV headers do not match the template. Expected: '.implode(', ', self::IMPORT_HEADERS),
                    ]);
                }
                continue;
            }

            $normalized = array_pad($row, count($headers), '');
            $mapped = array_combine($headers, $normalized);
            $rows[$lineNumber + 1] = array_map(
                static fn ($value) => is_string($value) ? trim($value) : $value,
                $mapped
            );
        }

        return $rows;
    }

    private function detectCsvDelimiter(string $headerLine): string
    {
        $semicolonCount = count(str_getcsv($headerLine, ';'));
        $commaCount = count(str_getcsv($headerLine, ','));

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    private function resolvePartType(string $sectionType, int $partSortOrder, string $csvValue): string
    {
        if ($csvValue !== '') {
            return $csvValue;
        }

        if ($sectionType === ExamSection::TYPE_LESEN) {
            return match ($partSortOrder) {
                1 => ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS,
                2 => ExamPart::TYPE_READING_TEXT_MCQ,
                3 => ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X,
                default => ExamPart::TYPE_READING_TEXT_MCQ,
            };
        }

        if ($sectionType === ExamSection::TYPE_SPRACHBAUSTEINE) {
            return $partSortOrder === 1
                ? ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ
                : ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH;
        }

        if ($sectionType === ExamSection::TYPE_HOEREN) {
            return ExamPart::TYPE_HOEREN_TRUE_FALSE;
        }

        if ($sectionType === ExamSection::TYPE_SCHREIBEN) {
            return ExamPart::TYPE_WRITING_TASK;
        }

        return ExamPart::TYPE_MULTIPLE_CHOICE;
    }
}
