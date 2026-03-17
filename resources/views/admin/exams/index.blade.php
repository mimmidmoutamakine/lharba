<x-admin.layouts.app>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-2xl font-bold">Exams</h2>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.part-bank.index') }}" class="rounded-md bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-900">Teil Bank</a>
            <a href="{{ route('admin.exams.import-package-template') }}" class="rounded-md bg-indigo-100 px-4 py-2 text-sm font-semibold text-indigo-900">JSON Template</a>
            <a href="{{ route('admin.exams.import-template') }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-900">CSV Template</a>
            <a href="{{ route('admin.exams.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Create Exam</a>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-4 shadow">
            <h3 class="text-sm font-semibold text-slate-900">Quick CSV Import</h3>
            <p class="mt-1 text-xs text-slate-600">Imports exam structure (exam, section, part).</p>
            <form method="POST" action="{{ route('admin.exams.import-csv') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-2">
                @csrf
                <input type="file" name="csv_file" accept=".csv,text/csv" class="block rounded border border-slate-300 bg-white px-3 py-2 text-sm">
                <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Import CSV</button>
            </form>
            @error('csv_file')
                <div class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</div>
            @enderror
        </div>

        <div class="rounded-lg bg-white p-4 shadow">
            <h3 class="text-sm font-semibold text-slate-900">Full JSON Import</h3>
            <p class="mt-1 text-xs text-slate-600">Imports exam + full part content.</p>
            <form method="POST" action="{{ route('admin.exams.import-package') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-2">
                @csrf
                <input type="file" name="package_file" accept=".json,application/json,text/plain" class="block rounded border border-slate-300 bg-white px-3 py-2 text-sm">
                <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Import JSON</button>
            </form>
            @error('package_file')
                <div class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left">Title</th>
                <th class="px-4 py-3 text-left">Level</th>
                <th class="px-4 py-3 text-left">Duration</th>
                <th class="px-4 py-3 text-left">Sections</th>
                <th class="px-4 py-3 text-left">Published</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($exams as $exam)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $exam->title }}</td>
                    <td class="px-4 py-3">{{ strtoupper($exam->level) }}</td>
                    <td class="px-4 py-3">{{ $exam->total_duration_minutes }} min</td>
                    <td class="px-4 py-3">{{ $exam->sections_count }}</td>
                    <td class="px-4 py-3">{{ $exam->is_published ? 'Yes' : 'No' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a class="text-blue-600 hover:underline" href="{{ route('admin.exams.edit', $exam) }}">Edit</a>
                            <a class="text-emerald-600 hover:underline" href="{{ route('exams.start', $exam) }}">Start</a>
                            <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}" onsubmit="return confirm('Delete this exam?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-rose-600 hover:underline" type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td class="px-4 py-6 text-slate-500" colspan="6">No exams yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $exams->links() }}</div>
</x-admin.layouts.app>
