<div class="mt-6 training-models-card-grid">
    @forelse($rows as $row)
        @php
            $item = $row['item'];

            $sectionLabel = match ($item->section_type) {
                'lesen' => 'Lesen',
                'sprachbausteine' => 'Sprachbausteine',
                'hoeren' => 'Hören',
                'schreiben' => 'Schreiben',
                default => ucfirst((string) $item->section_type),
            };

            $partLabel = trim($sectionLabel . ' ' . ($item->part_title ?? ''));

            $displayTitle = trim((string) $item->title);
            if (str_contains($displayTitle, ' - ')) {
                $parts = explode(' - ', $displayTitle, 2);
                $displayTitle = $parts[1] ?? $displayTitle;
            }
            $displayTitle = str_replace('_', ' ', $displayTitle);
            $displayTitle = preg_replace('/\s+/', ' ', $displayTitle);
            $displayTitle = mb_convert_case($displayTitle, MB_CASE_TITLE, 'UTF-8');

            $difficultyStars = match ($row['difficulty']) {
                'Easy' => 5,
                'Medium' => 3,
                'Hard' => 2,
                default => 0,
            };

            $statusLabel = match ($row['status']) {
                'Mastered' => 'متقن',
                'Practiced' => 'تمت ممارسته',
                default => 'لم يبدأ',
            };

            $statusClass = match ($row['status']) {
                'Mastered' => 'training-model-status-good',
                'Practiced' => 'training-model-status-mid',
                default => 'training-model-status-muted',
            };

            $difficultyLabelAr = match ($row['difficulty']) {
                'Easy' => 'سهل',
                'Medium' => 'متوسط',
                'Hard' => 'صعب',
                default => 'غير معروف',
            };

            $scoreValue = is_null($row['best_score']) ? '-' : $row['best_score'] . '%';
            $scoreRaw   = $row['best_score'] ?? 0;

            $attemptTone = match (true) {
                $row['attempts'] === 0 => 'training-model-badge-muted',
                $row['attempts'] <= 3 => 'training-model-badge-bad',
                default => 'training-model-badge-good',
            };

            $scoreTone = match (true) {
                is_null($row['best_score']) => 'training-model-badge-muted',
                $row['best_score'] < 60 => 'training-model-badge-bad',
                default => 'training-model-badge-good',
            };

            $levelBadge   = strtoupper(trim((string) ($item->level ?? '')));
            $sectionClass = 'tcm-section-' . $item->section_type;

            $sectionCode = match ($item->section_type) {
                'lesen'           => 'LES',
                'sprachbausteine' => 'SPR',
                'hoeren'          => 'HÖR',
                'schreiben'       => 'SCH',
                default           => strtoupper(substr((string) $item->section_type, 0, 3)),
            };

            $attemptsText = match(true) {
                $row['attempts'] === 0 => 'لم يبدأ',
                $row['attempts'] === 1 => 'مرة ×١',
                default => '×' . $row['attempts'],
            };

            $durationMinutes = match ($item->section_type) {
                'hoeren'    => 17,
                'schreiben' => 30,
                default     => 20,
            };
        @endphp

    <article class="training-model-card hub-float-in {{ $sectionClass }}">

        {{-- ════════════════════════════════
             MOBILE CARD (hidden on desktop)
             ════════════════════════════════ --}}
        <div class="tcm-row" dir="rtl"
             x-data="{
                 respectTime: {{ $row['my_respect_time'] ? 'true' : 'false' }},
                 saving: false,
                 async toggleTime() {
                     if (this.saving) return;
                     this.respectTime = !this.respectTime;
                     this.saving = true;
                     try {
                         await fetch('{{ route('training.models.rate', $item) }}', {
                             method: 'POST',
                             headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                             body: JSON.stringify({ respect_time: this.respectTime })
                         });
                     } finally { this.saving = false; }
                 }
             }">

            {{-- Score panel: gradient slab --}}
            <div class="tcm-score {{ $sectionClass }}">
                <span class="tcm-score-code">{{ $sectionCode }}</span>
                <span class="tcm-score-num">
                    @if(is_null($row['best_score']))
                        <span class="tcm-score-dash">—</span>
                    @else
                        {{ $row['best_score'] }}<span class="tcm-score-pct">%</span>
                    @endif
                </span>
                {{-- Progress arc: thin ring around score --}}
                <svg class="tcm-arc" viewBox="0 0 36 36" aria-hidden="true">
                    <circle class="tcm-arc-bg" cx="18" cy="18" r="14" fill="none" stroke-width="2.5"/>
                    <circle class="tcm-arc-fill" cx="18" cy="18" r="14" fill="none" stroke-width="2.5"
                        stroke-dasharray="{{ round($scoreRaw * 87.96 / 100, 1) }} 87.96"
                        stroke-dashoffset="21.99"
                        stroke-linecap="round"/>
                </svg>
            </div>

            {{-- Info block --}}
            <div class="tcm-info">
                <div class="tcm-top-row">
                    <h3 class="tcm-title">{{ $displayTitle }}</h3>
                    <span class="tcm-status {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <p class="tcm-sub">{{ $item->part_title ?? $sectionLabel }}@if($levelBadge !== '') · <span class="tcm-level">{{ $levelBadge }}</span>@endif</p>

                {{-- Interactive shuriken rating --}}
                <div class="tcm-rating"
                     x-data="{
                         rating: {{ $row['my_rating'] }},
                         hover: 0,
                         saving: false,
                         async rate(val) {
                             if (this.saving) return;
                             this.rating = val;
                             this.saving = true;
                             try {
                                 await fetch('{{ route('training.models.rate', $item) }}', {
                                     method: 'POST',
                                     headers: {
                                         'Content-Type': 'application/json',
                                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                     },
                                     body: JSON.stringify({ rating: val })
                                 });
                             } finally { this.saving = false; }
                         }
                     }">
                    <span class="tcm-rating-label">صعوبة:</span>
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button"
                                class="tcm-shuriken-btn"
                                @mouseenter="hover = {{ $i }}"
                                @mouseleave="hover = 0"
                                @click="rate({{ $i }})"
                                :class="{ 'tcm-shuriken-active': (hover || rating) >= {{ $i }}, 'tcm-shuriken-saving': saving }"
                                aria-label="تقييم {{ $i }}">
                            <svg viewBox="0 0 20 20" class="tcm-shuriken-svg">
                                <path d="M10 1.5 L11.6 8.4 L18.5 10 L11.6 11.6 L10 18.5 L8.4 11.6 L1.5 10 L8.4 8.4 Z"
                                      transform="rotate(22.5 10 10)"/>
                            </svg>
                        </button>
                    @endfor
                </div>

                {{-- Bottom row: attempts + duration + time toggle + print --}}
                <div class="tcm-bottom-row">
                    <span class="tcm-attempts">{{ $attemptsText }}</span>

                    {{-- Duration chip --}}
                    <span class="tcm-duration">
                        <svg viewBox="0 0 16 16" fill="none" class="tcm-duration-icon">
                            <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/>
                            <path d="M8 5v3l1.8 1.8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $durationMinutes }}د
                    </span>

                    {{-- Time respect toggle --}}
                    <button type="button"
                            @click="toggleTime()"
                            :class="{ 'tcm-time-off': !respectTime }"
                            class="tcm-time-toggle"
                            :title="respectTime ? 'إيقاف توقيت' : 'تفعيل التوقيت'">
                        <svg viewBox="0 0 16 16" fill="none" class="tcm-time-icon">
                            <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/>
                            <path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                            <path x-show="!respectTime" d="M2.5 2.5l11 11" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" class="tcm-time-slash"/>
                        </svg>
                    </button>

                    @php
                        $printableTypes = [
                            \App\Models\ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS,
                            \App\Models\ExamPart::TYPE_READING_TEXT_MCQ,
                            \App\Models\ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X,
                            \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ,
                            \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH,
                        ];
                    @endphp
                    @if (in_array($item->part_type, $printableTypes, true))
                        <a href="{{ route('training.models.print', $item) }}" target="_blank"
                           class="tcm-print-btn" aria-label="طباعة" title="طباعة">
                            <svg viewBox="0 0 20 20" fill="none">
                                <path d="M6 7V4.5A1 1 0 0 1 7 3.5h6A1 1 0 0 1 14 4.5V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <rect x="3.5" y="11" width="13" height="5.5" rx="1" stroke="currentColor" stroke-width="1.5"/>
                                <rect x="3" y="7" width="14" height="5.5" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M7 14h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Play button (passes respect_time preference) --}}
            <form method="POST" action="{{ route('training.models.start', $item) }}" class="tcm-play-form">
                @csrf
                <input type="hidden" name="respect_time" :value="respectTime ? '1' : '0'">
                <button type="submit" class="tcm-play {{ $sectionClass }}" aria-label="ابدأ">
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M7 5.5v9l7.5-4.5L7 5.5Z" fill="currentColor"/>
                    </svg>
                </button>
            </form>
        </div>


    </article>
    @empty
        <div class="training-models-empty">
            لا توجد نتائج مطابقة.
        </div>
    @endforelse
</div>
