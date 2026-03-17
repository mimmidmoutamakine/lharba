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
        <h2 class="text-2xl font-bold">Lesen Teil 3 Editor</h2>
        <a class="text-sm text-slate-600 hover:underline" href="{{ route('admin.parts.edit', $part) }}">Back to part</a>
    </div>

    @php
        $adSlots = 12;
        $situationSlots = 10;
    @endphp

    <form method="POST" action="{{ route('admin.lesen-teil3.update', $part) }}" class="mt-6 space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Part Meta</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Part title</label>
                    <input class="w-full rounded-md border-slate-300" type="text" name="title" value="{{ old('title', $part->title) }}" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Points</label>
                    <input class="w-full rounded-md border-slate-300" type="number" name="points" min="0" value="{{ old('points', $part->points) }}" required>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Instruction text</label>
                    <textarea class="w-full rounded-md border-slate-300" name="instruction_text" rows="3" required>{{ old('instruction_text', $part->instruction_text) }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Anzeigen (12)</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @for($i = 0; $i < $adSlots; $i++)
                    @php
                        $ad = $ads[$i] ?? null;
                        $defaultLabel = chr(65 + $i);
                    @endphp
                    <div class="rounded-md border border-slate-200 p-3">
                        <label class="mb-1 block text-xs font-medium">Label</label>
                        <input class="w-20 rounded-md border-slate-300" type="text" name="ads[{{ $i }}][label]" value="{{ old("ads.$i.label", $ad?->label ?? $defaultLabel) }}" required>
                        <label class="mb-1 mt-2 block text-xs font-medium">Title (optional)</label>
                        <input class="w-full rounded-md border-slate-300" type="text" name="ads[{{ $i }}][title]" value="{{ old("ads.$i.title", $ad?->title) }}">
                        <label class="mb-1 mt-2 block text-xs font-medium">Body text</label>
                        <textarea class="w-full rounded-md border-slate-300" rows="5" name="ads[{{ $i }}][body_text]" required>{{ old("ads.$i.body_text", $ad?->body_text) }}</textarea>
                    </div>
                @endfor
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Situationen (10) + Correct answer (Ad label or X)</h3>
            <div class="mt-4 space-y-3">
                @for($i = 0; $i < $situationSlots; $i++)
                    @php
                        $situation = $situations[$i] ?? null;
                        $label = old("situations.$i.label", $situation?->label ?? (string)($i + 1));
                        $correct = old("correct_answers.$label", $correctBySituation[$situation?->id ?? 0] ?? 'X');
                    @endphp
                    <div class="rounded-md border border-slate-200 p-3">
                        <div class="grid gap-3 md:grid-cols-[80px_1fr_120px]">
                            <div>
                                <label class="mb-1 block text-xs font-medium">Label</label>
                                <input class="w-full rounded-md border-slate-300" type="text" name="situations[{{ $i }}][label]" value="{{ $label }}" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium">Situation text</label>
                                <input class="w-full rounded-md border-slate-300" type="text" name="situations[{{ $i }}][situation_text]" value="{{ old("situations.$i.situation_text", $situation?->situation_text) }}" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium">Correct</label>
                                <input class="w-full rounded-md border-slate-300" type="text" name="correct_answers[{{ $label }}]" value="{{ $correct }}" required>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </section>

        <button class="rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white" type="submit">Save Lesen Teil 3</button>
    </form>
</x-admin.layouts.app>
