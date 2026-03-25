<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $attempt->exam->title }} - {{ $part->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#d9d9d9] pb-10 text-slate-900">
@if($part->part_type === \App\Models\ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS)
<div
    x-data="matchingEngine({
        attemptId: {{ $attempt->id }},
        saveUrl: '{{ route('attempts.save', $attempt) }}',
        submitUrl: '{{ route('attempts.submit', $attempt) }}',
        partId: {{ $part->id }},
        initialAssignments: @js($assignments),
        textIds: @js($part->lesenMatchingTexts->pluck('id')->values()->all()),
        options: @js($part->lesenMatchingOptions->map(fn ($o) => ['id' => $o->id, 'label' => $o->option_key.'. '.$o->option_text])->values()->all()),
        remainingSeconds: {{ $moduleRemainingSeconds }},
        textCount: {{ $part->lesenMatchingTexts->count() }},
    })"
    x-init="init()"
    class="min-h-screen"
    data-exam-scale-root
>
    <x-exam.header
        :attempt="$attempt"
        :exam="$attempt->exam"
        :part-tabs="$partTabs"
        :current-part-id="$part->id"
        :completed-part-ids="$completedPartIds"
    />

    <div class="border-b border-slate-600 bg-[#143773] px-4 py-2 text-xl font-bold text-white">
        {{ $part->section->title }}, {{ strtoupper($part->title) }}
    </div>

    {{-- MOBILE ONLY --}}
    <main class="mx-auto max-w-[1650px] px-3 py-3 xl:hidden">
        <section class="space-y-3">
            <x-exam.instruction-box :text="$part->instruction_text" />

            <div class="flex items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 py-3 shadow-sm">
                <div class="text-sm font-semibold text-slate-700">
                    <span x-text="answeredCount()"></span>/<span>{{ $part->lesenMatchingTexts->count() }}</span> beantwortet
                </div>

                <button
                    type="button"
                    class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-blue-600"
                    @click="resetPart()"
                >
                    Zurücksetzen
                </button>
            </div>

            <div class="space-y-3">
                @foreach($part->lesenMatchingTexts as $text)
                    <article
                        class="rounded-xl border border-slate-300 bg-white p-3 shadow-sm"
                        :class="mobileTextCardClass({{ (int) $text->id }})"
                    >
                        <button
                            type="button"
                            class="mb-3 flex min-h-12 w-full items-center justify-between rounded-xl border px-3 py-2 text-left"
                            :class="mobileAssignedHeaderClass({{ (int) $text->id }})"
                            @click="openMobilePicker({{ (int) $text->id }})"
                        >
                            <div class="min-w-0 flex-1">
                                <template x-if="assignments['{{ $text->id }}']">
                                    <span
                                        class="inline-flex max-w-full items-center rounded-lg bg-emerald-500 px-3 py-1.5 text-sm font-semibold text-white"
                                        x-text="optionLabel(assignments['{{ $text->id }}'])"
                                    ></span>
                                </template>

                                <template x-if="!assignments['{{ $text->id }}']">
                                    <span class="text-base font-semibold text-slate-500">
                                        Text {{ $text->label }} — Überschrift wählen
                                    </span>
                                </template>
                            </div>

                            <span class="ml-3 shrink-0 text-lg text-slate-500">⌄</span>
                        </button>

                        <div class="whitespace-pre-line text-[17px] leading-8 text-slate-900">
                            {{ $text->body_text }}
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    {{-- DESKTOP ONLY: EXACTLY YOUR OLD LAYOUT --}}
    <main class="mx-auto hidden max-w-[1650px] flex-col gap-3 px-3 py-3 xl:flex xl:flex-row">
        <section class="flex-1 space-y-4">
            <x-exam.instruction-box :text="$part->instruction_text" />
            <div id="textsColumn" class="exam-pane-height space-y-4 overflow-y-auto pr-2">
                @foreach($part->lesenMatchingTexts as $text)
                    <x-exam.text-card :text="$text" />
                @endforeach
            </div>
        </section>

        <aside class="w-full xl:w-[620px]">
            <div class="sticky top-6 rounded-2xl border border-slate-300 bg-[#eceef8] p-5 shadow-xl">
                <div id="optionPool" class="space-y-3">
                    @foreach($part->lesenMatchingOptions as $option)
                        <x-exam.option-card :option="$option" />
                    @endforeach
                </div>
            </div>

            <div class="mt-5 flex justify-center">
                <button
                    type="button"
                    class="rounded-xl bg-blue-700 px-10 py-3 text-2xl font-semibold text-white shadow hover:bg-blue-600"
                    @click="resetPart()"
                >
                    Zurücksetzen
                </button>
            </div>
        </aside>
    </main>

    {{-- MOBILE BOTTOM SHEET --}}
    <div
        x-cloak
        x-show="mobilePickerOpen"
        class="fixed inset-0 z-[80] xl:hidden"
        aria-modal="true"
        role="dialog"
    >
        <div
            class="absolute inset-0 bg-black/45"
            @click="closeMobilePicker()"
        ></div>

        <div
            x-show="mobilePickerOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 rounded-t-[28px] bg-[#eef1f8] px-4 pb-6 pt-3 shadow-2xl"
        >
            <div class="mx-auto mb-3 h-1.5 w-14 rounded-full bg-slate-300"></div>

            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-slate-500">Lesen Teil 1</div>
                    <div class="text-lg font-bold text-slate-900">
                        Text <span x-text="activeTextLabel()"></span> — Überschrift wählen
                    </div>
                </div>

                <button
                    type="button"
                    class="rounded-full bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-700"
                    @click="closeMobilePicker()"
                >
                    ✕
                </button>
            </div>

            <template x-if="activeTextId && assignments[activeTextId]">
                <div class="mb-3">
                    <button
                        type="button"
                        class="w-full rounded-xl bg-red-50 px-4 py-3 text-left font-semibold text-red-700 ring-1 ring-red-200"
                        @click="clearActiveTextAssignment()"
                    >
                        Antwort entfernen
                    </button>
                </div>
            </template>

            <div class="max-h-[58vh] space-y-2 overflow-y-auto pr-1">
                @foreach($part->lesenMatchingOptions as $option)
                    <button
                        type="button"
                        class="flex w-full items-start gap-3 rounded-2xl border px-3 py-3 text-left shadow-sm transition"
                        :class="mobileOptionButtonClass({{ (int) $option->id }})"
                        @click="chooseMobileOption({{ (int) $option->id }})"
                    >
                        <span class="mt-0.5 text-base font-bold text-slate-700">{{ $option->option_key }}.</span>
                        <span class="text-[17px] font-semibold leading-6 text-slate-900">
                            {{ $option->option_text }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- DESKTOP FOOTER ONLY --}}
    <footer class="exam-bottom-bar fixed bottom-0 left-0 right-0 hidden bg-[#001332] px-6 py-2 text-sm text-white xl:block">
        <div class="mx-auto flex max-w-[1650px] items-center justify-between">
            <div>TEST USER</div>
            <x-exam.font-controls />
            <div x-text="statusMessage"></div>
        </div>
    </footer>
</div>
@elseif($part->part_type === \App\Models\ExamPart::TYPE_READING_TEXT_MCQ)
<div
    x-data="readingMcqEngine({
        attemptId: {{ $attempt->id }},
        saveUrl: '{{ route('attempts.save', $attempt) }}',
        submitUrl: '{{ route('attempts.submit', $attempt) }}',
        partId: {{ $part->id }},
        initialChoices: @js($choices),
        questionIds: @js($part->lesenMcqQuestions->pluck('id')->values()->all()),
        optionMap: @js($part->lesenMcqQuestions->mapWithKeys(fn($q) => [$q->id => $q->options->pluck('id')->values()->all()])),
        remainingSeconds: {{ $moduleRemainingSeconds }},
    })"
    x-init="init()"
    class="min-h-screen"
    data-exam-scale-root
>
    <x-exam.header
        :attempt="$attempt"
        :exam="$attempt->exam"
        :part-tabs="$partTabs"
        :current-part-id="$part->id"
        :completed-part-ids="$completedPartIds"
    />

    <div class="border-b border-slate-600 bg-[#143773] px-4 py-2 text-xl font-bold text-white">
        {{ $part->section->title }}, {{ strtoupper($part->title) }}
    </div>

    @php $passage = $part->lesenMcqPassages->sortBy('sort_order')->first(); @endphp

    {{-- MOBILE ONLY --}}
    <main class="mx-auto max-w-[1650px] px-3 py-3 xl:hidden">
        <section class="space-y-3">
            <x-exam.instruction-box :text="$part->instruction_text" />


            <div class="sticky top-[72px] z-20 space-y-2">
                <div class="rounded-2xl border border-slate-300 bg-white/95 px-3 py-3 shadow backdrop-blur">
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        @foreach($part->lesenMcqQuestions as $index => $question)
                            <button
                                type="button"
                                class="flex h-10 min-w-10 shrink-0 items-center justify-center rounded-full border text-sm font-bold transition"
                                :class="navigatorButtonClass({{ $question->id }})"
                                @click="scrollToQuestion({{ $question->id }})"
                            >
                                {{ $index + 1 }}
                            </button>
                        @endforeach
                        <button
                            type="button"
                            class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-600"
                            @click="openPassageSheet()"
                        >
                            Text öffnen
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($part->lesenMcqQuestions as $index => $question)
                    <article
                        class="rounded-xl border border-slate-300 bg-[#eceef8] p-4 shadow scroll-mt-[180px]"
                        :class="mobileQuestionCardClass({{ $question->id }})"
                        id="question-{{ $question->id }}"
                    >
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <h4 class="text-[18px] font-bold leading-tight text-slate-900">
                                {{ $index + 1 }}. {{ $question->question_text }}
                            </h4>

                            <template x-if="choices['{{ $question->id }}']">
                                <span class="shrink-0 rounded-full bg-emerald-500 px-2.5 py-1 text-xs font-bold text-white">
                                    OK
                                </span>
                            </template>
                        </div>

                        <div class="space-y-2">
                            @foreach($question->options as $option)
                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-xl border px-3 py-3 transition"
                                    :class="mobileOptionClass({{ $question->id }}, {{ $option->id }})"
                                >
                                    <input
                                        type="radio"
                                        class="mt-1 h-5 w-5 shrink-0"
                                        name="mobile_q_{{ $question->id }}"
                                        :checked="Number(choices['{{ $question->id }}'] || 0) === {{ $option->id }}"
                                        @change="chooseAndAdvance({{ $question->id }}, {{ $option->id }})"
                                    >
                                    <span class="text-[17px] leading-6 text-slate-900">
                                        {{ $option->option_text }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-3 flex justify-between gap-2">
                            <button
                                type="button"
                                class="rounded-lg bg-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-700"
                                @click="openPassageSheet()"
                            >
                                Zum Text
                            </button>

                            <button
                                type="button"
                                class="rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 ring-1 ring-blue-200"
                                @click="goToNextUnansweredFrom({{ $question->id }})"
                            >
                                Nächste
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    {{-- DESKTOP ONLY - UNTOUCHED --}}
    <main class="mx-auto hidden max-w-[1650px] flex-col gap-3 px-3 py-3 xl:flex xl:flex-row">
        <section class="flex-1 space-y-4">
            <x-exam.instruction-box :text="$part->instruction_text" />
                
            <div class="exam-pane-height overflow-y-auto rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                @if($passage?->title)
                    <h3 class="mb-3 text-2xl font-bold">{{ $passage->title }}</h3>
                @endif
                <div class="columns-1 gap-7 text-lg leading-relaxed text-slate-900 md:columns-2" style="white-space: pre-line;">
                    {{ $passage?->body_text }}
                </div>
            </div>
        </section>

        <aside class="w-full xl:w-[600px]">
            <div class="space-y-2 pr-0.5">
                @foreach($part->lesenMcqQuestions as $index => $question)
                    <article class="rounded-xl border border-slate-300 bg-[#eceef8] p-3 shadow">
                        <h4 class="text-[17px] font-bold leading-tight">{{ $index + 1 }}. {{ $question->question_text }}</h4>
                        <div class="mt-1 space-y-0.5 text-[15px] leading-snug">
                            @foreach($question->options as $option)
                                <label class="flex cursor-pointer items-start gap-2">
                                    <input type="radio"
                                           class="mt-1 h-4 w-4"
                                           name="q_{{ $question->id }}"
                                           :checked="Number(choices['{{ $question->id }}'] || 0) === {{ $option->id }}"
                                           @change="choose({{ $question->id }}, {{ $option->id }})">
                                    <span>{{ $option->option_text }}</span>
                                </label>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </aside>
    </main>

    {{-- MOBILE PASSAGE SHEET --}}
    <div
        x-cloak
        x-show="passageSheetOpen"
        class="fixed inset-0 z-[80] xl:hidden"
        aria-modal="true"
        role="dialog"
    >   

        <div class="absolute inset-0 bg-black/45" @click="closePassageSheet()"></div>
        <div
            x-show="passageSheetOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            class="absolute inset-x-0 bottom-0 max-h-[85vh] rounded-t-[28px] bg-white px-4 pb-6 pt-3 shadow-2xl"
        >
            <div class="mx-auto mb-3 h-1.5 w-14 rounded-full bg-slate-300"></div>

            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-500">Lesen Teil 2</div>
                    @if($passage?->title)
                        <div class="text-xl font-bold text-slate-900">{{ $passage->title }}</div>
                    @else
                        <div class="text-xl font-bold text-slate-900">Text</div>
                    @endif
                </div>

                <button
                    type="button"
                    class="rounded-full bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-700"
                    @click="closePassageSheet()"
                >
                    ✕
                </button>
            </div>

            <div class="overflow-y-auto pr-1 text-[18px] leading-9 text-slate-900" style="max-height: calc(85vh - 100px); white-space: pre-line;">
                {{ $passage?->body_text }}
            </div>
        </div>
    </div>

    {{-- DESKTOP FOOTER ONLY --}}
    <footer class="exam-bottom-bar fixed bottom-0 left-0 right-0 hidden bg-[#001332] px-6 py-2 text-sm text-white xl:block">
        <div class="mx-auto flex max-w-[1650px] items-center justify-between">
            <div>TEST USER</div>
            <x-exam.font-controls />
            <div x-text="statusMessage"></div>
        </div>
    </footer>
</div>
@elseif($part->part_type === \App\Models\ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X)
<div
    x-data="situationAdsEngine({
        attemptId: {{ $attempt->id }},
        saveUrl: '{{ route('attempts.save', $attempt) }}',
        submitUrl: '{{ route('attempts.submit', $attempt) }}',
        partId: {{ $part->id }},
        initialAssignments: @js($situationAssignments),
        situations: @js($part->lesenSituations->map(fn($s) => ['id' => $s->id, 'text' => $s->situation_text])->values()->all()),
        ads: @js($part->lesenSituationAds->map(fn($a) => ['id' => $a->id, 'label' => $a->label, 'title' => $a->title, 'body' => $a->body_text])->values()->all()),
        remainingSeconds: {{ $moduleRemainingSeconds }},
    })"
    x-init="init()"
    class="min-h-screen"
    data-exam-scale-root
>
    <x-exam.header :attempt="$attempt" :exam="$attempt->exam" :part-tabs="$partTabs" :current-part-id="$part->id" :completed-part-ids="$completedPartIds" />

    <div class="border-b border-slate-600 bg-[#143773] px-4 py-2 text-xl font-bold text-white">{{ $part->section->title }}, {{ strtoupper($part->title) }}</div>
    <main class="mx-auto flex max-w-[1650px] flex-col gap-3 px-3 py-3 xl:flex-row">
        <section class="flex-1 space-y-4">
            <x-exam.instruction-box :text="$part->instruction_text" />

            <div class="exam-pane-height overflow-y-auto pr-2">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach($part->lesenSituationAds as $ad)
                        <article class="rounded-md border border-slate-300 bg-white p-4 shadow-sm"
                                 draggable="true"
                                 @dragstart="dragStartAd({{ $ad->id }}, $event)"
                                 :class="adCardClass({{ $ad->id }})"
                                 @click="selectAd({{ $ad->id }})">
                            <div class="mb-3 min-h-8 rounded bg-[#b5b8ff] px-3 py-2 text-xl font-bold text-slate-900"
                                 :class="adHeaderClass({{ $ad->id }})"
                                 @click.stop="clearAdAssignment({{ $ad->id }})"
                                 x-text="adHeaderLabel({{ $ad->id }})"></div>
                            <div class="whitespace-pre-line text-lg leading-relaxed">
                                <p class="mb-2 text-2xl font-bold">{{ $ad->label }}{{ $ad->title ? '. '.$ad->title : '' }}</p>
                                <p>{{ $ad->body_text }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <aside class="w-full xl:w-[600px]">
            <div class="rounded-2xl border border-slate-300 bg-[#eceef8] p-3 shadow-xl">
                @foreach($part->lesenSituations as $index => $situation)
                    <div class="mb-1.5 flex items-start gap-2 rounded-lg px-1.5 py-1"
                         @dragover.prevent
                         @drop.prevent="dropOnSituation({{ $situation->id }})"
                         @click="selectSituation({{ $situation->id }})"
                         :class="situationRowClass({{ $situation->id }})">
                        <div class="w-8 text-[15px] font-bold">{{ $index + 1 }}.</div>
                        <button type="button"
                                class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-full border-2 border-slate-300 bg-white text-base font-bold"
                                :class="xButtonClass({{ $situation->id }})"
                                @click.stop="toggleX({{ $situation->id }})"
                                x-text="xButtonText({{ $situation->id }})"></button>
                        <div class="-ml-6 mt-7 text-sm font-bold text-slate-700">x</div>
                        <div class="flex-1 rounded-md border border-indigo-300 bg-[#b5b8ff] px-2.5 py-1.5 text-[15px] font-semibold leading-snug"
                             :class="situationTextClass({{ $situation->id }})">
                            <div>{{ $situation->situation_text }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>
    </main>

    <footer class="exam-bottom-bar fixed bottom-0 left-0 right-0 bg-[#001332] px-6 py-2 text-sm text-white">
        <div class="mx-auto flex max-w-[1650px] items-center justify-between">
            <div>TEST USER</div>
            <x-exam.font-controls />
            <div x-text="statusMessage"></div>
        </div>
    </footer>
</div>
@elseif($part->part_type === \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ)
@php
    $passage = $part->sprachGapPassages->sortBy('sort_order')->first();
    $templateBody = $passage?->body_text ?? '';
    $templateChunks = preg_split('/\[\[(\d+)\]\]/', $templateBody, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
@endphp
<div
    x-data="sprachGapEngine({
        attemptId: {{ $attempt->id }},
        saveUrl: '{{ route('attempts.save', $attempt) }}',
        submitUrl: '{{ route('attempts.submit', $attempt) }}',
        partId: {{ $part->id }},
        initialChoices: @js($gapChoices),
        questions: @js($part->sprachGapQuestions->map(fn($q) => ['id' => $q->id, 'gap_number' => $q->gap_number, 'options' => $q->options->map(fn($o) => ['id' => $o->id, 'option_text' => $o->option_text])->values()->all()])->values()->all()),
        remainingSeconds: {{ $moduleRemainingSeconds }},
    })"
    x-init="init()"
    class="min-h-screen"
    data-exam-scale-root
>
    <x-exam.header :attempt="$attempt" :exam="$attempt->exam" :part-tabs="$partTabs" :current-part-id="$part->id" :completed-part-ids="$completedPartIds" />

    <div class="border-b border-slate-600 bg-[#143773] px-4 py-2 text-xl font-bold text-white">{{ $part->section->title }}, {{ strtoupper($part->title) }}</div>

    <main class="mx-auto flex max-w-[1650px] flex-col gap-3 px-3 py-3 xl:flex-row">
        <section class="flex-1 space-y-4">
            <x-exam.instruction-box :text="$part->instruction_text" />

            <article class="exam-pane-height overflow-y-auto rounded-md border border-slate-300 bg-white p-6 shadow-sm">
                @if($passage?->title)
                    <h3 class="mb-5 text-xl font-bold text-slate-900">{{ $passage->title }}</h3>
                @endif

                <div class="text-2xl leading-relaxed text-slate-900">
                    @foreach($templateChunks as $chunkIndex => $chunk)
                        @if($chunkIndex % 2 === 1)
                            @php
                                $gapNumber = (int) $chunk;
                                $question = $part->sprachGapQuestions->firstWhere('gap_number', $gapNumber);
                            @endphp
                            @if($question)
                                <button
                                    type="button"
                                    class="mx-1 inline-flex min-h-[40px] min-w-[70px] items-center rounded-xl border-2 border-[#3d5f8a] bg-[#3d5f8a] px-3 py-1 align-middle text-lg font-semibold text-white transition"
                                    :class="gapChipClass({{ $question->id }})"
                                    @click="selectGap({{ $question->id }})"
                                >
                                    <span class="mr-2 inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-[#042640] px-2 text-[20px]">{{ $gapNumber }}</span>
                                    <span x-text="selectedLabel({{ $question->id }})"></span>
                                </button>
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

        <aside class="w-full xl:w-[610px]">
            <div class="rounded-2xl border border-slate-300 bg-[#eceef8] p-3 shadow-xl">
                @foreach($part->sprachGapQuestions->sortBy('sort_order') as $question)
                    <article class="mb-1.5 rounded-md border border-slate-200 bg-white/70 px-2.5 py-1.5 transition"
                             :class="questionCardClass({{ $question->id }})"
                             @click="selectGap({{ $question->id }})">
                        <div class="grid grid-cols-[auto_1fr] items-center gap-2">
                            <div class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-[#02203d] px-2 text-sm font-bold text-white">
                                {{ $question->gap_number }}.
                            </div>
                            <div class="grid grid-cols-3 gap-1.5">
                                @foreach($question->options as $option)
                                    <label class="grid cursor-pointer grid-cols-[18px_1fr] items-center gap-1 rounded-md px-1.5 py-0.5 text-[14px] hover:bg-[#d9dcff]">
                                        <input
                                            type="radio"
                                            class="h-4 w-4"
                                            name="gap_{{ $question->id }}"
                                            :checked="Number(choices['{{ $question->id }}'] || 0) === {{ $option->id }}"
                                            @change="choose({{ $question->id }}, {{ $option->id }})"
                                        >
                                        <span>{{ $option->option_text }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </aside>
    </main>

    <footer class="exam-bottom-bar fixed bottom-0 left-0 right-0 bg-[#001332] px-6 py-2 text-sm text-white">
        <div class="mx-auto flex max-w-[1650px] items-center justify-between">
            <div>TEST USER</div>
            <x-exam.font-controls />
            <div x-text="statusMessage"></div>
        </div>
    </footer>
</div>
@elseif($part->part_type === \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH)
@php
    $poolPassage = $part->sprachPoolPassages->sortBy('sort_order')->first();
    $poolTemplateBody = $poolPassage?->body_text ?? '';
    $poolTemplateChunks = preg_split('/\[\[(\d+)\]\]/', $poolTemplateBody, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
@endphp
<div
    x-data="sprachPoolEngine({
        attemptId: {{ $attempt->id }},
        saveUrl: '{{ route('attempts.save', $attempt) }}',
        submitUrl: '{{ route('attempts.submit', $attempt) }}',
        partId: {{ $part->id }},
        initialAssignments: @js($poolAssignments),
        gapIds: @js($part->sprachPoolGaps->pluck('id')->values()->all()),
        options: @js($part->sprachPoolOptions->map(fn($o) => ['id' => $o->id, 'label' => $o->option_text])->values()->all()),
        remainingSeconds: {{ $moduleRemainingSeconds }},
    })"
    x-init="init()"
    class="min-h-screen"
    data-exam-scale-root
>
    <x-exam.header :attempt="$attempt" :exam="$attempt->exam" :part-tabs="$partTabs" :current-part-id="$part->id" :completed-part-ids="$completedPartIds" />

    <div class="border-b border-slate-600 bg-[#143773] px-4 py-2 text-xl font-bold text-white">{{ $part->section->title }}, {{ strtoupper($part->title) }}</div>

    <main class="mx-auto flex max-w-[1650px] flex-col gap-3 px-3 py-3 xl:flex-row">
        <section class="flex-1 space-y-4">
            <x-exam.instruction-box :text="$part->instruction_text" />

            <article class="exam-pane-height overflow-y-auto rounded-md border border-slate-300 bg-white p-6 shadow-sm">
                @if($poolPassage?->title)
                    <h3 class="mb-5 text-xl font-bold text-slate-900">{{ $poolPassage->title }}</h3>
                @endif

                <div class="text-2xl leading-relaxed text-slate-900">
                    @foreach($poolTemplateChunks as $chunkIndex => $chunk)
                        @if($chunkIndex % 2 === 1)
                            @php
                                $gapLabel = (string) ((int) $chunk);
                                $gap = $part->sprachPoolGaps->firstWhere('label', $gapLabel);
                            @endphp
                            @if($gap)
                                <span class="mx-1 inline-flex align-middle"
                                      @dragover.prevent
                                      @drop.prevent="dropOnGap({{ $gap->id }})">
                                    <button
                                        type="button"
                                        class="inline-flex min-h-[40px] min-w-[112px] items-center rounded-lg border px-3 py-1 text-lg font-semibold transition"
                                        :class="gapChipClass({{ $gap->id }})"
                                        @click="handleGapClick({{ $gap->id }})"
                                    >
                                        <template x-if="assignments['{{ $gap->id }}']">
                                            <span class="rounded-md bg-emerald-500 px-2 py-0.5 text-white"
                                                  @click.stop="removeAssignment({{ $gap->id }})"
                                                  x-text="optionLabel(assignments['{{ $gap->id }}'])"></span>
                                        </template>
                                        <template x-if="!assignments['{{ $gap->id }}']">
                                            <span class="text-slate-500">...{{ $gap->label }}...</span>
                                        </template>
                                    </button>
                                </span>
                            @else
                                <span class="mx-1 inline-block rounded-md bg-slate-300 px-3 py-1 align-middle text-sm font-semibold text-slate-700">[{{ $gapLabel }}]</span>
                            @endif
                        @else
                            <span style="white-space: pre-line;">{{ $chunk }}</span>
                        @endif
                    @endforeach
                </div>
            </article>
        </section>

        <aside class="w-full xl:w-[610px]">
            <div class="sticky top-3 rounded-2xl border border-slate-300 bg-[#eceef8] p-3 shadow-xl">
                <div class="space-y-1.5 pr-0.5">
                    @foreach($part->sprachPoolOptions as $option)
                        <div class="flex items-start gap-2" @click="handleOptionClick({{ $option->id }})">
                            <span class="mt-1 text-base font-semibold text-slate-700">{{ $option->option_key }}.</span>
                            <div class="w-full rounded-xl border border-indigo-300 bg-[#b5b8ff] px-3 py-1 text-[14px] font-semibold text-slate-900 shadow-sm transition"
                                 :class="optionCardClass({{ (int) $option->id }})"
                                 draggable="true"
                                 @dragstart="dragStart({{ $option->id }}, $event)">
                                {{ $option->option_text }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </main>

    <footer class="exam-bottom-bar fixed bottom-0 left-0 right-0 bg-[#001332] px-6 py-2 text-sm text-white">
        <div class="mx-auto flex max-w-[1650px] items-center justify-between">
            <div>TEST USER</div>
            <x-exam.font-controls />
            <div x-text="statusMessage"></div>
        </div>
    </footer>
</div>
@elseif($part->part_type === \App\Models\ExamPart::TYPE_HOEREN_TRUE_FALSE)
<div
    x-data="hoerenTrueFalseEngine({
        attemptId: {{ $attempt->id }},
        saveUrl: '{{ route('attempts.save', $attempt) }}',
        submitUrl: '{{ route('attempts.submit', $attempt) }}',
        partId: {{ $part->id }},
        initialChoices: @js($tfChoices),
        questionIds: @js($part->hoerenTrueFalseQuestions->pluck('id')->values()->all()),
        audioDurationSeconds: {{ (int) ($part->config_json['audio_duration_seconds'] ?? 0) }},
        remainingSeconds: {{ $moduleRemainingSeconds }},
    })"
    x-init="init()"
    class="min-h-screen"
    data-exam-scale-root
>
    <x-exam.header
        :attempt="$attempt"
        :exam="$attempt->exam"
        :part-tabs="$partTabs"
        :current-part-id="$part->id"
        :completed-part-ids="$completedPartIds"
        :audio-url="$part->config_json['audio_url'] ?? null"
    />

    <div class="border-b border-slate-600 bg-[#143773] px-4 py-2 text-xl font-bold text-white">{{ $part->section->title }}, {{ strtoupper($part->title) }}</div>

    <main class="mx-auto max-w-[1220px] px-4 py-4">
        <x-exam.instruction-box :text="$part->instruction_text" />

        <div class="mt-6 overflow-hidden rounded border border-slate-300 bg-white shadow">
            <table class="w-full border-collapse text-xl">
                <thead>
                    <tr class="bg-[#143773] text-left text-white">
                        <th class="w-20 border border-slate-300 px-3 py-2"></th>
                        <th class="w-44 border border-slate-300 px-3 py-2">RICHTIG</th>
                        <th class="w-44 border border-slate-300 px-3 py-2">FALSCH</th>
                        <th class="border border-slate-300 px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($part->hoerenTrueFalseQuestions as $index => $question)
                        <tr class="border-b border-slate-300 transition" :class="rowClass({{ $question->id }})">
                            <td class="border border-slate-300 px-3 py-3 text-center font-bold">{{ $index + 1 }}.</td>
                            <td class="border border-slate-300 px-3 py-3 text-center">
                                <input type="radio"
                                       class="h-6 w-6"
                                       name="tf_{{ $question->id }}"
                                       :checked="choices['{{ $question->id }}'] === 'true'"
                                       @change="choose({{ $question->id }}, 'true')">
                            </td>
                            <td class="border border-slate-300 px-3 py-3 text-center">
                                <input type="radio"
                                       class="h-6 w-6"
                                       name="tf_{{ $question->id }}"
                                       :checked="choices['{{ $question->id }}'] === 'false'"
                                       @change="choose({{ $question->id }}, 'false')">
                            </td>
                            <td class="border border-slate-300 px-3 py-3 leading-tight">{{ $question->statement_text }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>

    <footer class="exam-bottom-bar fixed bottom-0 left-0 right-0 bg-[#001332] px-6 py-2 text-sm text-white">
        <div class="mx-auto flex max-w-[1650px] items-center justify-between">
            <div>TEST USER</div>
            <x-exam.font-controls />
            <div x-text="statusMessage"></div>
        </div>
    </footer>
</div>
@elseif($part->part_type === \App\Models\ExamPart::TYPE_WRITING_TASK)
@php
    $level = strtoupper((string) $attempt->exam->level);
    $isB2 = str_starts_with($level, 'B2');
    $configuredTasks = collect($part->config_json['tasks'] ?? [])->values();
    if ($configuredTasks->isEmpty()) {
        $configuredTasks = collect([
            [
                'key' => 'A',
                'label' => 'Bitte um Informationen',
                'title' => 'Aufgabe A',
                'prompt' => "Sie lesen folgende Anzeige:\n\nSecuria Versicherungen AG\n\nSie interessieren sich fur die Angebote und mochten genaue Informationen erhalten. Schreiben Sie eine E-Mail mit Fragen zu Leistungen, Preisen und Vertragsbedingungen.",
            ],
            [
                'key' => 'B',
                'label' => 'Beschwerde',
                'title' => 'Aufgabe B',
                'prompt' => "Sie haben zwei Wochen Urlaub in einem Jugendcamp gemacht und waren nicht zufrieden.\nSchreiben Sie eine Beschwerde an das Camp. Nennen Sie konkrete Probleme und schlagen Sie eine Losung vor.",
            ],
        ]);
    }
    $initialTaskKey = (string) ($writingResponse['selected_task_key'] ?? '');
    $singleTask = $configuredTasks->first();
    if (! $isB2) {
        $initialTaskKey = (string) ($configuredTasks->first()['key'] ?? 'A');
    }
@endphp
<div
    x-data="writingEngine({
        attemptId: {{ $attempt->id }},
        saveUrl: '{{ route('attempts.save', $attempt) }}',
        submitUrl: '{{ route('attempts.submit', $attempt) }}',
        partId: {{ $part->id }},
        remainingSeconds: {{ $moduleRemainingSeconds }},
        isB2: {{ $isB2 ? 'true' : 'false' }},
        tasks: @js($configuredTasks),
        initialResponse: @js($writingResponse),
        initialTaskKey: @js($initialTaskKey),
    })"
    x-init="init()"
    class="min-h-screen"
    data-exam-scale-root
>
    <x-exam.header :attempt="$attempt" :exam="$attempt->exam" :part-tabs="$partTabs" :current-part-id="$part->id" :completed-part-ids="$completedPartIds" />

    <div class="border-b border-slate-600 bg-[#143773] px-4 py-2 text-xl font-bold text-white">{{ $part->section->title }}, {{ strtoupper($part->title) }}</div>

    <main class="mx-auto max-w-[1650px] px-4 py-4">
        <x-exam.instruction-box :text="$part->instruction_text" />

        <div class="mt-4 rounded-xl border border-amber-300 bg-[#e8edb1] px-6 py-4 text-xl font-semibold text-slate-900">
            <div>Entscheiden Sie schnell, denn die zur Verfugung stehende Zeit ist begrenzt auf 30 Minuten.</div>
            @if($isB2)
                <div class="mt-1">Aufgabe A: Bitte um Informationen</div>
                <div>Aufgabe B: Beschwerde</div>
            @endif
        </div>

        @if($isB2)
            <div x-show="!selectedTaskKey" class="mt-5 grid gap-5 lg:grid-cols-2">
                <template x-for="task in tasks" :key="task.key">
                    <button type="button"
                            class="rounded-xl border border-slate-300 bg-white p-5 text-left shadow transition hover:border-indigo-300"
                            @click="selectTask(task.key)">
                        <div class="text-4xl font-bold text-slate-900" x-text="task.title || ('Aufgabe ' + task.key)"></div>
                        <div class="mt-2 text-xl font-semibold text-indigo-900" x-text="task.label || ''"></div>
                        <div class="mt-3 whitespace-pre-line text-lg leading-relaxed text-slate-800" x-text="task.prompt || ''"></div>
                    </button>
                </template>
            </div>
        @else
            <div class="mt-5 rounded-xl border border-slate-300 bg-white p-5 shadow">
                <h3 class="text-4xl font-bold text-slate-900">{{ $singleTask['title'] ?? 'Aufgabe' }}</h3>
                <div class="mt-2 whitespace-pre-line text-xl leading-relaxed text-slate-800">{{ $singleTask['prompt'] ?? '' }}</div>
            </div>
        @endif

        <div x-show="!isB2 || selectedTaskKey" class="mt-5 grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-300 bg-white p-5 shadow">
                <template x-if="isB2">
                    <div>
                        <div class="mb-2 text-xs uppercase tracking-wide text-slate-500">Doppelklick, um Aufgabe zu wechseln (Text wird geloscht)</div>
                        <button type="button" class="w-full text-left" @dblclick="clearTaskChoice()">
                            <div class="text-4xl font-bold text-slate-900" x-text="selectedTask()?.title || ''"></div>
                            <div class="mt-2 text-xl font-semibold text-indigo-900" x-text="selectedTask()?.label || ''"></div>
                            <div class="mt-3 whitespace-pre-line text-lg leading-relaxed text-slate-800" x-text="selectedTask()?.prompt || ''"></div>
                        </button>
                    </div>
                </template>
                <template x-if="!isB2">
                    <div class="text-lg leading-relaxed text-slate-800 whitespace-pre-line">{{ $singleTask['prompt'] ?? '' }}</div>
                </template>
            </div>

            <div class="rounded-xl border border-slate-300 bg-white p-5 shadow">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-2xl font-semibold text-slate-900">Sonderzeichen</div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="char in specialChars" :key="char">
                            <button type="button"
                                    class="rounded-md border border-slate-300 bg-[#eceef8] px-3 py-1 text-2xl font-bold text-slate-900 hover:border-indigo-400 hover:bg-[#dfe3ff]"
                                    @click="insertChar(char)"
                                    x-text="char"></button>
                        </template>
                    </div>
                </div>

                <textarea id="writingEditor"
                          x-model="text"
                          @input="onInput()"
                          class="h-[54vh] w-full resize-none rounded-lg border border-slate-300 bg-white p-4 text-xl leading-relaxed text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                          placeholder="Schreiben Sie hier Ihren Text..."></textarea>

                <div class="mt-3 flex items-center justify-between text-sm text-slate-600">
                    <div x-text="statusMessage"></div>
                    <div x-text="`Zeichen: ${text.length}`"></div>
                </div>
            </div>
        </div>
    </main>

    <footer class="exam-bottom-bar fixed bottom-0 left-0 right-0 bg-[#001332] px-6 py-2 text-sm text-white">
        <div class="mx-auto flex max-w-[1650px] items-center justify-between">
            <div>TEST USER</div>
            <x-exam.font-controls />
            <div x-text="statusMessage"></div>
        </div>
    </footer>
</div>
@endif
</body>
</html>



