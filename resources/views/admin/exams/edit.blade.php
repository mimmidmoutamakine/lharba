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
        <h2 class="text-2xl font-bold">Edit Exam: {{ $exam->title }}</h2>
        <a class="text-sm text-slate-600 hover:underline" href="{{ route('admin.exams.index') }}">Back to exams</a>
    </div>

    <form method="POST" action="{{ route('admin.exams.update', $exam) }}" class="mt-6 rounded-lg bg-white p-6 shadow">
        @include('admin.exams._form', [
            'method' => 'PUT',
            'button' => 'Save Exam',
        ])
    </form>

    <section class="mt-6 rounded-lg bg-white p-6 shadow">
        <h3 class="text-lg font-semibold">Add Section</h3>
        <form class="mt-4 grid gap-3 md:grid-cols-4" method="POST" action="{{ route('admin.sections.store', $exam) }}">
            @csrf
            <select class="rounded-md border-slate-300" name="type" required>
                @foreach (\App\Models\ExamSection::types() as $key => $label)
                    <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <input class="rounded-md border-slate-300" type="text" name="title" placeholder="Section title" value="{{ old('title') }}" required>
            <input class="rounded-md border-slate-300" type="number" min="1" name="sort_order" placeholder="Order" value="{{ old('sort_order', 1) }}" required>
            <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Add Section</button>
        </form>
    </section>

    <section class="mt-6 space-y-4">
        @foreach ($exam->sections->sortBy('sort_order') as $section)
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h4 class="text-lg font-semibold">{{ $section->title }} ({{ ucfirst($section->type) }})</h4>
                    <form class="flex gap-2" method="POST" action="{{ route('admin.sections.destroy', [$exam, $section]) }}" onsubmit="return confirm('Delete section and all its parts?')">
                        @csrf
                        @method('DELETE')
                        <button class="text-sm text-rose-600 hover:underline" type="submit">Delete section</button>
                    </form>
                </div>

                <form class="mt-3 grid gap-3 md:grid-cols-4" method="POST" action="{{ route('admin.sections.update', [$exam, $section]) }}">
                    @csrf
                    @method('PUT')
                    <select class="rounded-md border-slate-300" name="type" required>
                        @foreach (\App\Models\ExamSection::types() as $key => $label)
                            <option value="{{ $key }}" @selected($section->type === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input class="rounded-md border-slate-300" type="text" name="title" value="{{ $section->title }}" required>
                    <input class="rounded-md border-slate-300" type="number" min="1" name="sort_order" value="{{ $section->sort_order }}" required>
                    <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold" type="submit">Update section</button>
                </form>

                <div class="mt-6">
                    <h5 class="font-semibold">Add Part</h5>
                    <form class="mt-3 grid gap-3 md:grid-cols-6" method="POST" action="{{ route('admin.parts.store', $section) }}">
                        @csrf
                        <input class="rounded-md border-slate-300" type="text" name="title" placeholder="Teil 1 (required only without bank)">
                        <select class="rounded-md border-slate-300 md:col-span-2" name="part_type" required>
                            @foreach (\App\Models\ExamPart::types() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input class="rounded-md border-slate-300" type="number" min="0" name="points" placeholder="Points (optional with bank)">
                        <input class="rounded-md border-slate-300" type="number" min="1" name="sort_order" placeholder="Order" required>
                        <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Add part</button>
                        <select class="rounded-md border-slate-300 md:col-span-4" name="part_bank_item_id">
                            <option value="">Use manual part data (no bank item)</option>
                            @foreach ($bankItems->where('section_type', $section->type) as $bankItem)
                                <option value="{{ $bankItem->id }}">
                                    {{ $bankItem->title }} [{{ strtoupper((string) $bankItem->level) }} | {{ $bankItem->part_type }}]
                                </option>
                            @endforeach
                        </select>
                        <label class="inline-flex items-center gap-2 text-sm md:col-span-2">
                            <input type="checkbox" name="random_from_bank" value="1" class="rounded border-slate-300">
                            Random from bank (by selected part type)
                        </label>
                        <textarea class="rounded-md border-slate-300 md:col-span-6" name="instruction_text" rows="2" placeholder="Instruction text"></textarea>
                    </form>
                </div>

                <div class="mt-6 overflow-hidden rounded border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Part</th>
                            <th class="px-3 py-2 text-left">Type</th>
                            <th class="px-3 py-2 text-left">Points</th>
                            <th class="px-3 py-2 text-left">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($section->parts->sortBy('sort_order') as $part)
                            <tr>
                                <td class="px-3 py-2">{{ $part->title }}</td>
                                <td class="px-3 py-2">{{ $part->part_type }}</td>
                                <td class="px-3 py-2">{{ $part->points }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex gap-3">
                                        <a class="text-blue-600 hover:underline" href="{{ route('admin.parts.edit', $part) }}">Edit</a>
                                        @if ($part->part_type === \App\Models\ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS)
                                            <a class="text-emerald-600 hover:underline" href="{{ route('admin.lesen-teil1.edit', $part) }}">Lesen Teil 1 Data</a>
                                        @elseif ($part->part_type === \App\Models\ExamPart::TYPE_READING_TEXT_MCQ)
                                            <a class="text-emerald-600 hover:underline" href="{{ route('admin.lesen-teil2.edit', $part) }}">Lesen Teil 2 Data</a>
                                        @elseif ($part->part_type === \App\Models\ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X)
                                            <a class="text-emerald-600 hover:underline" href="{{ route('admin.lesen-teil3.edit', $part) }}">Lesen Teil 3 Data</a>
                                        @elseif ($part->part_type === \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ)
                                            <a class="text-emerald-600 hover:underline" href="{{ route('admin.sprachbausteine-teil1.edit', $part) }}">Sprachbausteine Teil 1 Data</a>
                                        @elseif ($part->part_type === \App\Models\ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH)
                                            <a class="text-emerald-600 hover:underline" href="{{ route('admin.sprachbausteine-teil2.edit', $part) }}">Sprachbausteine Teil 2 Data</a>
                                        @elseif ($part->part_type === \App\Models\ExamPart::TYPE_HOEREN_TRUE_FALSE)
                                            <a class="text-emerald-600 hover:underline" href="{{ route('admin.hoeren-teil1.edit', $part) }}">Horen Teil 1 Data</a>
                                        @endif
                                        <form method="POST" action="{{ route('admin.parts.destroy', $part) }}" onsubmit="return confirm('Delete this part?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-600 hover:underline" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-3 py-3 text-slate-500" colspan="4">No parts yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </section>
</x-admin.layouts.app>
