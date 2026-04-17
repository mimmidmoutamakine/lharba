@props([
    'attempt',
    'exam',
    'currentPart' => null,
    'partTabs' => collect(),
    'currentPartId' => null,
    'completedPartIds' => [],
    'audioUrl' => null,
    'reviewMode' => false,
])

@php
    $breadcrumbPart = $currentPart ?? $partTabs->firstWhere('id', $currentPartId);
    $breadcrumbSection = $breadcrumbPart?->section?->title ?? '';
    $breadcrumbPartTitle = $breadcrumbPart?->title ?? '';
    $breadcrumbContentTitle = $breadcrumbPart?->bankItem?->source_label ?? '';
    $breadcrumb = trim($breadcrumbSection . ' ' . $breadcrumbPartTitle);
    if ($breadcrumbContentTitle) {
        $breadcrumb .= ': ' . $breadcrumbContentTitle;
    }
@endphp

@unless($reviewMode)
<script>
    window._examRespectTime  = {{ $attempt->respect_time ? 'true' : 'false' }};
    window._examTimeModeUrl  = '{{ route('attempts.time-mode', $attempt) }}';
</script>
@endunless

<header class="border-b border-slate-700 bg-[#112442] text-white shadow-lg">
    @if($breadcrumb)
    <div class="mx-auto max-w-[1650px] px-3 pt-1.5 sm:px-4">
        <div class="flex items-center gap-1.5 text-xs text-slate-400">
            <a href="{{ route('training.index') }}" class="flex items-center gap-1 hover:text-white transition-colors">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none">
                    <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>تدريب</span>
            </a>
            <span class="opacity-30">/</span>
            <span class="truncate text-slate-300">{{ $breadcrumb }}</span>
        </div>
    </div>
    @endif
    <div class="mx-auto max-w-[1650px] px-3 py-2 sm:px-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-stretch md:justify-between">

            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-12 w-16 shrink-0 items-center justify-center rounded bg-[#d81d2a] text-center text-white sm:h-16 sm:w-24">
                    <div>
                        <div class="text-2xl font-bold leading-none sm:text-4xl">telc</div>
                        <div class="text-[7px] tracking-wide sm:text-[10px]">LANGUAGE TESTS</div>
                    </div>
                </div>

                <div class="min-w-0 flex-1 md:hidden">
                    <div class="truncate text-lg font-semibold">Deutsch - {{ strtoupper($exam->level) }}</div>
                    @if (!$reviewMode)
                        <div class="flex items-center gap-1.5 text-sm text-slate-200">
                            <button onclick="window.dispatchEvent(new CustomEvent('exam-time-toggle'))"
                                    class="time-mode-btn {{ $attempt->respect_time ? '' : 'time-mode-off' }} opacity-80">
                                <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5">
                                    <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M10 6v4l2.5 2.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M3.5 3.5l13 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" class="time-mode-slash"/>
                                </svg>
                            </button>
                            <span class="remainingTimeLabel font-mono">--:--</span>
                        </div>
                    @endif
                </div>

                <div class="hidden min-w-0 md:flex md:flex-1 md:items-center md:gap-3">
                    @if ($audioUrl)
                        <div class="rounded-full bg-white px-6 py-3 text-slate-900 shadow select-none">
                            <div class="flex items-center gap-3 text-xl font-semibold">
                                <span>▶</span>
                                <span id="hoerenAudioTimeLabel">0:00 / --:--</span>
                                <span>•</span>
                                <span>🔊</span>
                            </div>
                        </div>
                    @endif

                    <x-exam.part-tabs
                        :part-tabs="$partTabs"
                        :current-part-id="$currentPartId"
                        :completed-part-ids="$completedPartIds"
                        :attempt="$attempt"
                        :route-name="$reviewMode ? 'attempts.review.show' : 'attempts.parts.show'"
                    />
                </div>
            </div>

            <div class="space-y-2 md:w-[300px] md:text-right">
                <div class="hidden text-xl font-semibold md:block">Deutsch - {{ strtoupper($exam->level) }}</div>

                @if ($reviewMode)
                    <div class="flex md:justify-end">
                        <a href="{{ route('attempts.finished', $attempt) }}"
                           class="w-full rounded-md bg-emerald-700 px-3 py-2 text-center font-semibold text-white shadow hover:bg-emerald-600 md:w-auto">
                            Zur Ergebnisseite
                        </a>
                    </div>
                @else
                    <div class="hidden items-center gap-2 text-base md:flex">
                        <button id="timeModeToggleBtn"
                                onclick="window.dispatchEvent(new CustomEvent('exam-time-toggle'))"
                                title="تبديل الوقت"
                                class="time-mode-btn {{ $attempt->respect_time ? '' : 'time-mode-off' }}">
                            <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4">
                                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M10 6v4l2.5 2.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M3.5 3.5l13 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" class="time-mode-slash"/>
                            </svg>
                        </button>
                        <span>Verbleibende Zeit:</span>
                        <span class="remainingTimeLabel font-mono">--:--</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 md:flex md:justify-end">
                        <button id="submitAttemptButton"
                                class="rounded-md bg-blue-700 px-3 py-2 font-semibold text-white shadow hover:bg-blue-600"
                                type="button">
                            ABGABE
                        </button>
                        <button id="manualSaveButton"
                                class="rounded-md bg-blue-600 px-3 py-2 font-semibold text-white shadow hover:bg-blue-500"
                                type="button">
                            SPEICHERN
                        </button>
                    </div>
                @endif
            </div>

            <div class="md:hidden">
                <x-exam.part-tabs
                    :part-tabs="$partTabs"
                    :current-part-id="$currentPartId"
                    :completed-part-ids="$completedPartIds"
                    :attempt="$attempt"
                    :route-name="$reviewMode ? 'attempts.review.show' : 'attempts.parts.show'"
                />
            </div>
        </div>
    </div>
</header>

@unless($reviewMode)
{{-- Sentinel: sits just below the header; observed to know when header leaves viewport --}}
<div id="examHeaderSentinel" class="h-px"></div>

{{-- Mobile sticky action bar: slides up when header scrolls out of view --}}
<div id="examMobileStickyBar"
     class="fixed bottom-0 left-0 right-0 z-30 flex translate-y-full gap-2 bg-[#001332] px-4 py-2 shadow-lg transition-transform duration-300 md:hidden">
    <button onclick="document.getElementById('submitAttemptButton').click()"
            class="flex-1 rounded-md bg-blue-700 px-3 py-2.5 text-sm font-semibold text-white shadow active:bg-blue-800"
            type="button">
        ABGABE
    </button>
    <button onclick="document.getElementById('manualSaveButton').click()"
            class="flex-1 rounded-md bg-blue-600 px-3 py-2.5 text-sm font-semibold text-white shadow active:bg-blue-700"
            type="button">
        SPEICHERN
    </button>
</div>

<script>
(function () {
    var sentinel = document.getElementById('examHeaderSentinel');
    var bar      = document.getElementById('examMobileStickyBar');
    if (!sentinel || !bar) return;
    var observer = new IntersectionObserver(function (entries) {
        var headerVisible = entries[0].isIntersecting;
        bar.classList.toggle('translate-y-full', headerVisible);
        bar.classList.toggle('translate-y-0',    !headerVisible);
    }, { threshold: 0 });
    observer.observe(sentinel);
})();
</script>
@endunless
