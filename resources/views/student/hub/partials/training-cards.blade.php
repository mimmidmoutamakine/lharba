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
        @endphp

    <article class="training-model-card hub-float-in">
        <div class="training-model-mobile-head">
            <div class="training-model-head">
                <h3 class="training-model-title" title="{{ $displayTitle }}">{{ $displayTitle }}</h3>
                <p class="training-model-subtitle" title="{{ $partLabel }}">{{ $partLabel }}</p>
            </div>

            <span class="training-model-status {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>

        <div class="training-model-mobile-pill-row">
            <span class="training-model-pill">{{ $partLabel }}</span>
        </div>

        <div class="training-model-mobile-metrics">
            <div class="training-model-badge {{ $attemptTone }}">
                <span class="training-model-badge-value">{{ $row['attempts'] }}</span>
                <span class="training-model-badge-label">المحاولات</span>
            </div>

            <div class="training-model-badge {{ $scoreTone }}">
                <span class="training-model-badge-value">{{ $scoreValue }}</span>
                <span class="training-model-badge-label">أفضل نتيجة</span>
            </div>
        </div>

        <div class="training-model-mobile-progress">
            <div class="training-model-stars-wrap">
                <div class="training-model-stars">
                    @for ($i = 1; $i <= 5; $i++)
                        <span class="training-model-star {{ $i <= $difficultyStars ? 'is-active' : '' }}">★</span>
                    @endfor
                </div>
                <span class="training-model-difficulty">{{ $difficultyLabelAr }}</span>
            </div>
        </div>

        <div class="training-model-card-footer">
            <div class="training-model-section-chip">
                {{ $item->section_type === 'lesen' ? 'Lesen' : ($item->section_type === 'sprachbausteine' ? 'Sprachbausteine' : ($item->section_type === 'hoeren' ? 'الاستماع' : 'Schreiben')) }}
            </div>

            <div class="hub-inline-actions">
                <form method="POST" action="{{ route('training.models.start', $item) }}" class="training-action-form">
                    @csrf
                    <button title="ابدأ" class="hub-outline-action" type="submit" aria-label="ابدأ">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <path d="M8 6.5v11l8.5-5.5L8 6.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>

                @if ($item->part_type === \App\Models\ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS)
                    <a title="طباعة" href="{{ route('training.models.print', $item) }}" target="_blank" class="hub-outline-action" aria-label="طباعة">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <path d="M7 8V5.5A1.5 1.5 0 0 1 8.5 4h7A1.5 1.5 0 0 1 17 5.5V8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <rect x="5" y="14" width="14" height="6" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                            <rect x="4" y="8" width="16" height="7" rx="2" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M8 17h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </a>
                @endif

                <a title="جدولة" href="{{ route('training.builder') }}" class="hub-outline-action" aria-label="جدولة">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 8v4l2.5 2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>

                <a title="مفضلة" href="{{ route('training.builder') }}" class="hub-outline-action" aria-label="مفضلة">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="m12 4 2.35 4.76 5.25.76-3.8 3.7.9 5.23L12 16l-4.7 2.45.9-5.23-3.8-3.7 5.25-.76L12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>
        </div>
    </article>
    @empty
        <div class="training-models-empty">
            لا توجد نتائج مطابقة.
        </div>
    @endforelse
</div>