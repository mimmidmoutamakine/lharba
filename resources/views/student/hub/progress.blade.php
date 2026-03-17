<x-app-layout>
    <div class="hub-page space-y-6" dir="rtl">
        <section class="hub-surface p-6 hub-float-in">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="hub-kicker">ملخص</p>
                    <h2 class="hub-section-title">لوحة سريعة</h2>
                    <p class="mt-1 text-sm text-slate-600">اختصار مفيد بدون تعقيد.</p>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('training.instant') }}">
                        @csrf
                        <button class="hub-glow-button hub-glow-button-primary px-4 py-2 text-sm font-semibold">تدريب فوري</button>
                    </form>
                    <a href="{{ route('training.index') }}" class="hub-glow-button hub-glow-button-secondary px-4 py-2 text-sm font-semibold">فتح التدريب</a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="hub-stat-card p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">النماذج المنجزة</p>
                <p class="hub-stat-value mt-3">{{ $totalModelsAttempted }}</p>
            </article>
            <article class="hub-stat-card p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">متوسط الدقة</p>
                <p class="hub-stat-value mt-3">{{ is_null($averageScore) ? '-' : $averageScore.'%' }}</p>
            </article>
            <article class="hub-stat-card p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">أفضل قسم</p>
                <p class="mt-2 text-xl font-bold text-slate-900">{{ $bestSection ?: '-' }}</p>
            </article>
            <article class="hub-stat-card p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">أضعف قسم</p>
                <p class="mt-2 text-xl font-bold text-slate-900">{{ $weakestSection ?: '-' }}</p>
            </article>
        </section>

        <section class="hub-surface p-6 hub-float-in">
            <p class="hub-kicker">الخريطة</p>
            <h2 class="hub-section-title">حسب كل Teil</h2>
            <p class="mt-1 text-sm text-slate-600">النسبة هنا = مستوى الثقة الحالي المبني على التغطية + الدقة + الثبات.</p>
            <div class="mt-4 space-y-5">
                @foreach($progressBars as $row)
                    @php
                        $confidence = $row['confidence'];
                        $confidenceColor = $confidence >= 75 ? 'bg-emerald-600' : ($confidence >= 50 ? 'bg-amber-500' : 'bg-rose-500');
                        $statusClass = $row['status'] === 'Ready' ? 'hub-chip hub-chip-emerald' : ($row['status'] === 'Improving' ? 'hub-chip hub-chip-amber' : 'hub-chip hub-chip-rose');
                        $statusLabel = $row['status'] === 'Ready' ? 'جاهز' : ($row['status'] === 'Improving' ? 'يتحسن' : 'يحتاج عملاً');
                    @endphp
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <span class="font-medium text-slate-800">{{ $row['label'] }}</span>
                            <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                        <div class="grid gap-2 text-xs text-slate-600 sm:grid-cols-3">
                            <div>التغطية: <span class="font-semibold text-slate-900">{{ $row['coverage'] }}%</span></div>
                            <div>الدقة: <span class="font-semibold text-slate-900">{{ is_null($row['accuracy']) ? '-' : $row['accuracy'].'%' }}</span></div>
                            <div>الثبات: <span class="font-semibold text-slate-900">{{ is_null($row['stability']) ? '-' : $row['stability'].'%' }}</span></div>
                        </div>
                        <div class="mt-3">
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-700">الثقة</span>
                                <span class="font-semibold text-slate-900">{{ $confidence }}%</span>
                            </div>
                            <div class="hub-progress-track">
                                <div class="h-3 rounded {{ $confidenceColor }}" style="width: {{ $confidence }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <div class="hub-surface p-6 hub-float-in">
                <p class="hub-kicker">آخر نشاط</p>
                <h2 class="hub-section-title">نماذج حديثة</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                    @forelse($recentModels as $model)
                        <li class="rounded-2xl border border-slate-200 px-3 py-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $model['title'] }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $model['section_type'] === 'lesen' ? 'القراءة' : ($model['section_type'] === 'sprachbausteine' ? 'اللغة' : ($model['section_type'] === 'hoeren' ? 'الاستماع' : 'الكتابة')) }}
                                    </div>
                                </div>
                                @if(!empty($model['part_bank_item_id']))
                                    <form method="POST" action="{{ route('training.models.start', $model['part_bank_item_id']) }}">
                                        @csrf
                                        <button class="hub-glow-button hub-glow-button-primary px-3 py-1.5 text-xs font-semibold">أعد المحاولة</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="text-slate-500">لا يوجد سجل بعد.</li>
                    @endforelse
                </ul>
            </div>

            <div class="hub-surface p-6 hub-float-in">
                <p class="hub-kicker">التالي</p>
                <h2 class="hub-section-title">اقتراحات التدريب</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                    @forelse($recommendedSections as $section)
                        <li class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $section['label'] }}</div>
                                    <div class="text-xs text-slate-600">الثقة: {{ $section['confidence'] }}% | التغطية: {{ $section['coverage'] }}%</div>
                                </div>
                                <a href="{{ route('training.index', ['section' => $section['key']]) }}" class="hub-glow-button rounded-full bg-amber-400 px-3 py-1.5 text-xs font-semibold text-slate-950">درّب الآن</a>
                            </div>
                        </li>
                    @empty
                        <li class="text-slate-500">أكمل بعض النماذج أولاً.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
</x-app-layout>
