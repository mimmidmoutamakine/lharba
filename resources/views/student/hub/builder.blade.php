<x-app-layout>
    <div class="hub-page space-y-6" dir="rtl">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="hub-hero px-6 py-6 sm:px-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-3 text-right">
                    <p class="hub-kicker">الهربة</p>
                    <h1 class="hub-section-title">ركّب الامتحان بالطريقة اللي تناسبك</h1>
                    <p class="max-w-2xl text-sm leading-7 text-slate-600">
                        اختار النماذج اللي باغي، جرّهم من اللائحة، وركّب امتحانك الخاص بترتيب واضح وبسيط.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('training.index') }}" class="hub-glow-button hub-glow-button-secondary px-4 py-2 text-sm font-semibold">
                        رجوع للتدريب
                    </a>
                </div>
            </div>
        </section>

        <div
            x-data='customExamBuilder(@json($modelsForBuilder), @json($capacities))'
            class="grid gap-5 lg:grid-cols-12 lg:gap-6"
        >
            <section class="order-1 space-y-5 lg:col-span-7 lg:space-y-6">
                <div class="hub-builder-zone p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <span class="hub-builder-chip hub-builder-chip-black" x-text="assigned.lesen.length + ' من ' + capacities.lesen"></span>
                        <h4 class="hub-builder-section-title font-black tracking-wide text-slate-950">القراءة</h4>
                    </div>
                    <div
                        class="mt-3 min-h-[180px] space-y-3 rounded-xl border border-slate-200 p-3 transition sm:min-h-[220px]"
                        :class="dropZoneClass('lesen')"
                        @dragover.prevent="onDragOver('lesen')"
                        @dragleave="clearHover('lesen')"
                        @drop="dropToSection('lesen')"
                    >
                        <template x-for="(item, index) in assigned.lesen" :key="'lesen-' + item.id + '-' + index">
                            <div
                                class="hub-builder-assigned hub-builder-assigned-black"
                                :class="{ 'hub-builder-assigned-dragging': dragMeta && dragMeta.id === item.id && dragMeta.fromSection === 'lesen' && dragMeta.fromIndex === index }"
                                draggable="true"
                                @dragstart="startDrag(item.id, 'lesen', index)"
                                @dragend="clearDrag()"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="text-right">
                                        <div class="hub-builder-item-title text-sm font-semibold text-slate-900" x-text="cleanModelTitle(item.title)"></div>
                                        <div class="text-xs text-slate-600" x-text="partTitleLabel(item.part_title)"></div>
                                    </div>
                                    <button type="button" class="rounded-full border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 transition hover:bg-slate-100" @click="removeAssigned('lesen', index)">
                                        حذف
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-for="n in placeholders('lesen')" :key="'lesen-ph-' + n">
                            <div class="hub-builder-drop">
                                ضع هنا نموذج القراءة
                            </div>
                        </template>
                    </div>
                </div>

                <div class="hub-builder-zone p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <span class="hub-builder-chip hub-builder-chip-red" x-text="assigned.sprachbausteine.length + ' من ' + capacities.sprachbausteine"></span>
                        <h4 class="hub-builder-section-title font-black tracking-wide text-slate-950">اللغويات</h4>
                    </div>
                    <div
                        class="mt-3 grid min-h-[120px] grid-cols-1 gap-3 rounded-xl border border-slate-200 p-3 transition sm:min-h-[130px] sm:grid-cols-2"
                        :class="dropZoneClass('sprachbausteine')"
                        @dragover.prevent="onDragOver('sprachbausteine')"
                        @dragleave="clearHover('sprachbausteine')"
                        @drop="dropToSection('sprachbausteine')"
                    >
                        <template x-for="(item, index) in assigned.sprachbausteine" :key="'sprach-' + item.id + '-' + index">
                            <div
                                class="hub-builder-assigned hub-builder-assigned-red"
                                :class="{ 'hub-builder-assigned-dragging': dragMeta && dragMeta.id === item.id && dragMeta.fromSection === 'sprachbausteine' && dragMeta.fromIndex === index }"
                                draggable="true"
                                @dragstart="startDrag(item.id, 'sprachbausteine', index)"
                                @dragend="clearDrag()"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="text-right">
                                        <div class="hub-builder-item-title text-sm font-semibold text-slate-900" x-text="cleanModelTitle(item.title)"></div>
                                        <div class="text-xs text-slate-600" x-text="partTitleLabel(item.part_title)"></div>
                                    </div>
                                    <button type="button" class="rounded-full border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 transition hover:bg-slate-100" @click="removeAssigned('sprachbausteine', index)">
                                        حذف
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-for="n in placeholders('sprachbausteine')" :key="'sprach-ph-' + n">
                            <div class="hub-builder-drop">
                                ضع هنا نموذج اللغويات
                            </div>
                        </template>
                    </div>
                </div>

                <div class="hub-builder-zone p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <span class="hub-builder-chip hub-builder-chip-yellow" x-text="assigned.schreiben.length + ' من ' + capacities.schreiben"></span>
                        <h4 class="hub-builder-section-title font-black tracking-wide text-slate-950">الكتابة</h4>
                    </div>
                    <div
                        class="mt-3 min-h-[120px] rounded-xl border border-slate-200 p-3 transition sm:min-h-[130px]"
                        :class="dropZoneClass('schreiben')"
                        @dragover.prevent="onDragOver('schreiben')"
                        @dragleave="clearHover('schreiben')"
                        @drop="dropToSection('schreiben')"
                    >
                        <template x-for="(item, index) in assigned.schreiben" :key="'schreiben-' + item.id + '-' + index">
                            <div
                                class="hub-builder-assigned hub-builder-assigned-yellow"
                                :class="{ 'hub-builder-assigned-dragging': dragMeta && dragMeta.id === item.id && dragMeta.fromSection === 'schreiben' && dragMeta.fromIndex === index }"
                                draggable="true"
                                @dragstart="startDrag(item.id, 'schreiben', index)"
                                @dragend="clearDrag()"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="text-right">
                                        <div class="hub-builder-item-title text-sm font-semibold text-slate-900" x-text="cleanModelTitle(item.title)"></div>
                                        <div class="text-xs text-slate-600" x-text="partTitleLabel(item.part_title)"></div>
                                    </div>
                                    <button type="button" class="rounded-full border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 transition hover:bg-slate-100" @click="removeAssigned('schreiben', index)">
                                        حذف
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-for="n in placeholders('schreiben')" :key="'schreiben-ph-' + n">
                            <div class="hub-builder-drop">
                                ضع هنا نموذج الكتابة
                            </div>
                        </template>
                    </div>
                </div>

                <form method="POST" action="{{ route('training.custom') }}" class="hub-surface p-4 sm:p-5">
                    @csrf
                    <template x-for="id in submissionIds()" :key="'submit-' + id">
                        <input type="hidden" name="model_ids[]" :value="id">
                    </template>

                    <div class="mb-4 grid gap-2 text-xs text-slate-600 sm:grid-cols-3">
                        <div class="hub-builder-summary-chip">
                            <span>القراءة</span>
                            <strong x-text="assigned.lesen.length"></strong>
                        </div>
                        <div class="hub-builder-summary-chip">
                            <span>اللغويات</span>
                            <strong x-text="assigned.sprachbausteine.length"></strong>
                        </div>
                        <div class="hub-builder-summary-chip">
                            <span>الكتابة</span>
                            <strong x-text="assigned.schreiben.length"></strong>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-slate-600">
                            مجموع النماذج المختارة:
                            <span class="font-semibold text-slate-900" x-text="submissionIds().length"></span>
                        </p>
                        <button type="submit" class="hub-glow-button hub-glow-button-primary px-4 py-2 text-sm font-semibold disabled:opacity-50" :disabled="submissionIds().length === 0">
                            ابدأ الامتحان المركّب
                        </button>
                    </div>
                </form>
            </section>

            <section class="order-2 hub-builder-pool p-4 text-white sm:p-5 lg:col-span-5">
                <div class="space-y-2 text-right">
                    <p class="hub-kicker text-amber-200">قائمة النماذج</p>
                    <h2 class="text-2xl font-black tracking-tight">اختر من هنا</h2>
                    <p class="text-sm text-slate-200">
                        جرّ أي نموذج من هاد اللائحة وحطّو فالمكان المناسب.
                    </p>
                </div>

                <div class="mt-4 space-y-3">
                    <label class="sr-only" for="builder-search">بحث</label>
                    <div class="hub-builder-search-wrap">
                        <svg class="hub-builder-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="M20 20L16.5 16.5"></path>
                        </svg>
                        <input
                            id="builder-search"
                            type="text"
                            x-model.trim="search"
                            class="hub-builder-search-input"
                            placeholder="قلّب على نموذج بالاسم..."
                        >
                    </div>

                    <div class="flex items-center justify-between gap-3 text-xs text-slate-300">
                        <span x-text="'المعروض: ' + filteredAvailable().length"></span>
                        <button
                            type="button"
                            x-show="search.length > 0"
                            x-cloak
                            class="rounded-full border border-white/10 px-3 py-1 text-[11px] text-slate-200 transition hover:border-amber-300/40 hover:text-white"
                            @click="search = ''"
                        >
                            مسح البحث
                        </button>
                    </div>
                </div>

                <div class="mt-4 h-[420px] overflow-y-auto rounded-[1.5rem] border border-white/10 bg-white/5 p-3 sm:h-[540px] lg:h-[680px]">
                    <template x-if="dragMeta">
                        <div class="mb-3 rounded-2xl border border-amber-300/25 bg-amber-300/10 px-3 py-2 text-center text-xs text-amber-100">
                            سحب النموذج وإفلاته فوق الخانة المناسبة.
                        </div>
                    </template>

                    <template x-if="available.length === 0">
                        <div class="hub-builder-empty">
                            <div class="hub-builder-empty-icon">✓</div>
                            <p class="text-sm font-semibold text-white">وزّعت جميع النماذج الموجودة</p>
                            <p class="text-xs text-slate-300">حاول تبدّل الترتيب أو ابدأ الامتحان المركّب.</p>
                        </div>
                    </template>

                    <template x-if="available.length > 0 && filteredAvailable().length === 0">
                        <div class="hub-builder-empty">
                            <div class="hub-builder-empty-icon">؟</div>
                            <p class="text-sm font-semibold text-white">ما لقيناش نموذج بهاد الاسم</p>
                            <p class="text-xs text-slate-300">جرّب كلمة أخرى أو مسح البحث.</p>
                        </div>
                    </template>

                    <template x-for="model in filteredAvailable()" :key="'pool-' + model.id">
                        <div
                            class="hub-builder-pool-item mb-2 cursor-grab px-3 py-3 transition"
                            :class="{ 'hub-builder-pool-item-dragging': dragMeta && dragMeta.id === model.id && dragMeta.fromSection === null }"
                            draggable="true"
                            @dragstart="startDrag(model.id, null, null)"
                            @dragend="clearDrag()"
                        >
                            <div class="hub-builder-item-title text-sm font-semibold text-white" x-text="cleanModelTitle(model.title)"></div>
                            <div class="mt-2 flex flex-wrap items-center justify-end gap-2 text-xs text-slate-300">
                                <span class="hub-builder-meta-pill" x-text="sectionLabel(model.section_type)"></span>
                                <span class="hub-builder-meta-pill" x-text="partTitleLabel(model.part_title)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('customExamBuilder', (models, dbCapacities) => ({
                available: [...models],
                search: '',
                dragMeta: null,
                hoverSection: null,
                capacities: dbCapacities,
                assigned: {
                    lesen: [],
                    sprachbausteine: [],
                    schreiben: [],
                },
                sectionByType(type) {
                    if (type === 'lesen') return 'lesen';
                    if (type === 'sprachbausteine') return 'sprachbausteine';
                    if (type === 'schreiben') return 'schreiben';
                    return null;
                },
                sectionLabel(type) {
                    if (type === 'lesen') return 'قراءة';
                    if (type === 'sprachbausteine') return 'لغويات';
                    if (type === 'schreiben') return 'كتابة';
                    if (type === 'hoeren') return 'استماع';
                    return type;
                },
                partTitleLabel(title) {
                    const part = String(title ?? '').trim();
                    const map = {
                        'Teil 1': 'الجزء 1',
                        'Teil 2': 'الجزء 2',
                        'Teil 3': 'الجزء 3',
                    };

                    return map[part] ?? part;
                },
                cleanModelTitle(title) {
                    const raw = String(title ?? '').trim();
                    const patterns = [
                        /^Lesen\s+Teil\s+\d+\s*-\s*/i,
                        /^Sprachbausteine\s+Teil\s+\d+\s*-\s*/i,
                        /^Hören\s+Teil\s+\d+\s*-\s*/i,
                        /^Hoeren\s+Teil\s+\d+\s*-\s*/i,
                        /^Schreiben\s+Teil\s+\d+\s*-\s*/i,
                    ];

                    let cleaned = raw;

                    patterns.forEach((pattern) => {
                        cleaned = cleaned.replace(pattern, '');
                    });

                    return cleaned || raw;
                },
                startDrag(id, fromSection, fromIndex) {
                    this.dragMeta = { id, fromSection, fromIndex };
                },
                clearDrag() {
                    this.dragMeta = null;
                    this.hoverSection = null;
                },
                onDragOver(section) {
                    this.hoverSection = section;
                },
                clearHover(section) {
                    if (this.hoverSection === section) {
                        this.hoverSection = null;
                    }
                },
                findInPool(id) {
                    return this.available.find((item) => item.id === id) ?? null;
                },
                filteredAvailable() {
                    if (!this.search) return this.available;

                    const query = this.search.toLowerCase();

                    return this.available.filter((item) => {
                        const section = this.sectionLabel(item.section_type).toLowerCase();
                        const part = String(item.part_title ?? '').toLowerCase();
                        const title = String(item.title ?? '').toLowerCase();
                        const cleanedTitle = this.cleanModelTitle(item.title).toLowerCase();

                        return title.includes(query) || cleanedTitle.includes(query) || section.includes(query) || part.includes(query);
                    });
                },
                draggedItem() {
                    if (!this.dragMeta) return null;
                    if (this.dragMeta.fromSection === null) return this.findInPool(this.dragMeta.id);
                    return this.assigned[this.dragMeta.fromSection][this.dragMeta.fromIndex] ?? null;
                },
                removeFromPool(id) {
                    this.available = this.available.filter((item) => item.id !== id);
                },
                removeAssigned(section, index) {
                    const [item] = this.assigned[section].splice(index, 1);
                    if (!item) return;
                    this.available.push(item);
                    this.available.sort((a, b) => a.title.localeCompare(b.title));
                },
                canDropToSection(item, section) {
                    return this.sectionByType(item.section_type) === section;
                },
                dropZoneClass(section) {
                    if (this.hoverSection !== section || !this.dragMeta) {
                        return '';
                    }

                    const item = this.draggedItem();
                    if (!item) return '';

                    return this.canDropToSection(item, section)
                        ? 'ring-2 ring-emerald-300 bg-emerald-50/40'
                        : 'ring-2 ring-rose-300 bg-rose-50/40';
                },
                dropToSection(section) {
                    if (!this.dragMeta) return;

                    let item = null;
                    if (this.dragMeta.fromSection === null) {
                        item = this.findInPool(this.dragMeta.id);
                    } else {
                        item = this.assigned[this.dragMeta.fromSection][this.dragMeta.fromIndex] ?? null;
                    }

                    if (!item) {
                        this.clearDrag();
                        return;
                    }

                    if (!this.canDropToSection(item, section)) {
                        this.clearDrag();
                        return;
                    }

                    if (this.dragMeta.fromSection !== null) {
                        this.assigned[this.dragMeta.fromSection].splice(this.dragMeta.fromSection === section ? this.dragMeta.fromIndex : this.dragMeta.fromIndex, 1);
                    } else {
                        this.removeFromPool(item.id);
                    }

                    if (this.assigned[section].length >= this.capacities[section]) {
                        const displaced = this.assigned[section].pop();
                        if (displaced) {
                            this.available.push(displaced);
                        }
                    }

                    this.assigned[section].push(item);
                    this.available.sort((a, b) => a.title.localeCompare(b.title));
                    this.clearDrag();
                },
                placeholders(section) {
                    return Math.max(0, this.capacities[section] - this.assigned[section].length);
                },
                submissionIds() {
                    return [
                        ...this.assigned.lesen,
                        ...this.assigned.sprachbausteine,
                        ...this.assigned.schreiben,
                    ].map((item) => item.id);
                },
            }));
        });
    </script>
</x-app-layout>
