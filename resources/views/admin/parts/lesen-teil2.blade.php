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
        <h2 class="text-2xl font-bold">Lesen Teil 2 Editor</h2>
        <a class="text-sm text-slate-600 hover:underline" href="{{ route('admin.parts.edit', $part) }}">Back to part</a>
    </div>

    <form method="POST" action="{{ route('admin.lesen-teil2.update', $part) }}" class="mt-6 space-y-6">
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
            <h3 class="text-lg font-semibold">Passage</h3>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Passage title (optional)</label>
                    <input class="w-full rounded-md border-slate-300" type="text" name="passage[title]" value="{{ old('passage.title', $passage?->title) }}">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Long text</label>
                    <textarea class="w-full rounded-md border-slate-300" rows="14" name="passage[body_text]" required>{{ old('passage.body_text', $passage?->body_text) }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Questions (5 questions, 3 options each)</h3>
            <div class="mt-4 space-y-5">
                @for ($q = 0; $q < 5; $q++)
                    @php
                        $question = $questions[$q] ?? null;
                        $qOptions = $question?->options?->sortBy('sort_order')->values() ?? collect();
                        $selectedCorrect = old("questions.$q.correct_option_key")
                            ?? optional($qOptions->firstWhere('is_correct', true))->option_key;
                    @endphp
                    <div class="rounded-md border border-slate-200 p-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Question {{ $q + 1 }}</label>
                        <textarea class="w-full rounded-md border-slate-300" rows="2" name="questions[{{ $q }}][question_text]" required>{{ old("questions.$q.question_text", $question?->question_text) }}</textarea>

                        <div class="mt-3 grid gap-3 md:grid-cols-3">
                            @for ($o = 0; $o < 3; $o++)
                                @php
                                    $option = $qOptions[$o] ?? null;
                                    $defaultKey = chr(65 + $o);
                                    $optionKey = old("questions.$q.options.$o.option_key", $option?->option_key ?? $defaultKey);
                                @endphp
                                <div class="rounded border border-slate-200 p-3">
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Option key</label>
                                    <input class="w-full rounded-md border-slate-300" type="text" name="questions[{{ $q }}][options][{{ $o }}][option_key]" value="{{ $optionKey }}" required>
                                    <label class="mb-1 mt-2 block text-xs font-medium text-slate-600">Option text</label>
                                    <input class="w-full rounded-md border-slate-300" type="text" name="questions[{{ $q }}][options][{{ $o }}][option_text]" value="{{ old("questions.$q.options.$o.option_text", $option?->option_text) }}" required>
                                </div>
                            @endfor
                        </div>

                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-medium text-slate-600">Correct option key (A/B/C)</label>
                            <input class="w-32 rounded-md border-slate-300" type="text" name="questions[{{ $q }}][correct_option_key]" value="{{ $selectedCorrect }}" required>
                        </div>
                    </div>
                @endfor
            </div>
        </section>

        <button class="rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white" type="submit">Save Lesen Teil 2</button>
    </form>
</x-admin.layouts.app>
