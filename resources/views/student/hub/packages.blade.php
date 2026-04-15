<x-app-layout>
    <div class="hub-page space-y-5" dir="rtl">

        <section class="hub-surface p-8 sm:p-10">
            <div class="flex items-start justify-between gap-6">
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        قريباً
                    </div>
                    <h1 class="hub-section-title">نظرة سريعة على الباقات</h1>
                    <p class="max-w-2xl text-base leading-8 text-slate-600">
                        مساحة خاصة بالباقات والخيارات المستقبلية ديال المنصة. قريباً غادي تلقاو هنا كل التفاصيل.
                    </p>
                </div>
                <span class="hidden sm:inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3l8 4.5v9L12 21 4 16.5v-9L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M12 12 20 7.5M12 12 4 7.5M12 12v9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            @foreach(['أساسي', 'متقدم', 'احترافي'] as $tier)
                <div class="hub-surface flex flex-col items-center gap-3 p-6 text-center opacity-45 select-none">
                    <div class="h-12 w-12 rounded-2xl bg-slate-100"></div>
                    <p class="font-black text-slate-700">{{ $tier }}</p>
                    <div class="h-3 w-20 rounded-full bg-slate-200"></div>
                    <div class="mt-2 h-9 w-full rounded-full bg-slate-100"></div>
                </div>
            @endforeach
        </section>

    </div>
</x-app-layout>
