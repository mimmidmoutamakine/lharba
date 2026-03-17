<x-admin.layouts.app>
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold">Edit Part: {{ $part->title }}</h2>
        <a class="text-sm text-slate-600 hover:underline" href="{{ route('admin.exams.edit', $part->section->exam) }}">Back to exam</a>
    </div>

    <form method="POST" action="{{ route('admin.parts.update', $part) }}" class="mt-6 rounded-lg bg-white p-6 shadow">
        @csrf
        @method('PUT')

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Title</label>
                <input class="w-full rounded-md border-slate-300" type="text" name="title" value="{{ old('title', $part->title) }}" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Part Type</label>
                <select class="w-full rounded-md border-slate-300" name="part_type" required>
                    @foreach (\App\Models\ExamPart::types() as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}" @selected(old('part_type', $part->part_type) === $typeKey)>{{ $typeLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Points</label>
                <input class="w-full rounded-md border-slate-300" type="number" min="0" name="points" value="{{ old('points', $part->points) }}" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Sort order</label>
                <input class="w-full rounded-md border-slate-300" type="number" min="1" name="sort_order" value="{{ old('sort_order', $part->sort_order) }}" required>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Instruction text</label>
                <textarea class="w-full rounded-md border-slate-300" rows="5" name="instruction_text">{{ old('instruction_text', $part->instruction_text) }}</textarea>
            </div>
        </div>

        <button class="mt-4 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Save Part</button>
    </form>

    @if ($part->part_type === \App\Models\ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS)
        <div class="mt-6 rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Lesen Teil 1 Content</h3>
            <p class="mt-1 text-sm text-slate-600">Use the dedicated editor for texts, options, and correct mappings.</p>
            <a href="{{ route('admin.lesen-teil1.edit', $part) }}" class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Open Lesen Teil 1 Editor</a>
        </div>
    @elseif ($part->part_type === \App\Models\ExamPart::TYPE_READING_TEXT_MCQ)
        <div class="mt-6 rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Lesen Teil 2 Content</h3>
            <p class="mt-1 text-sm text-slate-600">Use the dedicated editor for passage and 5 MCQ questions.</p>
            <a href="{{ route('admin.lesen-teil2.edit', $part) }}" class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Open Lesen Teil 2 Editor</a>
        </div>
    @elseif ($part->part_type === \App\Models\ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X)
        <div class="mt-6 rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Lesen Teil 3 Content</h3>
            <p class="mt-1 text-sm text-slate-600">Use the dedicated editor for 12 Anzeigen and 10 Situationen with X fallback.</p>
            <a href="{{ route('admin.lesen-teil3.edit', $part) }}" class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Open Lesen Teil 3 Editor</a>
        </div>
    @elseif ($part->part_type === \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ)
        <div class="mt-6 rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Sprachbausteine Teil 1 Content</h3>
            <p class="mt-1 text-sm text-slate-600">Use the dedicated editor for email text with 10 gaps and 3 options per gap.</p>
            <a href="{{ route('admin.sprachbausteine-teil1.edit', $part) }}" class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Open Sprachbausteine Teil 1 Editor</a>
        </div>
    @elseif ($part->part_type === \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH)
        <div class="mt-6 rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Sprachbausteine Teil 2 Content</h3>
            <p class="mt-1 text-sm text-slate-600">Use the dedicated editor for 10 gaps and a shared pool of 15 options.</p>
            <a href="{{ route('admin.sprachbausteine-teil2.edit', $part) }}" class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Open Sprachbausteine Teil 2 Editor</a>
        </div>
    @elseif ($part->part_type === \App\Models\ExamPart::TYPE_HOEREN_TRUE_FALSE)
        <div class="mt-6 rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold">Horen Teil 1 Content</h3>
            <p class="mt-1 text-sm text-slate-600">Use the dedicated editor for audio URL and true/false statements.</p>
            <a href="{{ route('admin.hoeren-teil1.edit', $part) }}" class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Open Horen Teil 1 Editor</a>
        </div>
    @endif
</x-admin.layouts.app>
