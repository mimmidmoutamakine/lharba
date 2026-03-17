<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Review - {{ $attempt->exam->title }} - {{ $part->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#d9d9d9] pb-10 text-slate-900">
<x-exam.header
    :attempt="$attempt"
    :exam="$attempt->exam"
    :part-tabs="$partTabs"
    :current-part-id="$part->id"
    :completed-part-ids="$completedPartIds"
    :audio-url="$part->config_json['audio_url'] ?? null"
    :review-mode="true"
/>

<div class="border-b border-slate-600 bg-[#143773] px-4 py-2 text-xl font-bold text-white">
    {{ $part->section->title }}, {{ strtoupper($part->title) }} - REVIEW
</div>

<main class="mx-auto max-w-[1650px] px-3 py-3">
    <div class="mb-3 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
        Quiz review: grun = richtig, rot = falsch. Sie sehen hier genau dieselbe Aufgabe noch einmal, aber im Korrekturmodus.
    </div>

    @if($part->part_type === \App\Models\ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS)
        @php
            $assignments = (array) ($answerJson['assignments'] ?? []);
            $optionsById = $part->lesenMatchingOptions->keyBy('id');
            $correctByTextId = $part->lesenMatchingAnswers->pluck('correct_option_id', 'lesen_matching_text_id');
            $correctOptionIds = $correctByTextId->values()->map(fn ($id) => (int) $id)->all();
        @endphp
        <div class="grid gap-3 xl:grid-cols-[1fr_620px]">
            <section class="space-y-4">
                <x-exam.instruction-box :text="$part->instruction_text" />
                <div class="space-y-4">
                    @foreach($part->lesenMatchingTexts as $text)
                        @php
                            $givenId = (int) ($assignments[$text->id] ?? 0);
                            $correctId = (int) ($correctByTextId[$text->id] ?? 0);
                            $isCorrect = $givenId > 0 && $givenId === $correctId;
                            $givenOption = $optionsById->get($givenId);
                            $correctOption = $optionsById->get($correctId);
                        @endphp
                        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                            <div class="mb-3 rounded-lg px-4 py-3 text-lg font-semibold {{ $givenId === 0 ? 'bg-slate-200 text-slate-600' : ($isCorrect ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white') }}">
                                @if($givenId === 0)
                                    Keine Antwort
                                @else
                                    Ihre Antwort: {{ $givenOption?->option_key }}. {{ $givenOption?->option_text }}
                                @endif
                            </div>
                            @if(! $isCorrect)
                                <div class="mb-3 rounded-lg bg-emerald-100 px-4 py-3 text-base font-semibold text-emerald-900">
                                    Richtig: {{ $correctOption?->option_key }}. {{ $correctOption?->option_text }}
                                </div>
                            @endif
                            <div class="text-lg leading-relaxed">{{ $text->body_text }}</div>
                        </article>
                    @endforeach
                </div>
            </section>

            <aside class="rounded-2xl border border-slate-300 bg-[#eceef8] p-5 shadow-xl">
                <div class="space-y-3">
                    @foreach($part->lesenMatchingOptions as $option)
                        @php
                            $wasChosen = in_array((int) $option->id, collect($assignments)->map(fn ($value) => (int) $value)->all(), true);
                            $isCorrectOption = in_array((int) $option->id, $correctOptionIds, true);
                        @endphp
                        <div class="rounded-xl border px-4 py-3 text-xl font-semibold {{ $wasChosen ? ($isCorrectOption ? 'border-emerald-400 bg-emerald-500 text-white' : 'border-rose-400 bg-rose-500 text-white') : ($isCorrectOption ? 'border-emerald-300 bg-emerald-100 text-emerald-900' : 'border-indigo-300 bg-[#b5b8ff] text-slate-900') }}">
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
                    <article class="rounded-xl border border-slate-300 bg-[#eceef8] p-3 shadow">
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
                            <div class="mb-3 min-h-8 rounded bg-[#b5b8ff] px-3 py-2 text-xl font-bold text-slate-900">
                                {{ $ad->label }}. {{ $ad->title }}
                            </div>
                            <div class="whitespace-pre-line text-lg leading-relaxed">{{ $ad->body_text }}</div>
                        </article>
                    @endforeach
                </div>
            </section>

            <aside class="rounded-2xl border border-slate-300 bg-[#eceef8] p-3 shadow-xl">
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

            <aside class="rounded-2xl border border-slate-300 bg-[#eceef8] p-3 shadow-xl">
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

            <aside class="rounded-2xl border border-slate-300 bg-[#eceef8] p-3 shadow-xl">
                <div class="space-y-1.5 pr-0.5">
                    @foreach($part->sprachPoolOptions as $option)
                        @php
                            $selectedGap = collect($assignments)->search((int) $option->id, true);
                            $isCorrectSelection = $selectedGap !== false && (int) ($correctByGapId[$selectedGap] ?? 0) === (int) $option->id;
                            $isWrongSelection = $selectedGap !== false && ! $isCorrectSelection;
                            $classes = 'border-indigo-300 bg-[#b5b8ff] text-slate-900';
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
            <div class="mt-6 overflow-hidden rounded border border-slate-300 bg-white shadow">
                <table class="w-full border-collapse text-xl">
                    <thead>
                        <tr class="bg-[#143773] text-left text-white">
                            <th class="w-20 border border-slate-300 px-3 py-2"></th>
                            <th class="w-44 border border-slate-300 px-3 py-2">Ihre Antwort</th>
                            <th class="w-44 border border-slate-300 px-3 py-2">Richtig</th>
                            <th class="border border-slate-300 px-3 py-2"></th>
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
                            <tr class="{{ $isCorrect ? 'bg-emerald-50' : 'bg-rose-50' }}">
                                <td class="border border-slate-300 px-3 py-3 text-center font-bold">{{ $index + 1 }}.</td>
                                <td class="border border-slate-300 px-3 py-3">{{ $given }}</td>
                                <td class="border border-slate-300 px-3 py-3 font-semibold">{{ $correct }}</td>
                                <td class="border border-slate-300 px-3 py-3 leading-tight">{{ $question->statement_text }}</td>
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
