<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Review - {{ $attempt->exam->title }} - {{ $part->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-50 pb-6 text-slate-900">
<x-exam.header
    :attempt="$attempt"
    :exam="$attempt->exam"
    :current-part="$part"
    :part-tabs="$partTabs"
    :current-part-id="$part->id"
    :completed-part-ids="$completedPartIds"
    :audio-url="$part->config_json['audio_url'] ?? null"
    :review-mode="true"
/>

<div class="border-b border-l-4 border-slate-700 border-l-[#d62828] bg-[#112442] px-4 py-2 text-xl font-bold text-white">
    {{ $part->section->title }}, {{ strtoupper($part->title) }} - REVIEW
</div>

<main class="mx-auto max-w-[1650px] px-3 py-3">
    @if($part->part_type === \App\Models\ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS)
        @php
            $assignments = (array) ($answerJson['assignments'] ?? []);
            $optionsById = $part->lesenMatchingOptions->keyBy('id');
            $correctByTextId = $part->lesenMatchingAnswers->pluck('correct_option_id', 'lesen_matching_text_id');
            $correctOptionIds = $correctByTextId->values()->map(fn ($id) => (int) $id)->all();

            $activeEntry = $part->examPartEntries->sortByDesc('id')->first();
            $activeVersion = optional($activeEntry?->versions)->where('is_active', true)->sortByDesc('id')->first()
                ?? optional($activeEntry?->versions)->sortByDesc('id')->first();

            $textSummaryByLabel = collect($activeVersion?->blocks ?? [])
                ->where('block_group', 'texts')
                ->mapWithKeys(function ($block) {
                    return [
                        (string) ($block->label ?? '') => (string) data_get($block->extra_json, 'summary', ''),
                    ];
                });
        @endphp
        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_520px] 2xl:grid-cols-[minmax(0,1fr)_620px]">
            <section class="space-y-4">
                {{-- <x-exam.instruction-box :text="$part->instruction_text" /> --}}
                <div class="space-y-4">
                    @foreach($part->lesenMatchingTexts as $text)
                        @php
                            $givenId = (int) ($assignments[$text->id] ?? 0);
                            $correctId = (int) ($correctByTextId[$text->id] ?? 0);
                            $isCorrect = $givenId > 0 && $givenId === $correctId;
                            $givenOption = $optionsById->get($givenId);
                            $correctOption = $optionsById->get($correctId);
                        @endphp
                        <article class="rounded-2xl border border-slate-300 bg-white p-3 md:p-4 shadow-sm">
                            <div class="mb-3 rounded-xl px-3 py-3 text-sm md:text-lg font-semibold leading-snug {{ $givenId === 0 ? 'bg-slate-200 text-slate-600' : ($isCorrect ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white') }}">
                                @if($givenId === 0)
                                    Keine Antwort
                                @else
                                    Ihre Antwort: {{ $givenOption?->option_key }}. {{ $givenOption?->option_text }}
                                @endif
                            </div>

                            @if(! $isCorrect)
                                <div class="mb-3 rounded-xl bg-emerald-100 px-3 py-3 text-sm md:text-base font-semibold text-emerald-900 leading-snug">
                                    Richtig: {{ $correctOption?->option_key }}. {{ $correctOption?->option_text }}
                                </div>
                            @endif

                            <div class="rounded-xl bg-slate-50 px-3 py-3 md:px-4 md:py-4">
                                <div class="text-[15px] md:text-lg leading-7 md:leading-relaxed text-slate-900">
                                    {{ $text->body_text }}
                                </div>
                            </div>

                            @php
                                $summaryText = trim((string) (
                                    $textSummaryByLabel[(string) $text->label]
                                    ?? $textSummaryByLabel[(string) $loop->iteration]
                                    ?? ''
                                ));
                            @endphp

                            @if($summaryText !== '')
                                <div class="mt-4 overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 shadow-sm">
                                    <div class="flex items-center gap-2 border-b border-amber-200/70 bg-white/50 px-4 py-3">
                                        {{-- <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 2a1 1 0 01.894.553l2 4A1 1 0 0112 8H8a1 1 0 01-.894-1.447l2-4A1 1 0 0110 2zM4 9a2 2 0 100 4 2 2 0 000-4zm12 0a2 2 0 100 4 2 2 0 000-4zM5 15a3 3 0 013-3h4a3 3 0 013 3v1H5v-1z"/>
                                            </svg>
                                        </div> --}}
                                        <div>
                                            {{-- <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-700">Quick Understanding</div> --}}
                                            <div class="text-sm font-semibold text-slate-900">Zusammenfassung</div>
                                        </div>
                                    </div>

                                    <div class="px-4 py-4 md:px-5">
                                        <div class="rounded-xl bg-white/70 px-4 py-4 text-sm md:text-[15px] leading-7 text-slate-800 rtl">
                                            {{ $summaryText }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            <aside class="rounded-2xl border border-slate-300 bg-white p-3 md:p-5 shadow-xl">
                <div class="space-y-3">
                    @foreach($part->lesenMatchingOptions as $option)
                        @php
                            $wasChosen = in_array((int) $option->id, collect($assignments)->map(fn ($value) => (int) $value)->all(), true);
                            $isCorrectOption = in_array((int) $option->id, $correctOptionIds, true);
                        @endphp
                        <div class="rounded-xl border px-3 py-3 text-sm font-semibold leading-snug md:text-lg xl:text-xl {{ $wasChosen ? ($isCorrectOption ? 'border-emerald-400 bg-emerald-500 text-white' : 'border-rose-400 bg-rose-500 text-white') : ($isCorrectOption ? 'border-emerald-300 bg-emerald-100 text-emerald-900' : 'border-indigo-200 bg-indigo-50 text-slate-900') }}">
                            {{ $option->option_key }}. {{ $option->option_text }}
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    @elseif($part->part_type === \App\Models\ExamPart::TYPE_READING_TEXT_MCQ)
        @php
            $choices = (array) ($answerJson['choices'] ?? []);
            $passage = $part->lesenMcqPassages->sortBy('sort_order')->first();
        @endphp
        <div class="grid gap-3 xl:grid-cols-[1fr_600px]">
            <section class="space-y-4">
                <x-exam.instruction-box :text="$part->instruction_text" />
                <div class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                    @if($passage?->title)
                        <h3 class="mb-3 text-2xl font-bold">{{ $passage->title }}</h3>
                    @endif
                    <div class="columns-1 gap-7 text-lg leading-relaxed md:columns-2" style="white-space: pre-line;">
                        {{ $passage?->body_text }}
                    </div>
                </div>
            </section>

            <aside class="space-y-2 pr-0.5">
                @foreach($part->lesenMcqQuestions as $index => $question)
                    @php
                        $givenId = (int) ($choices[$question->id] ?? 0);
                        $correctOption = $question->options->firstWhere('is_correct', true);
                    @endphp
                    <article class="rounded-xl border border-slate-300 bg-white p-3 shadow">
                        <h4 class="text-[17px] font-bold leading-tight">{{ $index + 1 }}. {{ $question->question_text }}</h4>
                        <div class="mt-2 space-y-1 text-[15px] leading-snug">
                            @foreach($question->options as $option)
                                @php
                                    $isCorrectOption = $correctOption && (int) $correctOption->id === (int) $option->id;
                                    $isSelected = $givenId === (int) $option->id;
                                    $classes = 'bg-white';
                                    if ($isCorrectOption) {
                                        $classes = 'bg-emerald-100 border-emerald-400';
                                    }
                                    if ($isSelected && ! $isCorrectOption) {
                                        $classes = 'bg-rose-100 border-rose-400';
                                    }
                                    if ($isSelected && $isCorrectOption) {
                                        $classes = 'bg-emerald-500 border-emerald-600 text-white';
                                    }
                                @endphp
                                <div class="rounded-lg border px-3 py-2 {{ $classes }}">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-4 w-4 rounded-full border border-slate-400 {{ $isSelected ? 'bg-blue-600 border-blue-600' : 'bg-white' }}"></span>
                                        <span>{{ $option->option_text }}</span>
                                        @if($isCorrectOption)
                                            <span class="ml-auto text-xs font-semibold uppercase">Richtig</span>
                                        @elseif($isSelected)
                                            <span class="ml-auto text-xs font-semibold uppercase">Ihre Wahl</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </aside>
        </div>
    @elseif($part->part_type === \App\Models\ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X)
        @php
            $assignments = (array) ($answerJson['situation_assignments'] ?? []);
            $adsById = $part->lesenSituationAds->keyBy('id');
            $correctRows = $part->lesenSituationAnswers->keyBy('lesen_situation_id');
        @endphp
        <div class="grid gap-3 xl:grid-cols-[1fr_600px]">
            <section class="space-y-4">
                <x-exam.instruction-box :text="$part->instruction_text" />
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach($part->lesenSituationAds as $ad)
                        <article class="rounded-md border border-slate-300 bg-white p-4 shadow-sm">
                            <div class="mb-3 min-h-8 rounded border border-indigo-200 bg-indigo-50 px-3 py-2 text-xl font-bold text-slate-900">
                                {{ $ad->label }}. {{ $ad->title }}
                            </div>
                            <div class="whitespace-pre-line text-lg leading-relaxed">{{ $ad->body_text }}</div>
                        </article>
                    @endforeach
                </div>
            </section>

            <aside class="rounded-2xl border border-slate-300 bg-white p-3 shadow-xl">
                @foreach($part->lesenSituations as $index => $situation)
                    @php
                        $givenRaw = $assignments[$situation->id] ?? null;
                        $correctRow = $correctRows->get($situation->id);
                        $isCorrect = false;
                        $correctLabel = '-';
                        if ($correctRow) {
                            if ($correctRow->is_no_match) {
                                $correctLabel = 'X';
                                $isCorrect = $givenRaw === 'X';
                            } else {
                                $correctAd = $adsById->get((int) $correctRow->correct_ad_id);
                                $correctLabel = $correctAd ? $correctAd->label.'. '.$correctAd->title : '-';
                                $isCorrect = (int) $givenRaw === (int) $correctRow->correct_ad_id;
                            }
                        }
                        $givenLabel = $givenRaw === 'X' ? 'X' : (($ad = $adsById->get((int) $givenRaw)) ? $ad->label.'. '.$ad->title : 'Keine Antwort');
                    @endphp
                    <div class="mb-2 rounded-lg border px-3 py-3 {{ $isCorrect ? 'border-emerald-300 bg-emerald-50' : 'border-rose-300 bg-rose-50' }}">
                        <div class="mb-1 text-sm font-bold text-slate-700">{{ $index + 1 }}. {{ $situation->situation_text }}</div>
                        <div class="text-sm"><span class="font-semibold">Ihre Antwort:</span> {{ $givenLabel }}</div>
                        @if(! $isCorrect)
                            <div class="text-sm font-semibold text-emerald-900"><span>Richtig:</span> {{ $correctLabel }}</div>
                        @endif
                    </div>
                @endforeach
            </aside>
        </div>
    @elseif($part->part_type === \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ)
        @php
            $choices = (array) ($answerJson['gap_choices'] ?? []);
            $passage = $part->sprachGapPassages->sortBy('sort_order')->first();
            $templateBody = $passage?->body_text ?? '';
            $templateChunks = preg_split('/\[\[(\d+)\]\]/', $templateBody, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        @endphp
        <div class="grid gap-3 xl:grid-cols-[1fr_610px]">
            <section class="space-y-4">
                <x-exam.instruction-box :text="$part->instruction_text" />
                <article class="rounded-md border border-slate-300 bg-white p-6 shadow-sm">
                    @if($passage?->title)
                        <h3 class="mb-5 text-xl font-bold text-slate-900">{{ $passage->title }}</h3>
                    @endif
                    <div class="text-2xl leading-relaxed text-slate-900">
                        @foreach($templateChunks as $chunkIndex => $chunk)
                            @if($chunkIndex % 2 === 1)
                                @php
                                    $gapNumber = (int) $chunk;
                                    $question = $part->sprachGapQuestions->firstWhere('gap_number', $gapNumber);
                                    $givenId = $question ? (int) ($choices[$question->id] ?? 0) : 0;
                                    $correctOption = $question?->options->firstWhere('is_correct', true);
                                    $givenOption = $question?->options->firstWhere('id', $givenId);
                                    $isCorrect = $question && $correctOption && $givenId > 0 && $givenId === (int) $correctOption->id;
                                @endphp
                                @if($question)
                                    <span class="mx-1 inline-flex min-h-[40px] min-w-[70px] items-center rounded-xl border-2 px-3 py-1 align-middle text-lg font-semibold {{ $givenId === 0 ? 'border-slate-300 bg-slate-100 text-slate-500' : ($isCorrect ? 'border-emerald-600 bg-emerald-500 text-white' : 'border-rose-600 bg-rose-500 text-white') }}">
                                        <span class="mr-2 inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-[#042640] px-2 text-[20px]">{{ $gapNumber }}</span>
                                        <span>{{ $givenOption?->option_text ?? '---' }}</span>
                                    </span>
                                @else
                                    <span class="mx-1 inline-block rounded-md bg-slate-300 px-3 py-1 align-middle text-sm font-semibold text-slate-700">[{{ $gapNumber }}]</span>
                                @endif
                            @else
                                <span style="white-space: pre-line;">{{ $chunk }}</span>
                            @endif
                        @endforeach
                    </div>
                </article>
            </section>

            <aside class="rounded-2xl border border-slate-300 bg-white p-3 shadow-xl">
                @foreach($part->sprachGapQuestions->sortBy('sort_order') as $question)
                    @php
                        $givenId = (int) ($choices[$question->id] ?? 0);
                        $correctOption = $question->options->firstWhere('is_correct', true);
                    @endphp
                    <article class="mb-2 rounded-md border border-slate-200 bg-white/80 px-2.5 py-2">
                        <div class="mb-2 text-sm font-bold text-slate-900">Lucke {{ $question->gap_number }}</div>
                        <div class="grid grid-cols-3 gap-1.5">
                            @foreach($question->options as $option)
                                @php
                                    $isCorrectOption = $correctOption && (int) $correctOption->id === (int) $option->id;
                                    $isSelected = $givenId === (int) $option->id;
                                    $classes = 'bg-white';
                                    if ($isCorrectOption) {
                                        $classes = 'bg-emerald-100 border-emerald-400';
                                    }
                                    if ($isSelected && ! $isCorrectOption) {
                                        $classes = 'bg-rose-100 border-rose-400';
                                    }
                                    if ($isSelected && $isCorrectOption) {
                                        $classes = 'bg-emerald-500 border-emerald-600 text-white';
                                    }
                                @endphp
                                <div class="rounded-md border px-2 py-1 text-[14px] {{ $classes }}">{{ $option->option_text }}</div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </aside>
        </div>
    @elseif($part->part_type === \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH)
        @php
            $assignments = (array) ($answerJson['pool_assignments'] ?? []);
            $poolPassage = $part->sprachPoolPassages->sortBy('sort_order')->first();
            $poolTemplateBody = $poolPassage?->body_text ?? '';
            $poolTemplateChunks = preg_split('/\[\[(\d+)\]\]/', $poolTemplateBody, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
            $optionsById = $part->sprachPoolOptions->keyBy('id');
            $correctByGapId = $part->sprachPoolAnswers->pluck('correct_option_id', 'sprach_pool_gap_id');
        @endphp
        <div class="grid gap-3 xl:grid-cols-[1fr_610px]">
            <section class="space-y-4">
                <x-exam.instruction-box :text="$part->instruction_text" />
                <article class="rounded-md border border-slate-300 bg-white p-6 shadow-sm">
                    @if($poolPassage?->title)
                        <h3 class="mb-5 text-xl font-bold text-slate-900">{{ $poolPassage->title }}</h3>
                    @endif
                    <div class="text-2xl leading-relaxed text-slate-900">
                        @foreach($poolTemplateChunks as $chunkIndex => $chunk)
                            @if($chunkIndex % 2 === 1)
                                @php
                                    $gapLabel = (string) ((int) $chunk);
                                    $gap = $part->sprachPoolGaps->firstWhere('label', $gapLabel);
                                    $givenId = $gap ? (int) ($assignments[$gap->id] ?? 0) : 0;
                                    $correctId = $gap ? (int) ($correctByGapId[$gap->id] ?? 0) : 0;
                                    $givenOption = $optionsById->get($givenId);
                                    $isCorrect = $gap && $givenId > 0 && $givenId === $correctId;
                                @endphp
                                @if($gap)
                                    <span class="mx-1 inline-flex min-h-[40px] min-w-[112px] items-center rounded-lg border px-3 py-1 text-lg font-semibold {{ $givenId === 0 ? 'border-slate-300 bg-slate-100 text-slate-500' : ($isCorrect ? 'border-emerald-600 bg-emerald-500 text-white' : 'border-rose-600 bg-rose-500 text-white') }}">
                                        {{ $givenOption?->option_text ?? '...'.$gap->label.'...' }}
                                    </span>
                                @else
                                    <span>{{ $chunk }}</span>
                                @endif
                            @else
                                <span style="white-space: pre-line;">{{ $chunk }}</span>
                            @endif
                        @endforeach
                    </div>
                </article>
            </section>

            <aside class="rounded-2xl border border-slate-300 bg-white p-3 shadow-xl">
                <div class="space-y-1.5 pr-0.5">
                    @foreach($part->sprachPoolOptions as $option)
                        @php
                            $selectedGap = collect($assignments)->search((int) $option->id, true);
                            $isCorrectSelection = $selectedGap !== false && (int) ($correctByGapId[$selectedGap] ?? 0) === (int) $option->id;
                            $isWrongSelection = $selectedGap !== false && ! $isCorrectSelection;
                            $classes = 'border-indigo-200 bg-indigo-50 text-slate-900';
                            if ($isCorrectSelection) {
                                $classes = 'border-emerald-500 bg-emerald-500 text-white';
                            } elseif ($isWrongSelection) {
                                $classes = 'border-rose-500 bg-rose-500 text-white';
                            }
                        @endphp
                        <div class="flex items-start gap-2">
                            <span class="mt-1 text-base font-semibold text-slate-700">{{ $option->option_key }}.</span>
                            <div class="w-full rounded-xl border px-3 py-1 text-[14px] font-semibold shadow-sm {{ $classes }}">
                                {{ $option->option_text }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    @elseif($part->part_type === \App\Models\ExamPart::TYPE_HOEREN_TRUE_FALSE)
        @php $choices = (array) ($answerJson['tf_choices'] ?? []); @endphp
        <div class="mx-auto max-w-[1220px]">
            <x-exam.instruction-box :text="$part->instruction_text" />
            <div class="mt-6 overflow-x-auto rounded border border-slate-200 bg-white shadow-sm">
                <table class="w-full min-w-[480px] border-collapse text-base sm:text-xl">
                    <thead>
                        <tr class="border-l-4 border-l-[#d62828] bg-[#112442] text-left text-white">
                            <th class="w-10 border-b border-slate-700 px-2 py-2 sm:w-20 sm:px-3"></th>
                            <th class="w-28 border-b border-slate-700 px-2 py-2 text-sm sm:w-44 sm:px-3 sm:text-base">Ihre Antwort</th>
                            <th class="w-28 border-b border-slate-700 px-2 py-2 text-sm sm:w-44 sm:px-3 sm:text-base">Richtig</th>
                            <th class="border-b border-slate-700 px-2 py-2 sm:px-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($part->hoerenTrueFalseQuestions as $index => $question)
                            @php
                                $givenRaw = $choices[$question->id] ?? null;
                                $given = $givenRaw === 'true' ? 'Richtig' : ($givenRaw === 'false' ? 'Falsch' : 'Keine Antwort');
                                $correct = $question->is_true_correct ? 'Richtig' : 'Falsch';
                                $isCorrect = in_array($givenRaw, ['true', 'false'], true) && (($givenRaw === 'true') === (bool) $question->is_true_correct);
                            @endphp
                            <tr class="border-b border-slate-100 last:border-0 {{ $isCorrect ? 'bg-emerald-50/60' : 'bg-rose-50/60' }}">
                                <td class="px-2 py-3 text-center font-bold sm:px-3">{{ $index + 1 }}.</td>
                                <td class="px-2 py-3 text-sm sm:px-3 sm:text-base">{{ $given }}</td>
                                <td class="px-2 py-3 text-sm font-semibold sm:px-3 sm:text-base">{{ $correct }}</td>
                                <td class="px-2 py-3 text-sm leading-snug sm:px-3 sm:text-base sm:leading-tight">{{ $question->statement_text }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($part->part_type === \App\Models\ExamPart::TYPE_WRITING_TASK)
        @php
            $response = (array) ($answerJson['writing_response'] ?? []);
            $tasks = collect($part->config_json['tasks'] ?? [])->keyBy('key');
            $selectedTask = $tasks->get($response['selected_task_key'] ?? null);
        @endphp
        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-300 bg-white p-5 shadow">
                <h3 class="text-3xl font-bold text-slate-900">{{ $selectedTask['title'] ?? $part->title }}</h3>
                @if(!empty($selectedTask['label']))
                    <div class="mt-2 text-xl font-semibold text-indigo-900">{{ $selectedTask['label'] }}</div>
                @endif
                <div class="mt-3 whitespace-pre-line text-lg leading-relaxed text-slate-800">{{ $selectedTask['prompt'] ?? $part->instruction_text }}</div>
            </div>
            <div class="rounded-xl border border-slate-300 bg-white p-5 shadow">
                <div class="mb-3 text-lg font-semibold text-slate-900">Ihr Text</div>
                <div class="min-h-[420px] whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 p-4 text-lg leading-relaxed text-slate-900">{{ trim((string) ($response['text'] ?? '')) !== '' ? $response['text'] : 'Keine Antwort eingereicht.' }}</div>
            </div>
        </div>
    @endif
</main>
</body>
</html>
