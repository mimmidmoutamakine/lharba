@props([
    'partTabs' => collect(),
    'currentPartId' => null,
    'completedPartIds' => [],
    'attempt',
    'routeName' => 'attempts.parts.show',
])

<nav class="hidden gap-2 overflow-x-auto lg:flex">
    @foreach ($partTabs as $tabPart)
        @php
            $isCurrent = (int) $currentPartId === (int) $tabPart->id;
            $isComplete = in_array($tabPart->id, $completedPartIds, true);
            $requiredCount = $tabPart->part_type === \App\Models\ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS
                ? (int) ($tabPart->lesen_matching_texts_count ?? 0)
                : ($tabPart->part_type === \App\Models\ExamPart::TYPE_READING_TEXT_MCQ
                    ? (int) ($tabPart->lesen_mcq_questions_count ?? 0)
                    : ($tabPart->part_type === \App\Models\ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X
                        ? (int) ($tabPart->lesen_situations_count ?? 0)
                        : ($tabPart->part_type === \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ
                            ? (int) ($tabPart->sprach_gap_questions_count ?? 0)
                            : ($tabPart->part_type === \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH
                                ? (int) ($tabPart->sprach_pool_gaps_count ?? 0)
                                : ($tabPart->part_type === \App\Models\ExamPart::TYPE_HOEREN_TRUE_FALSE
                                    ? (int) ($tabPart->hoeren_true_false_questions_count ?? 0)
                                    : 1)))));
        @endphp
        <a href="{{ route($routeName, [$attempt, $tabPart]) }}"
           data-part-tab-link="1"
           class="relative min-w-[145px] rounded-md border px-3 py-1.5 text-center text-xs font-semibold {{ $isCurrent ? 'border-white bg-white text-slate-900' : 'border-slate-500 bg-slate-400/60 text-slate-900 hover:bg-slate-300' }}">
            <div>{{ $tabPart->section->title }}</div>
            <div>{{ $tabPart->title }}</div>
            <div class="text-xs font-medium">({{ $tabPart->points }} Punkte)</div>
            <span class="absolute -right-1 -bottom-1 {{ $isComplete ? '' : 'hidden' }}"
                  data-part-complete="{{ $tabPart->id }}"
                  data-required-count="{{ $requiredCount }}"
                  data-initial-complete="{{ $isComplete ? '1' : '0' }}">
                <x-exam.completion-icon />
            </span>
        </a>
    @endforeach
</nav>
