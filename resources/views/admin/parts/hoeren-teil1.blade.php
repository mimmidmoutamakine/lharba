<x-admin.layouts.app>
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold">Horen Teil 1 Editor</h2>
        <a class="text-sm text-slate-600 hover:underline" href="{{ route('admin.parts.edit', $part) }}">Back to part</a>
    </div>

    @php
        $rows = max(5, $questions->count());
    @endphp

    <form method="POST" action="{{ route('admin.hoeren-teil1.update', $part) }}" class="mt-6 space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg bg-white p-6 shadow">
            <div class="grid gap-4 md:grid-cols-2">
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
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Audio URL</label>
                    <input class="w-full rounded-md border-slate-300" type="url" name="audio_url" value="{{ old('audio_url', $audioUrl) }}">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Audio duration (seconds)</label>
                    <input class="w-full rounded-md border-slate-300" type="number" min="1" name="audio_duration_seconds" value="{{ old('audio_duration_seconds', $audioDurationSeconds) }}">
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Statements (default 5)</h3>
            <div class="mt-4 space-y-3">
                @for ($i = 0; $i < $rows; $i++)
                    @php $q = $questions[$i] ?? null; @endphp
                    <article class="rounded-md border border-slate-200 p-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Statement {{ $i + 1 }}</label>
                        <textarea class="w-full rounded-md border-slate-300" rows="2" name="questions[{{ $i }}][statement_text]" required>{{ old("questions.$i.statement_text", $q?->statement_text) }}</textarea>
                        <div class="mt-3">
                            <label class="text-sm font-medium text-slate-700">Correct answer</label>
                            @php $isTrue = (string) old("questions.$i.is_true_correct", $q?->is_true_correct ? '1' : '0'); @endphp
                            <select class="ml-2 rounded-md border-slate-300" name="questions[{{ $i }}][is_true_correct]" required>
                                <option value="1" @selected($isTrue === '1')>Richtig</option>
                                <option value="0" @selected($isTrue === '0')>Falsch</option>
                            </select>
                        </div>
                    </article>
                @endfor
            </div>
        </section>

        <button class="rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white" type="submit">Save Horen Teil 1</button>
    </form>
</x-admin.layouts.app>

