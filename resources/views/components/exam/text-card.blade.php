@props(['text'])

<article
    class="text-drop-zone rounded-lg border border-slate-300 bg-white p-3 shadow-sm"
    :class="textCardClass({{ (int) $text->id }})"
    data-text-id="{{ $text->id }}"
    @click="handleTextClick({{ $text->id }})"
>
    <header class="mb-2 flex min-h-10 items-center rounded-md border px-3 py-1.5"
            :class="assignedHeaderClass({{ (int) $text->id }})">
        <template x-if="assignments['{{ $text->id }}']">
            <button type="button"
                    class="rounded-md bg-emerald-500 px-3 py-1 text-left font-semibold text-white"
                    @click.stop="removeAssignment({{ $text->id }})"
                    x-text="optionLabel(assignments['{{ $text->id }}'])"></button>
        </template>
        <template x-if="!assignments['{{ $text->id }}']">
            <span class="text-slate-500">...{{ $text->label }}...</span>
        </template>
    </header>
    <div class="whitespace-pre-line text-base leading-relaxed text-slate-900">{{ $text->body_text }}</div>
</article>
