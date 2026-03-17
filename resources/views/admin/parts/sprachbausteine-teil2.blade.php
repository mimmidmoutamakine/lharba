<x-admin.layouts.app>
    @if ($errors->any())
        <div class="mb-4 rounded-md border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">Please fix the following errors:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold">Sprachbausteine Teil 2 Editor</h2>
        <a class="text-sm text-slate-600 hover:underline" href="{{ route('admin.parts.edit', $part) }}">Back to part</a>
    </div>

    @php
        $passage = $part->sprachPoolPassages->sortBy('sort_order')->first();
        $gaps = $part->sprachPoolGaps->sortBy('sort_order')->values();
        $options = $part->sprachPoolOptions->sortBy('sort_order')->values();
    @endphp

    <form method="POST" action="{{ route('admin.sprachbausteine-teil2.update', $part) }}" class="mt-6 space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Part Meta</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Part title</label>
                    <input class="w-full rounded-md border-slate-300" type="text" name="title" value="{{ old('title', $part->title) }}" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Points</label>
                    <input class="w-full rounded-md border-slate-300" type="number" min="0" name="points" value="{{ old('points', $part->points) }}" required>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Instruction text</label>
                    <textarea class="w-full rounded-md border-slate-300" rows="3" name="instruction_text" required>{{ old('instruction_text', $part->instruction_text) }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Text Template</h3>
            <p class="text-sm text-slate-600">Use placeholders `[[1]]` ... `[[10]]` for gaps.</p>
            <div class="mt-4 grid gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Title (optional)</label>
                    <input class="w-full rounded-md border-slate-300" type="text" name="passage[title]" value="{{ old('passage.title', $passage?->title) }}">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Body text</label>
                    <textarea class="w-full rounded-md border-slate-300 font-mono text-sm" rows="18" name="passage[body_text]" required>{{ old('passage.body_text', $passage?->body_text) }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Gaps (10)</h3>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @for ($i = 0; $i < 10; $i++)
                    @php
                        $gap = $gaps[$i] ?? null;
                        $label = old("gaps.$i.label", $gap?->label ?? (string) ($i + 1));
                        $sortOrder = old("gaps.$i.sort_order", $gap?->sort_order ?? ($i + 1));
                    @endphp
                    <div class="rounded-md border border-slate-200 p-4">
                        <input type="hidden" name="gaps[{{ $i }}][id]" value="{{ $gap?->id }}">
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Gap label</label>
                                <input class="w-full rounded-md border-slate-300" type="text" name="gaps[{{ $i }}][label]" value="{{ $label }}" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Sort order</label>
                                <input class="w-full rounded-md border-slate-300" type="number" min="1" name="gaps[{{ $i }}][sort_order]" value="{{ $sortOrder }}" required>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Options Pool (15 / A-O)</h3>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @for ($i = 0; $i < 15; $i++)
                    @php
                        $option = $options[$i] ?? null;
                        $optionKey = old("options.$i.option_key", $option?->option_key ?? chr(65 + $i));
                        $sortOrder = old("options.$i.sort_order", $option?->sort_order ?? ($i + 1));
                    @endphp
                    <div class="rounded-md border border-slate-200 p-4">
                        <input type="hidden" name="options[{{ $i }}][id]" value="{{ $option?->id }}">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Option key</label>
                        <input class="w-full rounded-md border-slate-300" type="text" name="options[{{ $i }}][option_key]" value="{{ $optionKey }}" required>
                        <label class="mb-1 mt-2 block text-xs font-medium text-slate-600">Option text</label>
                        <input class="w-full rounded-md border-slate-300" type="text" name="options[{{ $i }}][option_text]" value="{{ old("options.$i.option_text", $option?->option_text) }}" required>
                        <label class="mb-1 mt-2 block text-xs font-medium text-slate-600">Sort order</label>
                        <input class="w-full rounded-md border-slate-300" type="number" min="1" name="options[{{ $i }}][sort_order]" value="{{ $sortOrder }}" required>
                    </div>
                @endfor
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Correct answers</h3>
            <p class="text-sm text-slate-600">Assign one unique option key per gap.</p>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach ($gaps->count() ? $gaps : collect(range(1, 10))->map(fn ($i) => (object) ['label' => (string) $i, 'id' => null]) as $i => $gap)
                    @php
                        $gapLabel = old("gaps.$i.label", $gap->label);
                        $selected = old("correct_answers.$gapLabel", $correctByGap[$gap->id] ?? null);
                    @endphp
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Gap {{ $gapLabel }}</label>
                        <select class="w-full rounded-md border-slate-300" name="correct_answers[{{ $gapLabel }}]" required>
                            <option value="">Select option</option>
                            @for ($o = 0; $o < 15; $o++)
                                @php
                                    $option = $options[$o] ?? null;
                                    $key = old("options.$o.option_key", $option?->option_key ?? chr(65 + $o));
                                    $id = $option?->id;
                                @endphp
                                <option value="{{ $key }}" @selected($selected == $key || ($selected == $id && $id))>{{ $key }}</option>
                            @endfor
                        </select>
                    </div>
                @endforeach
            </div>
        </section>

        <button class="rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white" type="submit">Save Sprachbausteine Teil 2</button>
    </form>
</x-admin.layouts.app>

