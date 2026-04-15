@props(['option'])

<div class="option-item flex items-start gap-2.5" data-option-id="{{ $option->id }}" @click="handleOptionClick({{ $option->id }})">
    <span class="mt-1.5 text-lg font-semibold text-slate-700">{{ $option->option_key }}.</span>
    <div class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[20px] font-semibold text-slate-900 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50"
         :class="optionCardClass({{ (int) $option->id }})"
         draggable="true"
         @dragstart="dragStart({{ $option->id }}, $event)">
        {{ $option->option_text }}
    </div>
</div>
