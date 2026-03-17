<x-app-layout>
    <div class="hub-page space-y-8" dir="rtl">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="hub-dot-stage hub-challenge-stage">
            <div class="relative z-10 space-y-8">
                @foreach ($groups as $group)
                    <div class="hub-float-in space-y-3">
                        <h2 class="hub-challenge-heading">{{ $group['title'] }}</h2>

                        <div class="hub-challenge-grid {{ $group['items']->count() === 2 ? 'hub-challenge-grid-two' : 'hub-challenge-grid-three' }}" dir="ltr">
                            @foreach ($group['items'] as $item)
                                <div class="hub-mode-card-shell">
                                    @if ($item['available'])
                                        <a
                                            href="{{ route('challenge.start.link', ['challengeKey' => $item['key']]) }}"
                                            class="hub-challenge-pill hub-challenge-pill-{{ $group['theme'] }} hub-mode-card-button"
                                            dir="ltr"
                                        >
                                            {{ $item['label'] }}
                                        </a>
                                    @else
                                        <div
                                            class="hub-challenge-pill hub-challenge-pill-{{ $group['theme'] }} hub-challenge-pill-disabled"
                                            dir="ltr"
                                            aria-disabled="true"
                                        >
                                            {{ $item['label'] }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <details class="hub-surface p-5 hub-float-in group" dir="rtl">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-right">
                <div>
                    <p class="hub-kicker">لوحة التحدّي</p>
                    <h2 class="hub-section-title">أفضل النتائج</h2>
                    <p class="mt-2 text-sm text-slate-600">أفضل سلسلة ديالك حتى الآن: {{ $personalBest }} جولات.</p>
                </div>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition group-open:rotate-180">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </summary>

            <div class="mt-5 overflow-hidden hub-grid-table">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-right">الرتبة</th>
                            <th class="px-4 py-2 text-right">الطالب</th>
                            <th class="px-4 py-2 text-right">الجولات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($leaderboard as $index => $row)
                            <tr>
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">{{ $row['name'] }}</td>
                                <td class="px-4 py-2 font-semibold">{{ $row['rounds'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-4 text-slate-500" colspan="3">لا توجد محاولات بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>
    </div>
</x-app-layout>
