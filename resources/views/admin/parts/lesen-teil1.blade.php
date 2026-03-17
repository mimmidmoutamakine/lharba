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
        <h2 class="text-2xl font-bold">Lesen Teil 1 Editor</h2>
        <a class="text-sm text-slate-600 hover:underline" href="{{ route('admin.parts.edit', $part) }}">Back to part</a>
    </div>

    @php
        $texts = $part->lesenMatchingTexts->sortBy('sort_order')->values();
        $options = $part->lesenMatchingOptions->sortBy('sort_order')->values();
        $textSlots = max(5, $texts->count());
        $optionSlots = max(10, $options->count());
    @endphp

    <form method="POST" action="{{ route('admin.lesen-teil1.update', $part) }}" class="mt-6 space-y-6">
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
            <h3 class="text-lg font-semibold">Texts (default: 5)</h3>
            <div class="mt-4 space-y-4">
                @for ($i = 0; $i < $textSlots; $i++)
                    @php
                        $text = $texts[$i] ?? null;
                        $label = old("texts.$i.label", $text?->label ?? (string) ($i + 1));
                        $sortOrder = old("texts.$i.sort_order", $text?->sort_order ?? ($i + 1));
                    @endphp
                    <div class="rounded-md border border-slate-200 p-4">
                        <input type="hidden" name="texts[{{ $i }}][id]" value="{{ $text?->id }}">
                        <div class="grid gap-3 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Label</label>
                                <input class="w-full rounded-md border-slate-300" type="text" name="texts[{{ $i }}][label]" value="{{ $label }}" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Sort order</label>
                                <input class="w-full rounded-md border-slate-300" type="number" min="1" name="texts[{{ $i }}][sort_order]" value="{{ $sortOrder }}" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-medium text-slate-600">Body text</label>
                            <textarea class="w-full rounded-md border-slate-300" rows="6" name="texts[{{ $i }}][body_text]" required>{{ old("texts.$i.body_text", $text?->body_text) }}</textarea>
                        </div>
                    </div>
                @endfor
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Titles / Options (default: 10)</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @for ($i = 0; $i < $optionSlots; $i++)
                    @php
                        $option = $options[$i] ?? null;
                        $optionKey = old("options.$i.option_key", $option?->option_key ?? chr(65 + $i));
                        $optionSort = old("options.$i.sort_order", $option?->sort_order ?? ($i + 1));
                    @endphp
                    <div class="rounded-md border border-slate-200 p-4">
                        <input type="hidden" name="options[{{ $i }}][id]" value="{{ $option?->id }}">
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Option key</label>
                                <input class="w-full rounded-md border-slate-300" type="text" name="options[{{ $i }}][option_key]" value="{{ $optionKey }}" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Sort order</label>
                                <input class="w-full rounded-md border-slate-300" type="number" min="1" name="options[{{ $i }}][sort_order]" value="{{ $optionSort }}" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-medium text-slate-600">Option text</label>
                            <input class="w-full rounded-md border-slate-300" type="text" name="options[{{ $i }}][option_text]" value="{{ old("options.$i.option_text", $option?->option_text) }}" required>
                        </div>
                    </div>
                @endfor
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Correct answers</h3>
            <p class="text-sm text-slate-600">Assign one correct option key per text label.</p>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach ($texts->count() ? $texts : collect(range(1, $textSlots))->map(fn ($i) => (object) ['label' => (string) $i, 'id' => null]) as $i => $text)
                    @php
                        $textLabel = old("texts.$i.label", $text->label);
                        $selected = old("correct_answers.$textLabel", $correctByText[$text->id] ?? null);
                    @endphp
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Text {{ $textLabel }}</label>
                        <select class="w-full rounded-md border-slate-300" name="correct_answers[{{ $textLabel }}]" required>
                            <option value="">Select title</option>
                            @for ($o = 0; $o < $optionSlots; $o++)
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

        <button class="rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white" type="submit">Save Lesen Teil 1</button>
    </form>
</x-admin.layouts.app>
