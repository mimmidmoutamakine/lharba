<x-app-layout>
    <div class="hub-page space-y-6" dir="rtl">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="training-mode-grid relative z-10 gap-4 flex flex-col-reverse xl:grid xl:grid-cols-3" dir="ltr">
            <div class="hub-float-in hub-mode-card-shell">
                <a href="{{ route('challenge.index') }}" class="hub-mode-card hub-mode-card-yellow text-right" dir="rtl">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-xs font-semibold tracking-[0.18em] text-white/80">الأصعب</p>
                        <svg class="hub-mode-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 18c2.2-3.3 5-5.4 8.5-6.5M10 7l2.2 2.2M15.5 5l2.5 2.5M4.5 13.5l2 2" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="hub-mode-card-body">
                        <h2 class="hub-mode-card-title">تحدّي أسبوعي</h2>
                        <p class="hub-mode-card-copy">قِس راسك في التحدي وحاول تطوّل السلسلة ديالك.</p>
                    </div>
                </a>
            </div>

            <div class="hub-float-in hub-mode-card-shell">
                <a href="{{ route('training.builder') }}" class="hub-mode-card hub-mode-card-red text-right" dir="rtl">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-xs font-semibold tracking-[0.18em] text-white/80">مرن</p>
                        <svg class="hub-mode-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 4h8l2 3-6 13h-1L5 7l3-3Z" stroke="white" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="hub-mode-card-body">
                        <h2 class="hub-mode-card-title">تركيب الإمتحان</h2>
                        <p class="hub-mode-card-copy">ركّب الإمتحان كيف ما بغيتي، واختار النماذج اللي بغيتي تركز عليهم.</p>
                    </div>
                </a>
            </div>

            <div class="hub-float-in hub-mode-card-shell">
                <form method="POST" action="{{ route('training.instant') }}" class="h-full">
                    @csrf
                    <button type="submit" class="hub-mode-card hub-mode-card-black hub-mode-card-button text-right" dir="rtl">
                        <div class="flex items-start justify-between gap-4">
                            <p class="text-xs font-semibold tracking-[0.18em] text-white/80">الأسرع</p>
                            <svg class="hub-mode-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M13 2 5 14h5l-1 8 8-12h-5l1-8Z" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="hub-mode-card-body">
                            <h2 class="hub-mode-card-title">امتحان عشوائي</h2>
                            <p class="hub-mode-card-copy">ضغطة وحدة غادي تعطيك امتحان عشوائي وتمرّن عليه بحال الإمتحان ديال بصح.</p>
                        </div>
                    </button>
                </form>
            </div>
        </div>

        <section class="hub-surface training-library-surface p-6 hub-float-in">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="hub-kicker">مكتبة النماذج</p>
                    <!-- <h2 class="hub-section-title">اختر نموذجاً معيناً</h2> -->
                    <p class="mt-2 text-sm text-slate-600">اختر نموذجاً معيناً</p>
                </div>
            </div>

            <form
                method="GET"
                action="{{ route('training.index') }}"
                class="training-filter-grid mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5"
                x-data="trainingFilterBar({
                    values: {{ \Illuminate\Support\Js::from($filters) }},
                    options: {
                        section: [
                            { value: '', label: 'كل الأقسام' },
                            @foreach ($sectionChoices as $key => $choice)
                                { value: '{{ $key }}', label: '{{ $choice['label'] }}' },
                            @endforeach
                        ],
                        difficulty: [
                            { value: '', label: 'كل المستويات' },
                            { value: 'Unknown', label: 'غير معروف' },
                            { value: 'Easy', label: 'سهل' },
                            { value: 'Medium', label: 'متوسط' },
                            { value: 'Hard', label: 'صعب' }
                        ],
                        status: [
                            { value: '', label: 'كل الحالات' },
                            { value: 'Not Started', label: 'لم يبدأ' },
                            { value: 'Practiced', label: 'تمت ممارسته' },
                            { value: 'Mastered', label: 'متقن' }
                        ],
                        sort: [
                            { value: 'title', label: 'ترتيب: الاسم' },
                            { value: 'difficulty', label: 'ترتيب: الصعوبة' },
                            { value: 'attempts', label: 'ترتيب: المحاولات' },
                            { value: 'best_score', label: 'ترتيب: أفضل نتيجة' }
                        ]
                    }
                })"
                @keydown.escape.window="closeAll()"
            >
                <input type="hidden" name="section" :value="values.section">
                <input type="hidden" name="difficulty" :value="values.difficulty">
                <input type="hidden" name="status" :value="values.status">
                <input type="hidden" name="sort" :value="values.sort">

                <div class="hub-filter-popover" x-data="{ key: 'section' }">
                    <button type="button" class="hub-filter-trigger" @click="toggle(key)" :class="{ 'hub-filter-trigger-active': isOpen(key) }">
                        <span class="hub-filter-trigger-label" x-text="selectedLabel(key)"></span>
                        <span class="hub-filter-trigger-icon" :class="{ 'hub-filter-trigger-icon-open': isOpen(key) }" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="hub-filter-panel" x-show="isOpen(key)" x-cloak x-transition.origin.top.right.duration.180ms @click.outside="closeAll()">
                        <template x-for="option in options[key]" :key="`${key}-${option.value || 'all'}`">
                            <button type="button" class="hub-filter-option" :class="{ 'hub-filter-option-active': values[key] === option.value }" @click="select(key, option.value)">
                                <span x-text="option.label"></span>
                                <svg x-show="values[key] === option.value" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="hub-filter-popover" x-data="{ key: 'difficulty' }">
                    <button type="button" class="hub-filter-trigger" @click="toggle(key)" :class="{ 'hub-filter-trigger-active': isOpen(key) }">
                        <span class="hub-filter-trigger-label" x-text="selectedLabel(key)"></span>
                        <span class="hub-filter-trigger-icon" :class="{ 'hub-filter-trigger-icon-open': isOpen(key) }" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="hub-filter-panel" x-show="isOpen(key)" x-cloak x-transition.origin.top.right.duration.180ms @click.outside="closeAll()">
                        <template x-for="option in options[key]" :key="`${key}-${option.value || 'all'}`">
                            <button type="button" class="hub-filter-option" :class="{ 'hub-filter-option-active': values[key] === option.value }" @click="select(key, option.value)">
                                <span x-text="option.label"></span>
                                <svg x-show="values[key] === option.value" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="hub-filter-popover" x-data="{ key: 'status' }">
                    <button type="button" class="hub-filter-trigger" @click="toggle(key)" :class="{ 'hub-filter-trigger-active': isOpen(key) }">
                        <span class="hub-filter-trigger-label" x-text="selectedLabel(key)"></span>
                        <span class="hub-filter-trigger-icon" :class="{ 'hub-filter-trigger-icon-open': isOpen(key) }" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="hub-filter-panel" x-show="isOpen(key)" x-cloak x-transition.origin.top.right.duration.180ms @click.outside="closeAll()">
                        <template x-for="option in options[key]" :key="`${key}-${option.value || 'all'}`">
                            <button type="button" class="hub-filter-option" :class="{ 'hub-filter-option-active': values[key] === option.value }" @click="select(key, option.value)">
                                <span x-text="option.label"></span>
                                <svg x-show="values[key] === option.value" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="hub-filter-popover" x-data="{ key: 'sort' }">
                    <button type="button" class="hub-filter-trigger" @click="toggle(key)" :class="{ 'hub-filter-trigger-active': isOpen(key) }">
                        <span class="hub-filter-trigger-label" x-text="selectedLabel(key)"></span>
                        <span class="hub-filter-trigger-icon" :class="{ 'hub-filter-trigger-icon-open': isOpen(key) }" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="hub-filter-panel" x-show="isOpen(key)" x-cloak x-transition.origin.top.right.duration.180ms @click.outside="closeAll()">
                        <template x-for="option in options[key]" :key="`${key}-${option.value || 'all'}`">
                            <button type="button" class="hub-filter-option" :class="{ 'hub-filter-option-active': values[key] === option.value }" @click="select(key, option.value)">
                                <span x-text="option.label"></span>
                                <svg x-show="values[key] === option.value" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                <button class="hub-glow-button hub-glow-button-primary training-filter-submit px-4 py-3 text-sm font-semibold xl:self-stretch">تطبيق</button>
            </form>

            <div class="mt-6 overflow-x-auto hub-grid-table training-models-table-wrap">
                <table class="training-models-table min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-right">القسم</th>
                            <th class="px-4 py-3 text-right">النموذج</th>
                            <th class="px-4 py-3 text-right">الصعوبة</th>
                            <th class="px-4 py-3 text-right">المحاولات</th>
                            <th class="px-4 py-3 text-right">أفضل نتيجة</th>
                            <th class="px-4 py-3 text-right">الحالة</th>
                            <th class="px-4 py-3 text-right">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3" data-label="القسم">
                                    <span class="hub-chip hub-chip-blue">
                                        {{ $row['item']->section_type === 'lesen' ? 'القراءة' : ($row['item']->section_type === 'sprachbausteine' ? 'اللغة' : ($row['item']->section_type === 'hoeren' ? 'الاستماع' : 'الكتابة')) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-900" data-label="النموذج">{{ $row['item']->title }}</td>
                                <td class="px-4 py-3" data-label="الصعوبة">{{ $row['difficulty'] === 'Unknown' ? 'غير معروف' : ($row['difficulty'] === 'Easy' ? 'سهل' : ($row['difficulty'] === 'Medium' ? 'متوسط' : 'صعب')) }}</td>
                                <td class="px-4 py-3" data-label="المحاولات">{{ $row['attempts'] }}</td>
                                <td class="px-4 py-3" data-label="أفضل نتيجة">{{ is_null($row['best_score']) ? '-' : $row['best_score'].'%' }}</td>
                                <td class="px-4 py-3" data-label="الحالة">
                                    <span class="hub-chip {{ $row['status'] === 'Mastered' ? 'hub-chip-emerald' : ($row['status'] === 'Practiced' ? 'hub-chip-amber' : 'hub-chip-rose') }}">
                                        {{ $row['status'] === 'Mastered' ? 'متقن' : ($row['status'] === 'Practiced' ? 'تمت ممارسته' : 'لم يبدأ') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3" data-label="إجراءات">
                                    <div class="hub-inline-actions">
                                        <form method="POST" action="{{ route('training.models.start', $row['item']) }}">
                                            @csrf
                                            <button title="ابدأ" class="hub-outline-action" type="submit" aria-label="ابدأ">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M8 6.5v11l8.5-5.5L8 6.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                        </form>
                                        @if ($row['item']->part_type === \App\Models\ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS)
                                            <a title="طباعة" href="{{ route('training.models.print', $row['item']) }}" target="_blank" class="hub-outline-action" aria-label="طباعة">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M7 8V5.5A1.5 1.5 0 0 1 8.5 4h7A1.5 1.5 0 0 1 17 5.5V8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <rect x="5" y="14" width="14" height="6" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                                                    <rect x="4" y="8" width="16" height="7" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                                    <path d="M8 17h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                                </svg>
                                            </a>
                                        @endif
                                        <a title="جدولة" href="{{ route('training.builder') }}" class="hub-outline-action" aria-label="جدولة">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                                                <path d="M12 8v4l2.5 2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                        <a title="مفضلة" href="{{ route('training.builder') }}" class="hub-outline-action" aria-label="مفضلة">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="m12 4 2.35 4.76 5.25.76-3.8 3.7.9 5.23L12 16l-4.7 2.45.9-5.23-3.8-3.7 5.25-.76L12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-slate-500">لا توجد نتائج مطابقة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
