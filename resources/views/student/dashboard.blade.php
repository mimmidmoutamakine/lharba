<x-app-layout>
    <div class="hub-page" dir="rtl">
        <section class="hub-dot-stage">
            <div class="relative z-10 gap-5 flex flex-col-reverse xl:grid xl:grid-cols-3" dir="ltr">
                <div class="hub-float-in hub-mode-card-shell">
                    <a href="{{ route('progress.index') }}" class="hub-mode-card hub-mode-card-yellow text-right" dir="rtl">
                        <div class="flex items-start justify-between gap-4">
                            <p class="text-xs font-semibold tracking-[0.18em] text-white/80">المتابعة</p>
                            <img src="{{ asset('images/hub/progress.png') }}" alt="" class="hub-mode-icon-image" aria-hidden="true">
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
                            <img src="{{ asset('images/hub/challenge.png') }}" alt="" class="hub-mode-icon-image" aria-hidden="true">
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
                            <img src="{{ asset('images/hub/training.png') }}" alt="" class="hub-mode-icon-image" aria-hidden="true">
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