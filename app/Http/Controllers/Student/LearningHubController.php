<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamSection;
use App\Models\PartBankItem;
use App\Services\PracticeExamBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LearningHubController extends Controller
{
    private const SECTION_CHOICES = [
        'lesen_t1' => ['label' => 'Lesen Teil 1', 'section_type' => ExamSection::TYPE_LESEN, 'part_type' => 'matching_titles_to_texts'],
        'lesen_t2' => ['label' => 'Lesen Teil 2', 'section_type' => ExamSection::TYPE_LESEN, 'part_type' => 'reading_text_mcq'],
        'lesen_t3' => ['label' => 'Lesen Teil 3', 'section_type' => ExamSection::TYPE_LESEN, 'part_type' => 'situations_to_ads_with_x'],
        'sprach_t1' => ['label' => 'Sprachbausteine Teil 1', 'section_type' => ExamSection::TYPE_SPRACHBAUSTEINE, 'part_type' => 'sprachbausteine_email_gap_mcq'],
        'sprach_t2' => ['label' => 'Sprachbausteine Teil 2', 'section_type' => ExamSection::TYPE_SPRACHBAUSTEINE, 'part_type' => 'sprachbausteine_pool_gap_match'],
        'hoeren_t1' => ['label' => 'Horen Teil 1', 'section_type' => ExamSection::TYPE_HOEREN, 'part_type' => 'hoeren_true_false'],
        'hoeren_t2' => ['label' => 'Horen Teil 2', 'section_type' => ExamSection::TYPE_HOEREN, 'part_type' => 'listening_mcq'],
        'schreiben_t1' => ['label' => 'Schreiben Teil 1', 'section_type' => ExamSection::TYPE_SCHREIBEN, 'part_type' => 'writing_task'],
    ];

    private const CHALLENGE_CHOICES = [
        'lesen' => [
            'title' => 'تحدّى راسك فـ Lesen :',
            'theme' => 'black',
            'items' => [
                ['key' => 'lesen_t3', 'label' => 'Lesen Teil 3', 'section_type' => ExamSection::TYPE_LESEN, 'part_type' => 'situations_to_ads_with_x'],
                ['key' => 'lesen_t2', 'label' => 'Lesen Teil 2', 'section_type' => ExamSection::TYPE_LESEN, 'part_type' => 'reading_text_mcq'],
                ['key' => 'lesen_t1', 'label' => 'Lesen Teil 1', 'section_type' => ExamSection::TYPE_LESEN, 'part_type' => 'matching_titles_to_texts'],
            ],
        ],
        'sprachbausteine' => [
            'title' => 'تحدّى راسك فـ Sprachbausteine :',
            'theme' => 'red',
            'items' => [
                ['key' => 'sprach_t1', 'label' => 'Sprachbausteine Teil 1', 'section_type' => ExamSection::TYPE_SPRACHBAUSTEINE, 'part_type' => 'sprachbausteine_email_gap_mcq'],
                ['key' => 'sprach_t2', 'label' => 'Sprachbausteine Teil 2', 'section_type' => ExamSection::TYPE_SPRACHBAUSTEINE, 'part_type' => 'sprachbausteine_pool_gap_match'],
            ],
        ],
        'hoeren' => [
            'title' => 'تحدّى راسك فـ Hören :',
            'theme' => 'yellow',
            'items' => [
                ['key' => 'hoeren_t3', 'label' => 'Hören Teil 3', 'section_type' => ExamSection::TYPE_HOEREN, 'part_type' => 'hoeren_true_false', 'part_title' => 'Teil 3'],
                ['key' => 'hoeren_t2', 'label' => 'Hören Teil 2', 'section_type' => ExamSection::TYPE_HOEREN, 'part_type' => 'listening_mcq'],
                ['key' => 'hoeren_t1', 'label' => 'Hören Teil 1', 'section_type' => ExamSection::TYPE_HOEREN, 'part_type' => 'hoeren_true_false', 'part_title' => 'Teil 1'],
            ],
        ],
    ];

    public function dashboard(): View
    {
        $userId = (int) Auth::id();
        $progressBars = $this->buildSectionProgressMetrics($userId);
        $dailyPlan = $this->buildDailyPlan($progressBars);

        $available = $progressBars->filter(fn (array $row) => $row['available_models'] > 0)->values();
        $totalSections = $available->count();
        $coveredSections = $available->where('coverage', 100)->count();
        $readySections = $available->where('status', 'Ready')->count();
        $coverageMissionPercent = $totalSections > 0 ? (int) round(($coveredSections / $totalSections) * 100) : 0;
        $masteryMissionPercent = $totalSections > 0 ? (int) round(($readySections / $totalSections) * 100) : 0;

        return view('student.dashboard', [
            'dailyPlan' => $dailyPlan,
            'coverageMissionPercent' => $coverageMissionPercent,
            'masteryMissionPercent' => $masteryMissionPercent,
            'coveredSections' => $coveredSections,
            'readySections' => $readySections,
            'totalSections' => $totalSections,
        ]);
    }

    public function training(Request $request): View
    {
        $sectionFilter = (string) $request->string('section');
        $statusFilter = (string) $request->string('status');
        $difficultyFilter = (string) $request->string('difficulty');
        $sort = (string) $request->string('sort', 'title');

        $query = PartBankItem::query()->active();
        if ($sectionFilter !== '' && isset(self::SECTION_CHOICES[$sectionFilter])) {
            $query
                ->where('section_type', self::SECTION_CHOICES[$sectionFilter]['section_type'])
                ->where('part_type', self::SECTION_CHOICES[$sectionFilter]['part_type']);
        }

        $models = $query->orderBy('title')->get();

        $stats = $this->buildModelStats($models->pluck('id')->all());
        $rows = $models->map(function (PartBankItem $item) use ($stats) {
            $modelStats = $stats[$item->id] ?? ['attempts' => 0, 'best_score' => null];
            $bestScore = $modelStats['best_score'];
            $status = $modelStats['attempts'] === 0
                ? 'Not Started'
                : (($bestScore ?? 0) >= 85 ? 'Mastered' : 'Practiced');
            $difficulty = $this->difficultyLabel($bestScore, $modelStats['attempts']);

            return [
                'item' => $item,
                'attempts' => $modelStats['attempts'],
                'best_score' => $bestScore,
                'status' => $status,
                'difficulty' => $difficulty,
            ];
        })->values();

        if ($statusFilter !== '') {
            $rows = $rows->filter(fn (array $row): bool => strcasecmp($row['status'], $statusFilter) === 0)->values();
        }
        if ($difficultyFilter !== '') {
            $rows = $rows->filter(fn (array $row): bool => strcasecmp($row['difficulty'], $difficultyFilter) === 0)->values();
        }

        $rows = match ($sort) {
            'difficulty' => $rows->sortBy('difficulty')->values(),
            'attempts' => $rows->sortByDesc('attempts')->values(),
            'best_score' => $rows->sortByDesc(fn (array $row) => $row['best_score'] ?? -1)->values(),
            default => $rows->sortBy(fn (array $row) => strtolower($row['item']->title))->values(),
        };

        return view('student.hub.training', [
            'sectionChoices' => self::SECTION_CHOICES,
            'rows' => $rows,
            'filters' => [
                'section' => $sectionFilter,
                'status' => $statusFilter,
                'difficulty' => $difficultyFilter,
                'sort' => $sort,
            ],
        ]);
    }

    public function builder(): View
    {
        $models = PartBankItem::query()
            ->active()
            ->orderBy('section_type')
            ->orderBy('title')
            ->get(['id', 'title', 'section_type', 'part_title', 'level']);

        return view('student.hub.builder', [
            'sectionChoices' => self::SECTION_CHOICES,
            'allModels' => $models,
            'modelsForBuilder' => $models->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'part_title' => $m->part_title,
                'section_type' => $m->section_type,
                'level' => strtoupper((string) $m->level),
            ])->values(),
            'capacities' => $this->builderSlotCapacities(),
        ]);
    }

    public function startInstantPractice(PracticeExamBuilderService $builder): RedirectResponse
    {
        $items = collect(self::SECTION_CHOICES)
            ->map(function (array $config) {
                return PartBankItem::query()
                    ->active()
                    ->where('section_type', $config['section_type'])
                    ->where('part_type', $config['part_type'])
                    ->inRandomOrder()
                    ->first();
            })
            ->filter();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['training' => 'No bank models available yet.']);
        }

        $exam = $builder->createFromBankItems(Auth::user(), $items, 'Instant Practice');

        return redirect()->route('exams.start', $exam);
    }

    public function startTargetedPractice(Request $request, PracticeExamBuilderService $builder): RedirectResponse
    {
        $selected = collect((array) $request->input('sections', []))
            ->map(static fn ($value) => (string) $value)
            ->filter(fn (string $key): bool => isset(self::SECTION_CHOICES[$key]))
            ->unique()
            ->values();

        if ($selected->isEmpty()) {
            throw ValidationException::withMessages(['sections' => 'Select at least one section.']);
        }

        $items = $selected->map(function (string $key) {
            $config = self::SECTION_CHOICES[$key];

            return PartBankItem::query()
                ->active()
                ->where('section_type', $config['section_type'])
                ->where('part_type', $config['part_type'])
                ->inRandomOrder()
                ->first();
        })->filter();

        if ($items->count() !== $selected->count()) {
            throw ValidationException::withMessages(['sections' => 'Some selected sections have no available models in bank.']);
        }

        $exam = $builder->createFromBankItems(Auth::user(), $items, 'Targeted Practice');

        return redirect()->route('exams.start', $exam);
    }

    public function startCustomExam(Request $request, PracticeExamBuilderService $builder): RedirectResponse
    {
        $modelIds = collect((array) $request->input('model_ids', []))
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($modelIds->isEmpty()) {
            throw ValidationException::withMessages(['model_ids' => 'Select at least one model for custom exam.']);
        }

        $items = PartBankItem::query()
            ->active()
            ->whereIn('id', $modelIds->all())
            ->get()
            ->sortBy(fn (PartBankItem $item) => $modelIds->search($item->id))
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['model_ids' => 'Selected models are not available.']);
        }

        $exam = $builder->createFromBankItems(Auth::user(), $items, 'Custom Practice');

        return redirect()->route('exams.start', $exam);
    }

    public function startModel(Request $request, PracticeExamBuilderService $builder, PartBankItem $model): RedirectResponse
    {
        abort_unless($model->is_active, 404);
        $exam = $builder->createFromBankItems(Auth::user(), collect([$model]), 'Model Practice');

        return redirect()->route('exams.start', $exam);
    }

    public function continuePlan(PracticeExamBuilderService $builder): RedirectResponse
    {
        $userId = (int) Auth::id();
        $progressBars = $this->buildSectionProgressMetrics($userId);
        $dailyPlan = $this->buildDailyPlan($progressBars);
        $nextTask = $dailyPlan[0] ?? null;

        if (! $nextTask || ! isset(self::SECTION_CHOICES[$nextTask['key']])) {
            return $this->startInstantPractice($builder);
        }

        $choice = self::SECTION_CHOICES[$nextTask['key']];
        $item = PartBankItem::query()
            ->active()
            ->where('section_type', $choice['section_type'])
            ->where('part_type', $choice['part_type'])
            ->inRandomOrder()
            ->first();

        if (! $item) {
            return $this->startInstantPractice($builder);
        }

        $exam = $builder->createFromBankItems(Auth::user(), collect([$item]), 'Daily Plan Practice');

        return redirect()->route('exams.start', $exam);
    }

    public function challenge(): View
    {
        $leaderboard = $this->buildChallengeLeaderboard();
        $personalBest = (int) ($leaderboard->firstWhere('user_id', Auth::id())['rounds'] ?? 0);
        $availableParts = PartBankItem::query()
            ->active()
            ->get(['section_type', 'part_type', 'part_title'])
            ->map(fn (PartBankItem $item): array => [
                'exact' => implode('|', [
                    $item->section_type,
                    $item->part_type,
                    (string) $item->part_title,
                ]),
                'base' => implode('|', [
                    $item->section_type,
                    $item->part_type,
                ]),
            ]);

        $availableExactKeys = $availableParts->pluck('exact')->unique()->flip();
        $availableBaseKeys = $availableParts->pluck('base')->unique()->flip();

        $groups = collect(self::CHALLENGE_CHOICES)->map(function (array $group) use ($availableExactKeys, $availableBaseKeys) {
            $items = collect($group['items'])->map(function (array $item) use ($availableExactKeys, $availableBaseKeys) {
                $signature = implode('|', [
                    $item['section_type'],
                    $item['part_type'],
                    (string) ($item['part_title'] ?? ''),
                ]);

                $baseSignature = implode('|', [
                    $item['section_type'],
                    $item['part_type'],
                ]);

                $isAvailable = ! empty($item['part_title'])
                    ? $availableExactKeys->has($signature)
                    : $availableBaseKeys->has($baseSignature);

                return $item + ['available' => $isAvailable];
            });

            return [
                'title' => $group['title'],
                'theme' => $group['theme'],
                'items' => $items,
            ];
        })->values();

        return view('student.hub.challenge', [
            'personalBest' => $personalBest,
            'leaderboard' => $leaderboard,
            'groups' => $groups,
        ]);
    }

    public function startChallenge(Request $request, PracticeExamBuilderService $builder): RedirectResponse
    {
        $round = max(1, (int) $request->input('round', 1));
        $challengeKey = (string) $request->input('challenge_key', '');
        return $this->launchChallengeRound($builder, $request, $challengeKey, $round);
    }

    public function startChallengeLink(string $challengeKey, Request $request, PracticeExamBuilderService $builder): RedirectResponse
    {
        $round = max(1, (int) $request->query('round', 1));
        return $this->launchChallengeRound($builder, $request, $challengeKey, $round);
    }

    private function launchChallengeRound(
        PracticeExamBuilderService $builder,
        Request $request,
        string $challengeKey,
        int $round
    ): RedirectResponse {
        $challengeChoice = collect(self::CHALLENGE_CHOICES)
            ->flatMap(fn (array $group) => $group['items'])
            ->firstWhere('key', $challengeKey);

        $query = PartBankItem::query()->active();
        if (is_array($challengeChoice)) {
            $query
                ->where('section_type', $challengeChoice['section_type'])
                ->where('part_type', $challengeChoice['part_type']);

            if (! empty($challengeChoice['part_title'])) {
                $query->where('part_title', $challengeChoice['part_title']);
            }
        }

        $item = $query->inRandomOrder()->first();
        if (! $item) {
            throw ValidationException::withMessages(['challenge' => 'No bank models available for challenge mode.']);
        }

        $exam = $builder->createFromBankItems(Auth::user(), collect([$item]), "Survival R{$round}");
        $request->session()->put('survival_mode', [
            'active' => true,
            'current_round' => $round,
        ]);

        return redirect()->route('exams.start', $exam);
    }

    public function progress(): View
    {
        $userId = (int) Auth::id();
        $progressBars = $this->buildSectionProgressMetrics($userId);

        $attempts = ExamAttempt::query()
            ->where('user_id', $userId)
            ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_EXPIRED])
            ->pluck('id');

        $answerRows = AttemptAnswer::query()
            ->select([
                'exam_parts.part_bank_item_id',
                'exam_parts.id as exam_part_id',
                'exam_parts.title as exam_part_title',
                'exam_parts.part_type as part_type',
                'exam_sections.type as section_type',
                'attempt_answers.is_correct',
                'attempt_answers.created_at',
                'attempt_answers.exam_attempt_id',
            ])
            ->join('exam_parts', 'exam_parts.id', '=', 'attempt_answers.exam_part_id')
            ->join('exam_sections', 'exam_sections.id', '=', 'exam_parts.exam_section_id')
            ->whereIn('attempt_answers.exam_attempt_id', $attempts)
            ->orderByDesc('attempt_answers.created_at')
            ->get();

        $totalModelsAttempted = (int) $answerRows
            ->map(fn ($row) => $row->part_bank_item_id ? 'bank_'.$row->part_bank_item_id : 'part_'.$row->exam_part_id)
            ->unique()
            ->count();
        $scoredRows = $answerRows->whereNotNull('is_correct')->values();
        $averageScore = null;

        $partAccuracySeries = $this->buildPartAccuracySeries($userId);

        $overallAccuracy = collect($partAccuracySeries)
            ->flatten()
            ->values();
        $averageScore = $overallAccuracy->isEmpty() ? null : round($overallAccuracy->avg(), 1);

        $rankable = $progressBars
            ->filter(fn (array $row) => $row['available_models'] > 0 && $row['attempts'] > 0)
            ->sortByDesc('confidence');
        $bestSection = $rankable->keys()->first();
        $weakestSection = $rankable->count() > 1 ? $rankable->sortBy('confidence')->keys()->first() : null;

        $recentModels = $answerRows
            ->map(fn ($row) => [
                'key' => $row->part_bank_item_id ? 'bank_'.$row->part_bank_item_id : 'part_'.$row->exam_part_id,
                'part_bank_item_id' => $row->part_bank_item_id ? (int) $row->part_bank_item_id : null,
                'title' => (string) ($row->exam_part_title ?? 'Part '.$row->exam_part_id),
                'section_type' => (string) $row->section_type,
            ])
            ->unique('key')
            ->take(6)
            ->values();

        $recommended = $progressBars
            ->filter(fn (array $row) => $row['available_models'] > 0)
            ->sortBy('confidence')
            ->take(3)
            ->map(fn (array $row, string $key) => [
                'key' => $key,
                'label' => $row['label'],
                'coverage' => $row['coverage'],
                'accuracy' => $row['accuracy'],
                'stability' => $row['stability'],
                'confidence' => $row['confidence'],
                'status' => $row['status'],
            ])
            ->values();

        $sectionLabelMap = [
            ExamSection::TYPE_LESEN => 'Lesen',
            ExamSection::TYPE_SPRACHBAUSTEINE => 'Sprachbausteine',
            ExamSection::TYPE_HOEREN => 'Horen',
            ExamSection::TYPE_SCHREIBEN => 'Schreiben',
        ];

        return view('student.hub.progress', [
            'totalModelsAttempted' => $totalModelsAttempted,
            'averageScore' => $averageScore,
            'bestSection' => $bestSection
                ? ($sectionLabelMap[$bestSection] ?? (self::SECTION_CHOICES[$bestSection]['label'] ?? ucfirst((string) $bestSection)))
                : null,
            'weakestSection' => $weakestSection
                ? ($sectionLabelMap[$weakestSection] ?? (self::SECTION_CHOICES[$weakestSection]['label'] ?? ucfirst((string) $weakestSection)))
                : null,
            'progressBars' => $progressBars,
            'recentModels' => $recentModels,
            'recommendedSections' => $recommended,
        ]);
    }

    private function buildModelStats(array $modelIds): array
    {
        if ($modelIds === []) {
            return [];
        }

        $rows = AttemptAnswer::query()
            ->select([
                'exam_parts.part_bank_item_id',
                DB::raw('COUNT(attempt_answers.id) as attempts'),
                DB::raw('MAX(CASE WHEN attempt_answers.is_correct = 1 THEN 100 WHEN attempt_answers.is_correct = 0 THEN 0 ELSE NULL END) as best_score'),
            ])
            ->join('exam_parts', 'exam_parts.id', '=', 'attempt_answers.exam_part_id')
            ->join('exam_attempts', 'exam_attempts.id', '=', 'attempt_answers.exam_attempt_id')
            ->where('exam_attempts.user_id', Auth::id())
            ->whereIn('exam_parts.part_bank_item_id', $modelIds)
            ->groupBy('exam_parts.part_bank_item_id')
            ->get();

        $stats = [];
        foreach ($rows as $row) {
            $stats[(int) $row->part_bank_item_id] = [
                'attempts' => (int) $row->attempts,
                'best_score' => $row->best_score !== null ? (int) $row->best_score : null,
            ];
        }

        return $stats;
    }

    private function difficultyLabel(?int $bestScore, int $attempts): string
    {
        if ($attempts === 0) {
            return 'Unknown';
        }
        if (($bestScore ?? 0) >= 85) {
            return 'Easy';
        }
        if (($bestScore ?? 0) >= 60) {
            return 'Medium';
        }

        return 'Hard';
    }

    private function buildChallengeLeaderboard()
    {
        $attempts = ExamAttempt::query()
            ->with(['exam:id,title', 'user:id,name', 'answers:id,exam_attempt_id,is_correct'])
            ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_EXPIRED])
            ->whereHas('exam', function ($query): void {
                $query->where('title', 'like', '[Survival R%');
            })
            ->latest('id')
            ->limit(500)
            ->get();

        $scoresByUser = [];
        foreach ($attempts as $attempt) {
            $title = (string) ($attempt->exam->title ?? '');
            if (! preg_match('/^\[Survival R(\d+)\]/', $title, $match)) {
                continue;
            }
            $round = (int) $match[1];
            $hasAnswers = $attempt->answers->whereNotNull('is_correct')->count() > 0;
            $failed = $attempt->answers->where('is_correct', false)->count() > 0;
            $passed = $hasAnswers && ! $failed;
            if (! $passed) {
                continue;
            }

            $uid = (int) $attempt->user_id;
            $scoresByUser[$uid] = max($scoresByUser[$uid] ?? 0, $round);
        }

        return collect($scoresByUser)
            ->map(function (int $rounds, int $userId) use ($attempts) {
                $userName = (string) optional($attempts->firstWhere('user_id', $userId)?->user)->name;

                return [
                    'user_id' => $userId,
                    'name' => $userName !== '' ? $userName : 'User '.$userId,
                    'rounds' => $rounds,
                ];
            })
            ->sortByDesc('rounds')
            ->values()
            ->take(20);
    }

    private function progressForPartType(int $userId, string $partType, ?int $totalFromBank = null): int
    {
        $totalFromBank ??= PartBankItem::query()->where('part_type', $partType)->active()->count();

        $attemptRows = AttemptAnswer::query()
            ->select(['exam_parts.part_bank_item_id', 'exam_parts.id as exam_part_id'])
            ->join('exam_parts', 'exam_parts.id', '=', 'attempt_answers.exam_part_id')
            ->join('exam_attempts', 'exam_attempts.id', '=', 'attempt_answers.exam_attempt_id')
            ->where('exam_attempts.user_id', $userId)
            ->where('exam_parts.part_type', $partType)
            ->get();

        $attempted = $attemptRows
            ->map(fn ($row) => $row->part_bank_item_id ? 'bank_'.$row->part_bank_item_id : 'part_'.$row->exam_part_id)
            ->unique()
            ->count();

        if ($totalFromBank <= 0) {
            return 0;
        }

        return (int) round((min($attempted, $totalFromBank) / $totalFromBank) * 100);
    }

    private function buildSectionProgressMetrics(int $userId)
    {
        $trackedPartTypes = collect(self::SECTION_CHOICES)->pluck('part_type')->unique()->values()->all();
        $bankTotalsByType = PartBankItem::query()
            ->active()
            ->whereIn('part_type', $trackedPartTypes)
            ->selectRaw('part_type, count(*) as c')
            ->groupBy('part_type')
            ->pluck('c', 'part_type');
        $partAccuracySeries = $this->buildPartAccuracySeries($userId);

        return collect(self::SECTION_CHOICES)->mapWithKeys(function (array $choice, string $key) use ($userId, $bankTotalsByType, $partAccuracySeries) {
            $totalInBank = (int) ($bankTotalsByType[$choice['part_type']] ?? 0);
            $coverage = $this->progressForPartType($userId, $choice['part_type'], $totalInBank);
            $accuracySamples = $partAccuracySeries[$choice['part_type']] ?? [];
            $accuracy = count($accuracySamples) > 0 ? (int) round(collect($accuracySamples)->avg()) : null;
            $stability = $this->calculateStability($accuracySamples);
            $confidence = $this->calculateConfidence($coverage, $accuracy, $stability);

            return [
                $key => [
                    'key' => $key,
                    'label' => $choice['label'],
                    'coverage' => $coverage,
                    'accuracy' => $accuracy,
                    'stability' => $stability,
                    'confidence' => $confidence,
                    'attempts' => count($accuracySamples),
                    'status' => $this->readinessStatus($coverage, $accuracy, $stability),
                    'available_models' => $totalInBank,
                ],
            ];
        });
    }

    private function buildDailyPlan($progressBars): array
    {
        $rows = collect($progressBars)
            ->filter(fn (array $row) => $row['available_models'] > 0)
            ->values();

        if ($rows->isEmpty()) {
            return [];
        }

        $byConfidence = $rows->sortBy('confidence')->values();
        $recovery = $byConfidence->first();
        $maintenance = $rows->sortByDesc('confidence')->first();
        $middleIndex = (int) floor(max(0, $byConfidence->count() - 1) / 2);
        $growth = $byConfidence->get($middleIndex);

        $tasks = collect([
            $recovery ? [
                'key' => $recovery['key'],
                'label' => $recovery['label'],
                'focus' => 'Recovery',
                'hint' => 'Lowest confidence: quick win practice.',
                'confidence' => $recovery['confidence'],
            ] : null,
            $growth ? [
                'key' => $growth['key'],
                'label' => $growth['label'],
                'focus' => 'Growth',
                'hint' => 'Mid-zone section: push performance upward.',
                'confidence' => $growth['confidence'],
            ] : null,
            $maintenance ? [
                'key' => $maintenance['key'],
                'label' => $maintenance['label'],
                'focus' => 'Maintenance',
                'hint' => 'Keep a strong section fresh.',
                'confidence' => $maintenance['confidence'],
            ] : null,
        ])
            ->filter()
            ->unique('key')
            ->values();

        return $tasks->all();
    }

    private function buildPartAccuracySeries(int $userId): array
    {
        $rows = AttemptAnswer::query()
            ->with([
                'part.section',
                'part.lesenMatchingAnswers',
                'part.lesenMcqQuestions.options',
                'part.lesenSituationAnswers',
                'part.sprachGapQuestions.options',
                'part.sprachPoolAnswers',
                'part.hoerenTrueFalseQuestions',
            ])
            ->whereHas('attempt', function ($query) use ($userId): void {
                $query->where('user_id', $userId)
                    ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_EXPIRED]);
            })
            ->orderByDesc('exam_attempt_id')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $partType = (string) optional($row->part)->part_type;
            if ($partType === '') {
                continue;
            }

            $score = $this->calculateAttemptRowAccuracy($row);
            if ($score === null) {
                continue;
            }

            $grouped[$partType] ??= [];
            $grouped[$partType][] = $score;
        }

        return $grouped;
    }

    private function calculateAttemptRowAccuracy(AttemptAnswer $row): ?float
    {
        $part = $row->part;
        if (! $part) {
            return null;
        }

        $answerJson = is_array($row->answer_json) ? $row->answer_json : [];

        if ($part->part_type === 'matching_titles_to_texts') {
            $map = $part->lesenMatchingAnswers->pluck('correct_option_id', 'lesen_matching_text_id');
            $given = (array) ($answerJson['assignments'] ?? []);
            $possible = $map->count();
            if ($possible === 0) {
                return null;
            }
            $correct = 0;
            foreach ($map as $textId => $optionId) {
                if ((int) ($given[$textId] ?? 0) === (int) $optionId) {
                    $correct++;
                }
            }

            return round(($correct / $possible) * 100, 1);
        }

        if ($part->part_type === 'reading_text_mcq') {
            $questions = $part->lesenMcqQuestions;
            $possible = $questions->count();
            if ($possible === 0) {
                return null;
            }
            $given = (array) ($answerJson['choices'] ?? []);
            $correct = 0;
            foreach ($questions as $question) {
                $correctOption = $question->options->firstWhere('is_correct', true);
                if ($correctOption && (int) ($given[$question->id] ?? 0) === (int) $correctOption->id) {
                    $correct++;
                }
            }

            return round(($correct / $possible) * 100, 1);
        }

        if ($part->part_type === 'situations_to_ads_with_x') {
            $rows = $part->lesenSituationAnswers;
            $possible = $rows->count();
            if ($possible === 0) {
                return null;
            }
            $given = (array) ($answerJson['situation_assignments'] ?? []);
            $correct = 0;
            foreach ($rows as $answerRow) {
                $selected = $given[$answerRow->lesen_situation_id] ?? null;
                if ($answerRow->is_no_match) {
                    if ($selected === 'X') {
                        $correct++;
                    }
                } elseif ((int) $selected === (int) $answerRow->correct_ad_id) {
                    $correct++;
                }
            }

            return round(($correct / $possible) * 100, 1);
        }

        if ($part->part_type === 'sprachbausteine_email_gap_mcq') {
            $questions = $part->sprachGapQuestions;
            $possible = $questions->count();
            if ($possible === 0) {
                return null;
            }
            $given = (array) ($answerJson['gap_choices'] ?? []);
            $correct = 0;
            foreach ($questions as $question) {
                $correctOption = $question->options->firstWhere('is_correct', true);
                if ($correctOption && (int) ($given[$question->id] ?? 0) === (int) $correctOption->id) {
                    $correct++;
                }
            }

            return round(($correct / $possible) * 100, 1);
        }

        if ($part->part_type === 'sprachbausteine_pool_gap_match') {
            $map = $part->sprachPoolAnswers->pluck('correct_option_id', 'sprach_pool_gap_id');
            $possible = $map->count();
            if ($possible === 0) {
                return null;
            }
            $given = (array) ($answerJson['pool_assignments'] ?? []);
            $correct = 0;
            foreach ($map as $gapId => $optionId) {
                if ((int) ($given[$gapId] ?? 0) === (int) $optionId) {
                    $correct++;
                }
            }

            return round(($correct / $possible) * 100, 1);
        }

        if ($part->part_type === 'hoeren_true_false') {
            $questions = $part->hoerenTrueFalseQuestions;
            $possible = $questions->count();
            if ($possible === 0) {
                return null;
            }
            $given = (array) ($answerJson['tf_choices'] ?? []);
            $correct = 0;
            foreach ($questions as $question) {
                $selected = $given[$question->id] ?? null;
                if (! in_array($selected, ['true', 'false'], true)) {
                    continue;
                }
                $isTrue = $selected === 'true';
                if ($isTrue === (bool) $question->is_true_correct) {
                    $correct++;
                }
            }

            return round(($correct / $possible) * 100, 1);
        }

        if ($part->part_type === 'writing_task') {
            $response = (array) ($answerJson['writing_response'] ?? []);
            $text = trim((string) ($response['text'] ?? ''));

            return $text === '' ? 0.0 : 100.0;
        }

        return null;
    }

    private function calculateStability(array $scores): ?int
    {
        if (count($scores) < 2) {
            return null;
        }

        $recent = array_slice($scores, 0, 5);
        $deltas = [];
        for ($i = 1; $i < count($recent); $i++) {
            $deltas[] = abs($recent[$i - 1] - $recent[$i]);
        }
        if ($deltas === []) {
            return 100;
        }

        $avgDelta = array_sum($deltas) / count($deltas);
        $stability = max(0, min(100, 100 - ($avgDelta * 2)));

        return (int) round($stability);
    }

    private function calculateConfidence(int $coverage, ?int $accuracy, ?int $stability): int
    {
        $acc = $accuracy ?? 0;
        $stab = $stability ?? $acc;
        $value = ($coverage * 0.2) + ($acc * 0.5) + ($stab * 0.3);

        return (int) round(max(0, min(100, $value)));
    }

    private function readinessStatus(int $coverage, ?int $accuracy, ?int $stability): string
    {
        if (($accuracy ?? 0) >= 75 && ($stability ?? 0) >= 60 && $coverage >= 30) {
            return 'Ready';
        }

        if (($accuracy ?? 0) >= 50 || $coverage >= 20) {
            return 'Improving';
        }

        return 'Risk';
    }

    private function builderSlotCapacities(): array
    {
        $defaults = [
            ExamSection::TYPE_LESEN => 3,
            ExamSection::TYPE_SPRACHBAUSTEINE => 2,
            ExamSection::TYPE_SCHREIBEN => 1,
        ];

        $rows = ExamSection::query()
            ->whereIn('type', array_keys($defaults))
            ->whereHas('exam', fn ($query) => $query->where('is_published', true))
            ->withCount('parts')
            ->get()
            ->groupBy('type')
            ->map(fn ($group) => (int) $group->max('parts_count'));

        return [
            'lesen' => max(1, min(12, (int) ($rows[ExamSection::TYPE_LESEN] ?? $defaults[ExamSection::TYPE_LESEN]))),
            'sprachbausteine' => max(1, min(12, (int) ($rows[ExamSection::TYPE_SPRACHBAUSTEINE] ?? $defaults[ExamSection::TYPE_SPRACHBAUSTEINE]))),
            'schreiben' => max(1, min(12, (int) ($rows[ExamSection::TYPE_SCHREIBEN] ?? $defaults[ExamSection::TYPE_SCHREIBEN]))),
        ];
    }
}
