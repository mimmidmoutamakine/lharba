<x-app-layout>
    <div class="app-content py-10 sm:py-14" dir="rtl">
        <div class="mx-auto max-w-5xl space-y-8">
            <section class="overflow-hidden rounded-[2rem] border border-black/5 bg-white/90 shadow-[0_30px_80px_rgba(15,23,42,0.08)]">
                <div class="bg-[radial-gradient(circle_at_top_right,rgba(255,214,10,0.18),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(255,58,58,0.12),transparent_30%)] px-6 py-8 sm:px-10 sm:py-10">
                    <p class="text-sm font-bold tracking-[0.22em] text-amber-700">تهيئة البداية</p>
                    <h1 class="mt-3 text-3xl font-black text-slate-950 sm:text-5xl">خلّينا نضبط حسابك قبل البداية</h1>
                    <p class="mt-4 max-w-2xl text-base leading-8 text-slate-600 sm:text-lg">
                        جاوب على هاد الخطوات السريعة باش نرتّبو لك الواجهة، ونقترحو عليك تدريب مناسب من أول دخول.
                    </p>
                </div>
            </section>

            <form method="POST" action="{{ route('setup.store') }}" class="space-y-6">
                @csrf

                <section class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-[2rem] border border-black/5 bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                        <p class="text-xs font-bold tracking-[0.18em] text-slate-400">الخطوة 1</p>
                        <h2 class="mt-3 text-2xl font-black text-slate-950">المستوى</h2>
                        <div class="mt-5 grid grid-cols-2 gap-4">
                            @foreach ($levelOptions as $value => $label)
                                <label class="cursor-pointer rounded-3xl border border-black/10 bg-slate-50 px-4 py-5 transition hover:border-black/20 hover:bg-white">
                                    <input type="radio" name="preferred_level" value="{{ $value }}" class="peer sr-only" {{ old('preferred_level') === $value ? 'checked' : '' }}>
                                    <span class="flex items-center justify-between rounded-2xl text-lg font-bold text-slate-900 peer-checked:text-red-600">
                                        {{ $label }}
                                        <span class="h-4 w-4 rounded-full border border-black/20 peer-checked:border-red-600 peer-checked:bg-red-600"></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('preferred_level')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-[2rem] border border-black/5 bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                        <p class="text-xs font-bold tracking-[0.18em] text-slate-400">الخطوة 2</p>
                        <h2 class="mt-3 text-2xl font-black text-slate-950">شنو الهدف ديالك؟</h2>
                        <div class="mt-5 space-y-3">
                            @foreach ($goalOptions as $value => $label)
                                <label class="flex cursor-pointer items-center gap-4 rounded-3xl border border-black/10 bg-slate-50 px-4 py-4 transition hover:border-black/20 hover:bg-white">
                                    <input type="radio" name="study_goal" value="{{ $value }}" class="h-5 w-5 border-black/20 text-red-600 focus:ring-red-500" {{ old('study_goal') === $value ? 'checked' : '' }}>
                                    <span class="text-base font-semibold text-slate-800">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('study_goal')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="grid gap-6 lg:grid-cols-[0.9fr,1.1fr]">
                    <div class="rounded-[2rem] border border-black/5 bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                        <p class="text-xs font-bold tracking-[0.18em] text-slate-400">الخطوة 3</p>
                        <h2 class="mt-3 text-2xl font-black text-slate-950">الوقت اليومي</h2>
                        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($dailyMinuteOptions as $minutes)
                                <label class="cursor-pointer rounded-3xl border border-black/10 bg-slate-50 px-4 py-5 text-center transition hover:border-black/20 hover:bg-white">
                                    <input type="radio" name="daily_minutes" value="{{ $minutes }}" class="peer sr-only" {{ (int) old('daily_minutes') === $minutes ? 'checked' : '' }}>
                                    <span class="block text-xl font-black text-slate-900 peer-checked:text-red-600">{{ $minutes }}</span>
                                    <span class="mt-1 block text-sm text-slate-500">دقيقة</span>
                                </label>
                            @endforeach
                        </div>
                        @error('daily_minutes')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-[2rem] border border-black/5 bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                        <p class="text-xs font-bold tracking-[0.18em] text-slate-400">الخطوة 4</p>
                        <h2 class="mt-3 text-2xl font-black text-slate-950">شنو بغيتي تركّز عليه؟</h2>
                        <p class="mt-2 text-sm text-slate-500">اختر الأقسام اللي باغي تعطيها أولوية باش التوصيات الجاية تكون أقرب لاحتياجك.</p>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ($sectionOptions as $value => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-3xl border border-black/10 bg-slate-50 px-4 py-4 transition hover:border-black/20 hover:bg-white">
                                    <input type="checkbox" name="focus_sections[]" value="{{ $value }}" class="h-5 w-5 rounded border-black/20 text-red-600 focus:ring-red-500" {{ in_array($value, old('focus_sections', []), true) ? 'checked' : '' }}>
                                    <span class="text-sm font-semibold text-slate-800">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('focus_sections')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('focus_sections.*')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="flex flex-col items-start justify-between gap-4 rounded-[2rem] border border-black/5 bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.06)] sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-2xl font-black text-slate-950">جاهز نبدأو؟</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-500">من بعد الحفظ، غادي ندخلوك للواجهة الرئيسية باش تبدا التدريب بالطريقة اللي كتناسبك.</p>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-full bg-[linear-gradient(135deg,#2c0d0d_0%,#d91f26_55%,#f59e0b_100%)] px-8 py-4 text-base font-black text-white shadow-[0_18px_35px_rgba(217,31,38,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_22px_40px_rgba(217,31,38,0.28)]">
                        حفظ الإعدادات
                    </button>
                </section>
            </form>
        </div>
    </div>
</x-app-layout>
