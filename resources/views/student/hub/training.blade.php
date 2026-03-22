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
                        <img src="{{ asset('images/hub/weekly-challenge.png') }}" alt="" class="hub-mode-icon-image" aria-hidden="true">
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
                        <img src="{{ asset('images/hub/builder.png') }}" alt="" class="hub-mode-icon-image" aria-hidden="true">
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
                            <img src="{{ asset('images/hub/instant.png') }}" alt="" class="hub-mode-icon-image" aria-hidden="true">
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
                x-ref="filterForm"
                @keydown.escape.window="closeAll()"
            >
                <input type="hidden" name="section" :value="values.section">
                <input type="hidden" name="difficulty" :value="values.difficulty">
                <input type="hidden" name="status" :value="values.status">
                <input type="hidden" name="sort" :value="values.sort">

                <div class="hub-filter-popover" x-data="{ key: 'section' }">
                    <button type="button" class="hub-filter-trigger" @click.stop="toggle(key)" :class="{ 'hub-filter-trigger-active': isOpen(key) }">
                        <span class="hub-filter-trigger-label" x-text="selectedLabel(key)"></span>
                        <span class="hub-filter-trigger-icon" :class="{ 'hub-filter-trigger-icon-open': isOpen(key) }" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="hub-filter-panel" x-show="isOpen(key)" x-cloak x-transition.origin.top.right.duration.180ms @click.outside="closeIfSame(key)">
                        <template x-for="option in options[key]" :key="`${key}-${option.value || 'all'}`">
                            <button type="button" class="hub-filter-option" :class="{ 'hub-filter-option-active': values[key] === option.value }" @click.stop="selectAndSubmit(key, option.value)">
                                <span x-text="option.label"></span>
                                <svg x-show="values[key] === option.value" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="hub-filter-popover" x-data="{ key: 'difficulty' }">
                    <button type="button" class="hub-filter-trigger" @click.stop="toggle(key)" :class="{ 'hub-filter-trigger-active': isOpen(key) }">
                        <span class="hub-filter-trigger-label" x-text="selectedLabel(key)"></span>
                        <span class="hub-filter-trigger-icon" :class="{ 'hub-filter-trigger-icon-open': isOpen(key) }" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="hub-filter-panel" x-show="isOpen(key)" x-cloak x-transition.origin.top.right.duration.180ms @click.outside="closeIfSame(key)">
                        <template x-for="option in options[key]" :key="`${key}-${option.value || 'all'}`">
                            <button type="button" class="hub-filter-option" :class="{ 'hub-filter-option-active': values[key] === option.value }" @click.stop="selectAndSubmit(key, option.value)">
                                <span x-text="option.label"></span>
                                <svg x-show="values[key] === option.value" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="hub-filter-popover" x-data="{ key: 'status' }">
                    <button type="button" class="hub-filter-trigger" @click.stop="toggle(key)" :class="{ 'hub-filter-trigger-active': isOpen(key) }">
                        <span class="hub-filter-trigger-label" x-text="selectedLabel(key)"></span>
                        <span class="hub-filter-trigger-icon" :class="{ 'hub-filter-trigger-icon-open': isOpen(key) }" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="hub-filter-panel" x-show="isOpen(key)" x-cloak x-transition.origin.top.right.duration.180ms @click.outside="closeIfSame(key)">
                        <template x-for="option in options[key]" :key="`${key}-${option.value || 'all'}`">
                            <button type="button" class="hub-filter-option" :class="{ 'hub-filter-option-active': values[key] === option.value }" @click.stop="selectAndSubmit(key, option.value)">
                                <span x-text="option.label"></span>
                                <svg x-show="values[key] === option.value" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="hub-filter-popover" x-data="{ key: 'sort' }">
                    <button type="button" class="hub-filter-trigger" @click.stop="toggle(key)" :class="{ 'hub-filter-trigger-active': isOpen(key) }">
                        <span class="hub-filter-trigger-label" x-text="selectedLabel(key)"></span>
                        <span class="hub-filter-trigger-icon" :class="{ 'hub-filter-trigger-icon-open': isOpen(key) }" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="hub-filter-panel" x-show="isOpen(key)" x-cloak x-transition.origin.top.right.duration.180ms @click.outside="closeIfSame(key)">
                        <template x-for="option in options[key]" :key="`${key}-${option.value || 'all'}`">
                            <button type="button" class="hub-filter-option" :class="{ 'hub-filter-option-active': values[key] === option.value }" @click.stop="selectAndSubmit(key, option.value)">
                                <span x-text="option.label"></span>
                                <svg x-show="values[key] === option.value" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- <button class="hub-glow-button hub-glow-button-primary training-filter-submit px-4 py-3 text-sm font-semibold xl:self-stretch">تطبيق</button> --}}
            </form>


            <div id="training-cards-container" :class="{ 'opacity-60 pointer-events-none': loading }">
                @include('student.hub.partials.training-cards', ['rows' => $rows])
            </div>

        </section>
    </div>
</x-app-layout>
