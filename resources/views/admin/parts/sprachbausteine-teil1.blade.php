<x-admin.layouts.app>
    @php
        $defaultBody = "Liebe Daniela,\n\nich habe schon ein ganz schlechtes Gewissen, denn [[1]] wollte ich dir schon vor zwei Monaten schreiben. Aber du weiBt ja, wie das ist: Wenn man sich auf eine Prufung vorbereitet, hat [[2]] uberhaupt keine Zeit mehr fur seine Hobbys - alles dreht sich nur noch ums Lernen.\n\nNun habe ich es aber geschafft: Gestern war die Prufung und ich bin zuversichtlich, dass ich sie bestanden habe. Mein Freund, mit [[3]] Hilfe es mir uberhaupt nur moglich war, diese ganze Zeit zu [[4]], hat mich fur heute Abend in ein tolles Restaurant eingeladen.\n\nIn deinem letzten Brief hast du mich gefragt, [[5]] ich Lust hatte, mit dir zusammen ein Wochenende in London zu verbringen. Naturlich habe ich Lust! Nach dem ganzen Stress der letzten Wochen fande ich es super, mal ein paar Tage lang mit einer Freundin etwas Tolles zu [[6]].\n\nLondon ist eine wunderbare Stadt, ich habe schon viele Berichte daruber gelesen. Ich wurde mich [[7]] besonders [[8]] die Tate Gallery und das Filmmuseum interessieren.\n\nMach [[9]] einfach ein paar Vorschlage, wann du Zeit hast. Ich bin sicher, dass wir [[10]] auf ein Wochenende einigen konnen.\n\nHerzliche GruBe";
        $defaultQuestions = collect(range(1, 10))->map(function ($n) use ($questions) {
            $existing = $questions->firstWhere('gap_number', $n);
            $existingOptions = $existing?->options?->sortBy('sort_order')->values() ?? collect();
            return [
                'gap_number' => $n,
                'options' => [
                    ['option_key' => $existingOptions[0]->option_key ?? 'A', 'option_text' => $existingOptions[0]->option_text ?? ''],
                    ['option_key' => $existingOptions[1]->option_key ?? 'B', 'option_text' => $existingOptions[1]->option_text ?? ''],
                    ['option_key' => $existingOptions[2]->option_key ?? 'C', 'option_text' => $existingOptions[2]->option_text ?? ''],
                ],
                'correct_option_key' => $existingOptions->firstWhere('is_correct', true)->option_key ?? 'A',
            ];
        })->values()->all();
        $initialQuestions = old('questions', $defaultQuestions);
    @endphp

    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold">Sprachbausteine Teil 1: {{ $part->section->exam->title }} / {{ $part->title }}</h2>
        <a class="text-sm text-slate-600 hover:underline" href="{{ route('admin.parts.edit', $part) }}">Back to part</a>
    </div>

    <form method="POST" action="{{ route('admin.sprachbausteine-teil1.update', $part) }}" class="mt-6 space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg bg-white p-6 shadow">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Part title</label>
                    <input class="w-full rounded-md border-slate-300" type="text" name="title" value="{{ old('title', $part->title) }}" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Points</label>
                    <input class="w-full rounded-md border-slate-300" type="number" min="0" name="points" value="{{ old('points', $part->points) }}" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Template tokens</label>
                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">Use placeholders `[[1]]` ... `[[10]]` inside the email text.</div>
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Instruction text</label>
                    <textarea class="w-full rounded-md border-slate-300" rows="3" name="instruction_text" required>{{ old('instruction_text', $part->instruction_text) }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email title (optional)</label>
                    <input class="w-full rounded-md border-slate-300" type="text" name="passage[title]" value="{{ old('passage.title', $passage?->title) }}">
                </div>
                <div class="md:col-span-3">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email body</label>
                    <textarea class="w-full rounded-md border-slate-300 font-mono text-sm" rows="15" name="passage[body_text]" required>{{ old('passage.body_text', $passage?->body_text ?? $defaultBody) }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Gaps (1-10) and Options</h3>
            <p class="mt-1 text-sm text-slate-600">Each gap must have exactly 3 options and exactly 1 correct option.</p>

            <div class="mt-4 space-y-4">
                @foreach ($initialQuestions as $index => $question)
                    <article class="rounded-lg border border-slate-200 p-4">
                        <div class="mb-3 flex items-center gap-3">
                            <label class="text-sm font-semibold text-slate-700">Gap number</label>
                            <input
                                class="w-20 rounded-md border-slate-300"
                                type="number"
                                min="1"
                                max="10"
                                name="questions[{{ $index }}][gap_number]"
                                value="{{ $question['gap_number'] ?? ($index + 1) }}"
                                required
                            >
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            @for ($opt = 0; $opt < 3; $opt++)
                                <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Option Key</label>
                                    <input
                                        class="w-full rounded-md border-slate-300"
                                        type="text"
                                        name="questions[{{ $index }}][options][{{ $opt }}][option_key]"
                                        value="{{ $question['options'][$opt]['option_key'] ?? chr(65 + $opt) }}"
                                        required
                                    >
                                    <label class="mb-1 mt-2 block text-sm font-medium text-slate-700">Option Text</label>
                                    <input
                                        class="w-full rounded-md border-slate-300"
                                        type="text"
                                        name="questions[{{ $index }}][options][{{ $opt }}][option_text]"
                                        value="{{ $question['options'][$opt]['option_text'] ?? '' }}"
                                        required
                                    >
                                </div>
                            @endfor
                        </div>

                        <div class="mt-3">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Correct Option Key</label>
                            <input
                                class="w-28 rounded-md border-slate-300"
                                type="text"
                                name="questions[{{ $index }}][correct_option_key]"
                                value="{{ $question['correct_option_key'] ?? 'A' }}"
                                required
                            >
                        </div>
                    </article>
                @endforeach
            </div>

            <button class="mt-6 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Save Sprachbausteine Teil 1</button>
        </section>
    </form>
</x-admin.layouts.app>

