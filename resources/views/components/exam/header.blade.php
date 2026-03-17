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
    <div class="mx-auto flex max-w-[1650px] items-stretch justify-between gap-3 px-4 py-2">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-16 w-24 items-center justify-center rounded bg-[#d81d2a] text-center text-white">
                <div>
                    <div class="text-4xl font-bold leading-none">telc</div>
                    <div class="text-[10px] tracking-wide">LANGUAGE TESTS</div>
                </div>
            </div>

            @if ($audioUrl)
                <div class="rounded-full bg-white px-6 py-3 text-slate-900 shadow select-none">
                    <div class="flex items-center gap-3 text-xl font-semibold">
                        <span>▶</span>
                        <span id="hoerenAudioTimeLabel">0:00 / --:--</span>
                        <span>•</span>
                        <span>🔊</span>
                    </div>
                </div>
                <audio id="hoerenAudioElement" preload="auto" class="hidden" src="{{ $audioUrl }}"></audio>
            @endif

            <x-exam.part-tabs
                :part-tabs="$partTabs"
                :current-part-id="$currentPartId"
                :completed-part-ids="$completedPartIds"
                :attempt="$attempt"
                :route-name="$reviewMode ? 'attempts.review.show' : 'attempts.parts.show'"
            />
        </div>

        <div class="w-[300px] space-y-1 text-right text-sm">
            <div class="text-xl font-semibold">Deutsch - {{ strtoupper($exam->level) }}</div>
            @if ($reviewMode)
                <div class="text-base font-semibold text-emerald-200">Review-Modus</div>
                <div class="mt-1.5 flex justify-end gap-2">
                    <a href="{{ route('attempts.finished', $attempt) }}" class="rounded-md bg-emerald-700 px-3 py-1.5 font-semibold text-white shadow hover:bg-emerald-600">Zur Ergebnisseite</a>
                </div>
            @else
                <div class="text-base">Verbleibende Zeit: <span id="remainingTimeLabel">--:--</span></div>
                <div class="mt-1.5 flex justify-end gap-2">
                    <button id="submitAttemptButton" class="rounded-md bg-blue-700 px-3 py-1.5 font-semibold text-white shadow hover:bg-blue-600" type="button">ABGABE</button>
                    <button id="manualSaveButton" class="rounded-md bg-blue-600 px-3 py-1.5 font-semibold text-white shadow hover:bg-blue-500" type="button">SPEICHERN</button>
                </div>
            @endif
        </div>
    </div>
</header>
