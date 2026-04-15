<x-app-layout>
    <div class="hub-page space-y-5" dir="rtl">

        <section class="hub-surface p-8 sm:p-10">
            <div class="flex items-start justify-between gap-6">
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        قريباً
                    </div>
                    <h1 class="hub-section-title">دروس قصيرة وواضحة</h1>
                    <p class="max-w-2xl text-base leading-8 text-slate-600">
                        هنا غادي نجمعو الشروحات، النصائح، وطريقة التعامل مع كل Teil بشكل مبسط.
                    </p>
                </div>
                <span class="hidden sm:inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                        <path d="M5 6.5A2.5 2.5 0 0 1 7.5 4H20v14H7.5A2.5 2.5 0 0 0 5 20.5v-14Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M5 20.5H18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach(['Lesen', 'Schreiben', 'Hören', 'Sprachbausteine'] as $topic)
                <div class="hub-surface flex items-center gap-4 p-5 opacity-50 select-none">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M8 12h8M8 8h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-slate-700">{{ $topic }}</p>
                        <p class="text-xs text-slate-400">قيد الإعداد</p>
                    </div>
                </div>
            @endforeach
        </section>

    </div>
</x-app-layout>
