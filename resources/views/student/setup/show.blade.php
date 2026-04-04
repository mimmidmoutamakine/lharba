<x-app-layout>
    @php
        $selectedExam = old('exam_family', $latestPendingRequest?->examFamily?->code ?? '');
        $selectedLevel = old('preferred_level', $latestPendingRequest?->level ?? '');
        $examMeta = [
            'ecl' => ['label' => 'ECL', 'image' => 'images/exams/ecl.png'],
            'osd' => ['label' => 'ÖSD', 'image' => 'images/exams/osd.png'],
            'goethe' => ['label' => 'GOETHE', 'image' => 'images/exams/goethe.png'],
            'telc' => ['label' => 'TELC', 'image' => 'images/exams/telc.png'],
        ];
    @endphp

    <div
        class="min-h-[calc(100vh-5rem)] bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.96),rgba(241,245,249,0.96))]"
        dir="rtl"
        x-data="{
            exam: '{{ $selectedExam }}',
            level: '{{ $selectedLevel }}',
            examLabels: {
                telc: 'TELC',
                goethe: 'Goethe',
                osd: 'ÖSD',
                ecl: 'ECL'
            },
            get canSubmit() {
                return this.exam !== '' && this.level !== '';
            }
        }"
    >
        <div class="mx-auto max-w-5xl px-4 py-5 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="rounded-[2rem] border border-slate-200/80 bg-white/90 p-5 shadow-[0_20px_60px_rgba(15,23,42,0.06)] backdrop-blur sm:p-6 lg:p-8">
                <div class="text-right">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        {{ $latestPendingRequest ? 'تعديل الطلب' : 'تهيئة البداية' }}
                    </div>

                    <h1 class="mt-4 text-3xl font-black leading-tight text-slate-950 sm:text-4xl">
                        {{ $latestPendingRequest ? 'بدّل الاختيار ديالك' : 'اختَر المستوى والامتحان' }}
                    </h1>

                    <p class="mt-3 text-base leading-8 text-slate-500">
                        حدّد غير المستوى والامتحان، ومن بعد غادي يتصيفط الطلب ديالك للإدارة للمراجعة.
                    </p>
                </div>

                <form method="POST" action="{{ route('setup.store') }}" class="mt-6 space-y-6">
                    @csrf

                    <section class="space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm font-black text-slate-800">اختر المستوى:</p>
                            <div class="h-px flex-1 bg-slate-200"></div>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:gap-3">
                            @foreach ($levelOptions as $value => $label)
                                <label class="cursor-pointer">
                                    <input
                                        type="radio"
                                        name="preferred_level"
                                        value="{{ $value }}"
                                        class="peer sr-only"
                                        x-model="level"
                                        {{ $selectedLevel === $value ? 'checked' : '' }}
                                    >
                                    <span class="inline-flex min-w-[64px] items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-xl font-black text-slate-950 transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white peer-checked:border-amber-300 peer-checked:bg-amber-50 peer-checked:text-amber-700 peer-checked:shadow-[0_10px_24px_rgba(245,158,11,0.12)] sm:min-w-[72px] sm:px-5 sm:py-3 sm:text-2xl">
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('preferred_level')
                            <p class="text-sm font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </section>

                    <section class="space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm font-black text-slate-800">اختر الامتحان:</p>
                            <div class="h-px flex-1 bg-slate-200"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            @foreach ($examFamilyOptions as $value => $label)
                                <label class="cursor-pointer">
                                    <input
                                        type="radio"
                                        name="exam_family"
                                        value="{{ $value }}"
                                        class="peer sr-only"
                                        x-model="exam"
                                        {{ $selectedExam === $value ? 'checked' : '' }}
                                    >

                                    <div class="rounded-[1.45rem] border border-slate-200 bg-slate-50 p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white peer-checked:border-rose-300 peer-checked:bg-rose-50 peer-checked:shadow-[0_14px_32px_rgba(239,68,68,0.10)]">
                                        <div class="flex aspect-[1.35/1] items-center justify-center overflow-hidden rounded-[1.2rem] bg-white ring-1 ring-slate-200">
                                            <img
                                                src="{{ asset($examMeta[$value]['image']) }}"
                                                alt="{{ $examMeta[$value]['label'] }}"
                                                class="max-h-[64px] w-auto object-contain transition-transform duration-200"
                                            >
                                        </div>

                                        <div class="mt-3 text-center">
                                            <span class="text-sm font-black text-slate-900 sm:text-base">
                                                {{ $examMeta[$value]['label'] }}
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        @error('exam_family')
                            <p class="text-sm font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </section>

                    <section class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-4 sm:p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="text-right">
                                <p class="text-xs font-black text-slate-400">اختياراتك</p>
                                <div class="mt-2 flex flex-wrap justify-end gap-2">
                                    <span
                                        class="inline-flex items-center rounded-full border px-3 py-2 text-xs font-black sm:text-sm"
                                        :class="level ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-white text-slate-400'"
                                        x-text="level ? ('المستوى: ' + level) : 'اختَر المستوى'"
                                    ></span>

                                    <span
                                        class="inline-flex items-center rounded-full border px-3 py-2 text-xs font-black sm:text-sm"
                                        :class="exam ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 bg-white text-slate-400'"
                                        x-text="exam ? ('الامتحان: ' + examLabels[exam]) : 'اختَر الامتحان'"
                                    ></span>
                                </div>
                            </div>

                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                                <a
                                    href="{{ route('approval.pending') }}"
                                    class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:border-slate-400"
                                >
                                    رجوع
                                </a>

                                <button
                                    type="submit"
                                    class="inline-flex min-w-[210px] items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-black text-white transition-all duration-200 sm:text-base"
                                    :class="canSubmit
                                        ? 'bg-[linear-gradient(135deg,#2c0d0d_0%,#d91f26_55%,#f59e0b_100%)] shadow-[0_16px_35px_rgba(217,31,38,0.22)] hover:-translate-y-0.5 hover:shadow-[0_20px_40px_rgba(217,31,38,0.28)]'
                                        : 'cursor-not-allowed bg-slate-300 shadow-none'"
                                    :disabled="!canSubmit"
                                >
                                    حفظ التعديل
                                </button>
                            </div>
                        </div>
                    </section>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>