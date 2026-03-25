@props([
    'attempt',
    'exam',
    'partTabs' => collect(),
    'currentPartId' => null,
    'completedPartIds' => [],
    'audioUrl' => null,
    'reviewMode' => false,
])

<header class="border-b border-slate-700 bg-[#112442] text-white shadow-lg">
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
                        <div class="text-sm text-slate-200">
                            Verbleibende Zeit: <span id="remainingTimeLabel">--:--</span>
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
                    <div class="hidden text-base md:block">
                        Verbleibende Zeit: <span id="remainingTimeLabel">--:--</span>
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