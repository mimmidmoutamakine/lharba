@csrf
@if ($method === 'PUT')
    @method('PUT')
@endif

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Title</label>
        <input class="w-full rounded-md border-slate-300" type="text" name="title" value="{{ old('title', $exam->title ?? '') }}" required>
        @error('title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Level</label>
        <input class="w-full rounded-md border-slate-300" type="text" name="level" value="{{ old('level', $exam->level ?? 'B2') }}" required>
        @error('level')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Duration (minutes)</label>
        <input class="w-full rounded-md border-slate-300" type="number" min="1" name="total_duration_minutes" value="{{ old('total_duration_minutes', $exam->total_duration_minutes ?? 90) }}" required>
        @error('total_duration_minutes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="flex items-end gap-2">
        <input id="is_published" type="checkbox" name="is_published" value="1" {{ old('is_published', $exam->is_published ?? false) ? 'checked' : '' }}>
        <label class="text-sm font-medium text-slate-700" for="is_published">Published</label>
    </div>
</div>

<div class="mt-5">
    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">{{ $button }}</button>
</div>
