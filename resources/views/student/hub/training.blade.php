<x-app-layout>
    <div class="hub-page space-y-5" dir="rtl"
         x-data="{ compact: false }"
         @scroll.window.passive="compact = ($refs.modeRow?.getBoundingClientRect().bottom ?? 999) < 10">
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

        {{-- ── Compact sticky bar — slides up when mode cards leave viewport (mobile only) ── --}}
        <div x-show="compact"
             x-cloak
             x-transition:enter="transition-transform duration-200 ease-out"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition-transform duration-150 ease-in"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             class="training-sticky-bar lg:hidden"
             dir="rtl">

            {{-- عشوائي --}}
            <form method="POST" action="{{ route('training.instant') }}" class="training-sticky-form">
                @csrf
                <button type="submit" class="training-sticky-btn training-sticky-black">
                    <span class="training-sticky-kicker">الأسرع</span>
                    <span class="training-sticky-label">عشوائي</span>
                </button>
            </form>

            <div class="training-sticky-divider"></div>

            {{-- تركيب --}}
            <a href="{{ route('training.builder') }}" class="training-sticky-btn training-sticky-red">
                <span class="training-sticky-kicker">مرن</span>
                <span class="training-sticky-label">تركيب</span>
            </a>

            <div class="training-sticky-divider"></div>

            {{-- تحدي --}}
            <a href="{{ route('challenge.index') }}" class="training-sticky-btn training-sticky-yellow">
                <span class="training-sticky-kicker">الأصعب</span>
                <span class="training-sticky-label">تحدّي</span>
            </a>
        </div>

        {{-- ── Mode cards: 3-col grid on all sizes ── --}}
        <div class="training-mode-row" x-ref="modeRow">
            {{-- عشوائي first (most-used on mobile) --}}
            <div class="training-mode-cell">
                <form method="POST" action="{{ route('training.instant') }}" class="h-full">
                    @csrf
                    <button type="submit" class="hub-mode-card hub-mode-card-black hub-mode-card-button text-right" dir="rtl">
                        <div class="flex items-start justify-between gap-3">
                            <p class="training-mode-kicker text-white/80">الأسرع</p>
                            <img src="{{ asset('images/hub/instant.png') }}" alt="" class="hub-mode-icon-image" aria-hidden="true">
                        </div>
                        <h2 class="hub-mode-card-title mt-auto">امتحان عشوائي</h2>
                        <p class="hub-mode-card-copy">ضغطة وحدة تعطيك امتحان عشوائي كامل.</p>
                    </button>
                </form>
            </div>

            {{-- تركيب --}}
            <div class="training-mode-cell">
                <a href="{{ route('training.builder') }}" class="hub-mode-card hub-mode-card-red text-right" dir="rtl">
                    <div class="flex items-start justify-between gap-3">
                        <p class="training-mode-kicker text-white/80">مرن</p>
                        <img src="{{ asset('images/hub/builder.png') }}" alt="" class="hub-mode-icon-image" aria-hidden="true">
                    </div>
                    <h2 class="hub-mode-card-title mt-auto">تركيب الإمتحان</h2>
                    <p class="hub-mode-card-copy">اختار النماذج اللي بغيتي تركز عليهم.</p>
                </a>
            </div>

            {{-- تحدي --}}
            <div class="training-mode-cell">
                <a href="{{ route('challenge.index') }}" class="hub-mode-card hub-mode-card-yellow text-right" dir="rtl">
                    <div class="flex items-start justify-between gap-3">
                        <p class="training-mode-kicker text-white/80">الأصعب</p>
                        <img src="{{ asset('images/hub/weekly-challenge.png') }}" alt="" class="hub-mode-icon-image" aria-hidden="true">
                    </div>
                    <h2 class="hub-mode-card-title mt-auto">تحدّي أسبوعي</h2>
                    <p class="hub-mode-card-copy">قِس راسك وحاول تطوّل السلسلة ديالك.</p>
                </a>
            </div>
        </div>

        {{-- ── Library ── --}}
        <section
            class="hub-surface training-library-surface p-5 sm:p-6"
            x-data="trainingFilterBar({
                values: {{ \Illuminate\Support\Js::from($filters) }},
                options: {
                    section: [
                        { value: '', label: 'الكل' },
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
            {{-- ── Sticky filter bar ── --}}
            <div class="training-filter-sticky">

                {{-- Title row --}}
                <div class="training-filter-titlerow">
                    <p class="hub-kicker">مكتبة النماذج</p>
                    <button type="button"
                            class="training-filter-reset"
                            x-show="activeFilterCount() > 0" x-cloak
                            @click="values.section=''; values.difficulty=''; values.status=''; values.sort='title'; refreshList()">
                        × إعادة ضبط
                    </button>
                </div>

                {{-- Section groups + filter toggle ── one flex row --}}
                <div class="training-filter-mainrow">
                    <div class="training-filter-row1">
                        <button type="button" class="training-grp-btn"
                                :class="{ 'training-grp-btn-active': activeGroup === '' }"
                                @click="selectGroup('', '')">الكل</button>
                        @if (collect(['lesen_t1','lesen_t2','lesen_t3'])->intersect(array_keys($sectionChoices))->isNotEmpty())
                        <button type="button" class="training-grp-btn training-grp-lesen"
                                :class="{ 'training-grp-btn-active': activeGroup === 'lesen' }"
                                @click="selectGroup('lesen', 'lesen_t1')">Lesen</button>
                        @endif
                        @if (collect(['sprach_t1','sprach_t2'])->intersect(array_keys($sectionChoices))->isNotEmpty())
                        <button type="button" class="training-grp-btn training-grp-sprach"
                                :class="{ 'training-grp-btn-active': activeGroup === 'sprach' }"
                                @click="selectGroup('sprach', 'sprach_t1')">Sprach</button>
                        @endif
                        @if (collect(['hoeren_t1','hoeren_t2'])->intersect(array_keys($sectionChoices))->isNotEmpty())
                        <button type="button" class="training-grp-btn training-grp-hoeren"
                                :class="{ 'training-grp-btn-active': activeGroup === 'hoeren' }"
                                @click="selectGroup('hoeren', 'hoeren_t1')">Hören</button>
                        @endif
                        @if (isset($sectionChoices['schreiben_t1']))
                        <button type="button" class="training-grp-btn training-grp-schreiben"
                                :class="{ 'training-grp-btn-active': activeGroup === 'schreiben' }"
                                @click="selectGroup('schreiben', 'schreiben_t1')">Schreib</button>
                        @endif
                    </div>

                    {{-- Filter toggle button --}}
                    <button type="button"
                            class="training-filter-toggle-btn"
                            :class="{ 'training-filter-toggle-btn-active': showSecondary || advancedFilterCount() > 0 }"
                            @click="showSecondary = !showSecondary"
                            :title="showSecondary ? 'إخفاء الفلاتر' : 'فلاتر إضافية'">
                        <svg viewBox="0 0 20 20" fill="none" class="training-filter-toggle-icon">
                            <path d="M3 5h14M6 10h8M9 15h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        <span class="training-filter-toggle-badge"
                              x-show="advancedFilterCount() > 0" x-cloak
                              x-text="advancedFilterCount()"></span>
                    </button>
                </div>

                {{-- Teil sub-pills (animated) --}}
                <div class="training-filter-row2"
                     x-show="activeGroup !== ''" x-cloak
                     x-transition:enter="transition-all duration-150 ease-out"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all duration-100 ease-in"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1">
                    <div class="training-sub-row training-sub-lesen" x-show="activeGroup === 'lesen'" x-cloak>
                        @foreach (['lesen_t1' => 'T1', 'lesen_t2' => 'T2', 'lesen_t3' => 'T3'] as $key => $label)
                            @if (isset($sectionChoices[$key]))
                            <button type="button" class="training-sub-pill"
                                    :class="{ 'training-sub-pill-active': values.section === '{{ $key }}' }"
                                    @click="selectAndSubmit('section', '{{ $key }}')">{{ $label }}</button>
                            @endif
                        @endforeach
                    </div>
                    <div class="training-sub-row training-sub-sprach" x-show="activeGroup === 'sprach'" x-cloak>
                        @foreach (['sprach_t1' => 'T1', 'sprach_t2' => 'T2'] as $key => $label)
                            @if (isset($sectionChoices[$key]))
                            <button type="button" class="training-sub-pill"
                                    :class="{ 'training-sub-pill-active': values.section === '{{ $key }}' }"
                                    @click="selectAndSubmit('section', '{{ $key }}')">{{ $label }}</button>
                            @endif
                        @endforeach
                    </div>
                    <div class="training-sub-row training-sub-hoeren" x-show="activeGroup === 'hoeren'" x-cloak>
                        @foreach (['hoeren_t1' => 'T1', 'hoeren_t2' => 'T2'] as $key => $label)
                            @if (isset($sectionChoices[$key]))
                            <button type="button" class="training-sub-pill"
                                    :class="{ 'training-sub-pill-active': values.section === '{{ $key }}' }"
                                    @click="selectAndSubmit('section', '{{ $key }}')">{{ $label }}</button>
                            @endif
                        @endforeach
                    </div>
                    <div class="training-sub-row training-sub-schreiben" x-show="activeGroup === 'schreiben'" x-cloak>
                        @if (isset($sectionChoices['schreiben_t1']))
                        <button type="button" class="training-sub-pill"
                                :class="{ 'training-sub-pill-active': values.section === 'schreiben_t1' }"
                                @click="selectAndSubmit('section', 'schreiben_t1')">T1</button>
                        @endif
                    </div>
                </div>

                {{-- Secondary filter panel (difficulty / status / sort) ── slides in --}}
                <div class="training-secondary-panel"
                     x-show="showSecondary" x-cloak
                     x-transition:enter="transition-all duration-180 ease-out"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all duration-120 ease-in"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2">

                    {{-- Difficulty --}}
                    <div class="training-secondary-group">
                        <span class="training-secondary-label">الصعوبة</span>
                        @foreach (['' => 'الكل', 'Easy' => 'سهل', 'Medium' => 'متوسط', 'Hard' => 'صعب'] as $val => $lbl)
                        <button type="button" class="training-meta-chip"
                                :class="{ 'training-meta-chip-active': values.difficulty === '{{ $val }}' }"
                                @click="selectAndSubmit('difficulty', '{{ $val }}')">{{ $lbl }}</button>
                        @endforeach
                    </div>

                    {{-- Status --}}
                    <div class="training-secondary-group">
                        <span class="training-secondary-label">الحالة</span>
                        @foreach (['' => 'الكل', 'Not Started' => 'لم يبدأ', 'Practiced' => 'تمارس', 'Mastered' => 'متقن'] as $val => $lbl)
                        <button type="button" class="training-meta-chip"
                                :class="{ 'training-meta-chip-active': values.status === '{{ $val }}' }"
                                @click="selectAndSubmit('status', '{{ $val }}')">{{ $lbl }}</button>
                        @endforeach
                    </div>

                    {{-- Sort --}}
                    <div class="training-secondary-group">
                        <span class="training-secondary-label">ترتيب</span>
                        @foreach (['title' => 'الاسم', 'difficulty' => 'الصعوبة', 'attempts' => 'المحاولات', 'best_score' => 'النتيجة'] as $val => $lbl)
                        <button type="button" class="training-meta-chip"
                                :class="{ 'training-meta-chip-active': values.sort === '{{ $val }}' }"
                                @click="selectAndSubmit('sort', '{{ $val }}')">{{ $lbl }}</button>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- Cards --}}
            <div id="training-cards-container" :class="{ 'opacity-60 pointer-events-none': loading }">
                @include('student.hub.partials.training-cards', ['rows' => $rows])
            </div>


        </section>
    </div>
</x-app-layout>
