<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prufung versendet</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-50 text-slate-900">
    <main class="min-h-screen px-4 py-8 sm:px-6 sm:py-12">
        <div class="mx-auto max-w-[1280px] space-y-5">

            {{-- Header card --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
                <div class="border-b border-l-4 border-slate-200 border-l-[#d62828] bg-[#112442] px-5 py-6 sm:px-8">
                    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">

                        <div class="flex h-16 w-20 shrink-0 items-center justify-center rounded-lg bg-[#d62828] text-center text-white">
                            <div>
                                <div class="text-2xl font-bold leading-none">Lharba</div>
                                <div class="text-[8px] tracking-widest opacity-80">liman istata3</div>
                            </div>
                        </div>

                        <div class="text-center sm:text-left">
                            @if(!empty($isSurvival))
                                <div class="text-2xl font-bold text-white sm:text-3xl">
                                    Survival Mode — Runde {{ $survivalRound }}
                                </div>
                                <div class="mt-1 text-base text-slate-300 sm:text-lg">
                                    @if(!empty($survivalPassed))
                                        Runde bestanden. Bereit fur die nachste Runde?
                                    @else
                                        Runde nicht bestanden. Challenge beendet.
                                    @endif
                                </div>
                            @else
                                <div class="text-xl font-bold text-white sm:text-3xl" dir="rtl">
                                    تــيلك بــحر وقلال نينجاوات
                                </div>
                                <div class="mt-1 text-sm text-slate-300 sm:text-base" dir="rtl">
                                    شوف فالأسفل الأجوبة ديالك والحلول ديالهم ولا ضغط على تصحيح باش تراجع الإمتحان
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="flex flex-wrap items-center justify-center gap-3 px-5 py-5 sm:px-8">
                    @if(!empty($isSurvival) && !empty($survivalPassed))
                        <form method="POST" action="{{ route('challenge.start') }}" class="inline-flex">
                            @csrf
                            <input type="hidden" name="round" value="{{ (int) $survivalRound + 1 }}">
                            <button type="submit"
                                    class="inline-flex items-center rounded-xl bg-[#d62828] px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-red-700">
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
                                    class="inline-flex items-center rounded-xl bg-emerald-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-emerald-700">
                                إعادة المحاولة
                            </button>
                        </form>
                    @endif

                    @if(!empty($reviewStartPartId))
                        <a href="{{ route('attempts.review.show', [$attempt, $reviewStartPartId]) }}"
                           class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                            تصحيح
                        </a>
                    @endif

                    <a href="{{ route('progress.index') }}"
                       class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                        إحصائيات
                    </a>

                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center rounded-xl bg-slate-800 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-slate-700">
                        الصفحة الرئيسية
                    </a>
                </div>
            </div>

            {{-- Answer review --}}
            @if(!empty($reviewParts))
                <div class="space-y-4">
                    <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">Antwort-Review</h2>
                    @foreach($reviewParts as $part)
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">{{ $part['title'] }}</h3>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600">
                                    {{ $part['correct'] }}/{{ $part['total'] }} richtig
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-slate-50 text-left">
                                            <th class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-600">Aufgabe</th>
                                            <th class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-600">Ihre Antwort</th>
                                            <th class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-600">Richtig</th>
                                            <th class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-600">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($part['items'] as $item)
                                            <tr class="border-b border-slate-100 last:border-0 {{ $item['ok'] ? 'bg-emerald-50/60' : 'bg-rose-50/60' }}">
                                                <td class="px-4 py-3 font-medium text-slate-900">{{ $item['label'] }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $item['your'] }}</td>
                                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $item['correct'] }}</td>
                                                <td class="px-4 py-3">
                                                    @if($item['ok'])
                                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Richtig</span>
                                                    @else
                                                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800">Falsch</span>
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

        </div>
    </main>
</body>
</html>
