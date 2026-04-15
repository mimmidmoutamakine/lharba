<x-app-layout>
    <div class="hub-page space-y-5" dir="rtl">

        <section class="hub-surface p-8 sm:p-10">
            <div class="flex items-start justify-between gap-6">
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        قريباً
                    </div>
                    <h1 class="hub-section-title">آخر المستجدات</h1>
                    <p class="max-w-2xl text-base leading-8 text-slate-600">
                        من هنا نقدروا نعرضو الجديد ديال المنصة، التحديثات، والتنبيهات المهمة للطلبة.
                    </p>
                </div>
                <span class="hidden sm:inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                        <path d="M5 6h11a3 3 0 0 1 3 3v8H8a3 3 0 0 1-3-3V6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M8 18a3 3 0 0 0 3 3h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M8.5 10h7M8.5 13h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
            </div>
        </section>

        <section class="space-y-3">
            @foreach(range(1, 3) as $i)
                <div class="hub-surface flex items-start gap-4 p-5 opacity-40 select-none">
                    <div class="mt-1 h-10 w-10 shrink-0 rounded-xl bg-slate-100"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 w-3/4 rounded-full bg-slate-200"></div>
                        <div class="h-3 w-1/2 rounded-full bg-slate-100"></div>
                    </div>
                </div>
            @endforeach
        </section>

    </div>
</x-app-layout>
