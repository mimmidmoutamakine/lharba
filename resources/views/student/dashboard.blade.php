<x-app-layout>
    <div class="hub-page" dir="rtl">
        <section class="hub-dot-stage">
            <div class="relative z-10 gap-5 flex flex-col-reverse xl:grid xl:grid-cols-3" dir="ltr">
                <div class="hub-float-in hub-mode-card-shell">
                    <a href="{{ route('progress.index') }}" class="hub-mode-card hub-mode-card-yellow text-right" dir="rtl">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-xs font-semibold tracking-[0.18em] text-white/80">المتابعة</p>
                        <svg class="hub-mode-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 46a20 20 0 1140 0" stroke="white" stroke-width="3.5" stroke-linecap="round"/>
                            <path d="M32 18v8" stroke="white" stroke-width="3.5" stroke-linecap="round"/>
                            <path d="M18 24l5 5" stroke="white" stroke-width="3.5" stroke-linecap="round"/>
                            <path d="M46 24l-5 5" stroke="white" stroke-width="3.5" stroke-linecap="round"/>
                            <path d="M32 46l11-11" stroke="white" stroke-width="4.5" stroke-linecap="round"/>
                            <circle cx="32" cy="46" r="3.5" stroke="white" stroke-width="2.5"/>
                        </svg>
                    </div>
                    <div class="hub-mode-card-body">
                        <h2 class="hub-mode-card-title">إحصائيات</h2>
                        <p class="hub-mode-card-copy">شوف فين وصلتي دابا وشنو باقي خاصك تكمل.</p>
                    </div>
                    </a>
                </div>

                <div class="hub-float-in hub-mode-card-shell">
                    <a href="{{ route('challenge.index') }}" class="hub-mode-card hub-mode-card-red text-right" dir="rtl">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-xs font-semibold tracking-[0.18em] text-white/80">التحدّي</p>
                        <svg class="hub-mode-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 50c18-4 22-18 20-34 10 8 14 18 12 30" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M24 40c4-2 8-6 10-12 6 5 8 10 7 16" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 52c5-3 10-3 15 0" stroke="white" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="hub-mode-card-body">
                        <h2 class="hub-mode-card-title">تحديات</h2>
                        <p class="hub-mode-card-copy">جولات متتالية. خطأ واحد ويوقف التحدي.</p>
                    </div>
                    </a>
                </div>

                <div class="hub-float-in hub-mode-card-shell">
                    <a href="{{ route('training.index') }}" class="hub-mode-card hub-mode-card-black text-right" dir="rtl">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-xs font-semibold tracking-[0.18em] text-white/80">اليوم</p>
                        <svg class="hub-mode-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M16 12l12 10-10 12L8 26l8-14z" stroke="white" stroke-width="3.5" stroke-linejoin="round"/>
                            <path d="M36 10l14 6-4 16-14-6 4-16z" stroke="white" stroke-width="3.5" stroke-linejoin="round"/>
                            <path d="M22 36l16 4-4 14-16-4 4-14z" stroke="white" stroke-width="3.5" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="hub-mode-card-body">
                        <h2 class="hub-mode-card-title">التدريب</h2>
                        <p class="hub-mode-card-copy">اختار القسم أو خليه يبدأ لك تدريب مباشر.</p>
                    </div>
                    </a>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
