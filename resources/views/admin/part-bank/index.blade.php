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

    <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <h3 class="text-sm font-semibold text-slate-900">Import Lesen Teil 1 Bank (.csv)</h3>
        <p class="mt-1 text-xs text-slate-600">
            CSV structure by position: Titel | A..J | Text1..Text5 | Antwort1..Antwort5.
            This supports your current spreadsheet format directly.
        </p>
        <div class="mt-2 flex items-center gap-3">
            <a href="{{ route('admin.part-bank.template-lesen-teil1') }}" class="rounded-md bg-slate-200 px-3 py-2 text-xs font-semibold text-slate-900">Download Template</a>
        </div>
        <form method="POST" action="{{ route('admin.part-bank.import-lesen-teil1') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2" required>
            <input type="text" name="level" value="b2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Level (b2)">
            <input type="number" name="points" value="25" min="0" max="200" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Points">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Import to Bank</button>
            <textarea name="instruction_text" rows="2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-5" placeholder="Instruction text override (optional)"></textarea>
        </form>
        @error('csv_file')
            <div class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <h3 class="text-sm font-semibold text-slate-900">Import Lesen Teil 2 Bank (.csv)</h3>
        <p class="mt-1 text-xs text-slate-600">
            CSV by position: Titel buch, Titel_Text, text, F1/A1/B1/C1 ... F5/A5/B5/C5, frage1..frage5.
        </p>
        <div class="mt-2 flex items-center gap-3">
            <a href="{{ route('admin.part-bank.template-lesen-teil2') }}" class="rounded-md bg-slate-200 px-3 py-2 text-xs font-semibold text-slate-900">Download Template</a>
        </div>
        <form method="POST" action="{{ route('admin.part-bank.import-lesen-teil2') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2" required>
            <input type="text" name="level" value="b2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Level (b2)">
            <input type="number" name="points" value="25" min="0" max="200" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Points">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Import Teil 2 to Bank</button>
            <textarea name="instruction_text" rows="2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-5" placeholder="Instruction text override (optional)"></textarea>
        </form>
    </div>

    <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <h3 class="text-sm font-semibold text-slate-900">Import Lesen Teil 3 Bank (.csv)</h3>
        <p class="mt-1 text-xs text-slate-600">
            CSV by position: Title, situations 1..10, Anzeigen A..L (title+text), answers 1..10 (A-L or X).
        </p>
        <div class="mt-2 flex items-center gap-3">
            <a href="{{ route('admin.part-bank.template-lesen-teil3') }}" class="rounded-md bg-slate-200 px-3 py-2 text-xs font-semibold text-slate-900">Download Template</a>
        </div>
        <form method="POST" action="{{ route('admin.part-bank.import-lesen-teil3') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2" required>
            <input type="text" name="level" value="b2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Level (b2)">
            <input type="number" name="points" value="25" min="0" max="200" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Points">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Import Teil 3 to Bank</button>
            <textarea name="instruction_text" rows="2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-5" placeholder="Instruction text override (optional)"></textarea>
        </form>
    </div>

    <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <h3 class="text-sm font-semibold text-slate-900">Import Sprachbausteine Teil 1 Bank (.csv)</h3>
        <p class="mt-1 text-xs text-slate-600">
            CSV headers: source_label, passage_title, passage_text, gap_number, A, B, C, correct.
            Keep 10 rows per source_label (gap_number 1..10).
        </p>
        <div class="mt-2 flex items-center gap-3">
            <a href="{{ route('admin.part-bank.template-sprachbausteine-teil1') }}" class="rounded-md bg-slate-200 px-3 py-2 text-xs font-semibold text-slate-900">Download Template</a>
        </div>
        <form method="POST" action="{{ route('admin.part-bank.import-sprachbausteine-teil1') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2" required>
            <input type="text" name="level" value="b2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Level (b2)">
            <input type="number" name="points" value="15" min="0" max="200" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Points">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Import SBS Teil 1 to Bank</button>
            <textarea name="instruction_text" rows="2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-5" placeholder="Instruction text override (optional)"></textarea>
        </form>
    </div>

    <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <h3 class="text-sm font-semibold text-slate-900">Import Sprachbausteine Teil 2 Bank (.csv)</h3>
        <p class="mt-1 text-xs text-slate-600">
            CSV headers: source_label, passage_title, passage_text, gap_label, option_A..option_O, correct_option_key.
            Keep 10 rows per source_label (gap_label 1..10).
        </p>
        <div class="mt-2 flex items-center gap-3">
            <a href="{{ route('admin.part-bank.template-sprachbausteine-teil2') }}" class="rounded-md bg-slate-200 px-3 py-2 text-xs font-semibold text-slate-900">Download Template</a>
        </div>
        <form method="POST" action="{{ route('admin.part-bank.import-sprachbausteine-teil2') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2" required>
            <input type="text" name="level" value="b2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Level (b2)">
            <input type="number" name="points" value="15" min="0" max="200" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Points">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Import SBS Teil 2 to Bank</button>
            <textarea name="instruction_text" rows="2" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-5" placeholder="Instruction text override (optional)"></textarea>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">Title</th>
                    <th class="px-4 py-3 text-left">Level</th>
                    <th class="px-4 py-3 text-left">Section</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Points</th>
                    <th class="px-4 py-3 text-left">Created</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item->title }}</td>
                        <td class="px-4 py-3">{{ strtoupper((string) $item->level) }}</td>
                        <td class="px-4 py-3">{{ $item->section_type }}</td>
                        <td class="px-4 py-3">{{ $item->part_type }}</td>
                        <td class="px-4 py-3">{{ $item->points }}</td>
                        <td class="px-4 py-3">{{ $item->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            @if ($item->part_type === \App\Models\ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS)
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.part-bank.print', $item) }}" target="_blank" class="rounded-md bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-800">Print</a>
                                    <a href="{{ route('admin.part-bank.print', ['item' => $item, 'answers' => 1]) }}" target="_blank" class="rounded-md bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800">الأجوبة</a>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">Print soon</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-slate-500" colspan="7">No Teil bank items yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $items->links() }}</div>
</x-admin.layouts.app>
