<x-admin.layouts.app>
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold">Teil Bank</h2>
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('admin.part-bank.cleanup-duplicates') }}" onsubmit="return confirm('Remove duplicate Teil bank rows and keep one per source?')">
                @csrf
                <button type="submit" class="rounded-md bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-800">Cleanup Duplicates</button>
            </form>
            <a href="{{ route('admin.exams.index') }}" class="text-sm text-slate-600 hover:underline">Back to exams</a>
        </div>
    </div>

    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
        JSON import mode is active. Use the exported `.json` files for Lesen Teil 1, Teil 2, and Teil 3.
    </div>

    <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <h3 class="text-sm font-semibold text-slate-900">Import Lesen Teil 1 Bank (.json)</h3>
        <p class="mt-1 text-xs text-slate-600">
            Import the new structured JSON file for Lesen Teil 1.
        </p>

        <form method="POST" action="{{ route('admin.part-bank.import-lesen-teil1') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input type="file" name="csv_file" accept=".json,application/json,text/plain" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2" required>
            <input type="text" name="level" value="b2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Level override (optional)">
            <input type="number" name="points" value="25" min="0" max="200" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Points override">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Import JSON</button>
            <textarea name="instruction_text" rows="2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-5" placeholder="Instruction text override (optional)"></textarea>
        </form>

        @error('csv_file')
            <div class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <h3 class="text-sm font-semibold text-slate-900">Import Lesen Teil 2 Bank (.json)</h3>
        <p class="mt-1 text-xs text-slate-600">
            Import the new structured JSON file for Lesen Teil 2.
        </p>

        <form method="POST" action="{{ route('admin.part-bank.import-lesen-teil2') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input type="file" name="csv_file" accept=".json,application/json,text/plain" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2" required>
            <input type="text" name="level" value="b2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Level override (optional)">
            <input type="number" name="points" value="25" min="0" max="200" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Points override">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Import JSON</button>
            <textarea name="instruction_text" rows="2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-5" placeholder="Instruction text override (optional)"></textarea>
        </form>
    </div>

    <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <h3 class="text-sm font-semibold text-slate-900">Import Lesen Teil 3 Bank (.json)</h3>
        <p class="mt-1 text-xs text-slate-600">
            Import the new structured JSON file for Lesen Teil 3.
        </p>

        <form method="POST" action="{{ route('admin.part-bank.import-lesen-teil3') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input type="file" name="csv_file" accept=".json,application/json,text/plain" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2" required>
            <input type="text" name="level" value="b2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Level override (optional)">
            <input type="number" name="points" value="25" min="0" max="200" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Points override">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Import JSON</button>
            <textarea name="instruction_text" rows="2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-5" placeholder="Instruction text override (optional)"></textarea>
        </form>
    </div>

    <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <h3 class="text-sm font-semibold text-slate-900">Import Sprachbausteine Teil 1 Bank (.json)</h3>
        <p class="mt-1 text-xs text-slate-600">
            Import the new structured JSON file for Sprachbausteine Teil 1.
        </p>

        <form method="POST" action="{{ route('admin.part-bank.import-sprachbausteine-teil1') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input type="file" name="csv_file" accept=".json,application/json,text/plain" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2" required>
            <input type="text" name="level" value="b2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Level override (optional)">
            <input type="number" name="points" value="15" min="0" max="200" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Points override">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Import JSON</button>
            <textarea name="instruction_text" rows="2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-5" placeholder="Instruction text override (optional)"></textarea>
        </form>
    </div>
    
    <div class="mt-6 rounded-lg bg-white p-4 shadow">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Imported Models</h3>
            <span class="text-xs text-slate-500">{{ $items->total() }} total</span>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Title</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Source</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Level</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Section</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Part</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Points</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $item->title }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->source_label }}</td>
                            <td class="px-3 py-2 text-slate-600 uppercase">{{ $item->level }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->section_type }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->part_title }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->points }}</td>
                            <td class="px-3 py-2">
                                @if($item->is_active)
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-sm text-slate-500">No imported models yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $items->links() }}
        </div>
    </div>
</x-admin.layouts.app>