<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prufung versendet</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#d9d9d9] text-slate-900">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <section class="w-full max-w-[1280px] border border-slate-400 bg-[#35557f] px-8 py-10 text-center text-white shadow-2xl">
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded bg-[#d81d2a] text-center text-white">
                <div>
                    <div class="text-5xl font-bold leading-none">telc</div>
                    <div class="text-[10px] tracking-wide">LANGUAGE TESTS</div>
                </div>
            </div>

            @if(!empty($isSurvival))
                <div class="mt-6 text-[40px] font-semibold leading-tight">
                    Survival Mode - Runde {{ $survivalRound }}
                </div>
                <div class="text-[28px] font-medium leading-tight">
                    @if(!empty($survivalPassed))
                        Runde bestanden. Bereit fur die nachste Runde?
                    @else
                        Runde nicht bestanden. Challenge beendet.
                    @endif
                </div>
            @else
                <div class="mt-6 text-[44px] font-semibold leading-tight">
                    Ich danke Ihnen!
                </div>
                <div class="text-[28px] font-medium leading-tight">
                    Prufung abgegeben. Unten sehen Sie Ihre Losungen und die richtigen Antworten.
                </div>
            @endif

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                @if(!empty($isSurvival) && !empty($survivalPassed))
                    <form method="POST" action="{{ route('challenge.start') }}" class="inline-flex">
                        @csrf
                        <input type="hidden" name="round" value="{{ (int) $survivalRound + 1 }}">
                        <button type="submit"
                                class="inline-flex rounded-md border border-amber-300 bg-amber-500 px-9 py-4 text-[20px] font-semibold text-white shadow hover:bg-amber-600">
                            Nachste Runde starten
                        </button>
                    </form>
                @endif

                @if(!empty($retrainKeys))
                    <form method="POST" action="{{ route('training.targeted') }}" class="inline-flex">
                        @csrf
                        @foreach($retrainKeys as $key)
                            <input type="hidden" name="sections[]" value="{{ $key }}">
                        @endforeach
                        <button type="submit"
                                class="inline-flex rounded-md border border-emerald-300 bg-emerald-500 px-6 py-3 text-lg font-semibold text-white shadow hover:bg-emerald-600">
                            Fehlerbereiche jetzt trainieren
                        </button>
                    </form>
                @endif

                @if(!empty($reviewStartPartId))
                    <a href="{{ route('attempts.review.show', [$attempt, $reviewStartPartId]) }}"
                       class="inline-flex rounded-md border border-white/30 bg-white/10 px-6 py-3 text-lg font-semibold text-white shadow hover:bg-white/20">
                        Fehler im Quiz-Modus ansehen
                    </a>
                @endif

                <form method="POST" action="{{ route('training.continue-plan') }}" class="inline-flex">
                    @csrf
                    <button type="submit"
                            class="inline-flex rounded-md border border-amber-300 bg-amber-500 px-6 py-3 text-lg font-semibold text-white shadow hover:bg-amber-600">
                        Mit Plan fortsetzen
                    </button>
                </form>

                <a href="{{ route('progress.index') }}"
                   class="inline-flex rounded-md border border-indigo-300 bg-indigo-500 px-6 py-3 text-lg font-semibold text-white shadow hover:bg-indigo-600">
                    Fortschritt ansehen
                </a>

                <a href="{{ route('dashboard') }}"
                   class="inline-flex rounded-md border border-blue-500 bg-[#11357b] px-6 py-3 text-lg font-semibold text-white shadow hover:bg-[#0f2f6d]">
                    Zuruck auf die Startseite
                </a>
            </div>

            @if(!empty($reviewParts))
                <div class="mt-10 space-y-5 text-left text-slate-900">
                    <h2 class="text-center text-3xl font-bold text-white">Antwort-Review</h2>
                    @foreach($reviewParts as $part)
                        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-xl font-bold">{{ $part['title'] }}</h3>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold">{{ $part['correct'] }}/{{ $part['total'] }} richtig</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-slate-100 text-left">
                                            <th class="border border-slate-200 px-3 py-2">Aufgabe</th>
                                            <th class="border border-slate-200 px-3 py-2">Ihre Antwort</th>
                                            <th class="border border-slate-200 px-3 py-2">Richtig</th>
                                            <th class="border border-slate-200 px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($part['items'] as $item)
                                            <tr class="{{ $item['ok'] ? 'bg-emerald-50' : 'bg-rose-50' }}">
                                                <td class="border border-slate-200 px-3 py-2 font-medium">{{ $item['label'] }}</td>
                                                <td class="border border-slate-200 px-3 py-2">{{ $item['your'] }}</td>
                                                <td class="border border-slate-200 px-3 py-2 font-semibold">{{ $item['correct'] }}</td>
                                                <td class="border border-slate-200 px-3 py-2">
                                                    @if($item['ok'])
                                                        <span class="rounded bg-emerald-600 px-2 py-1 text-xs font-semibold text-white">Richtig</span>
                                                    @else
                                                        <span class="rounded bg-rose-600 px-2 py-1 text-xs font-semibold text-white">Falsch</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
</body>
</html>
