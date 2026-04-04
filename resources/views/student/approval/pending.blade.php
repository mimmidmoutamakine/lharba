<x-app-layout>
    <div class="min-h-[calc(100vh-5rem)] bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.96),rgba(241,245,249,0.96))]" dir="rtl">
        <div class="mx-auto max-w-5xl px-4 py-5 sm:px-6 lg:px-8">
            <div class="space-y-5">

                {{-- Top hero --}}
                <section class="rounded-[2rem] border border-slate-200/80 bg-white/90 p-5 shadow-[0_20px_60px_rgba(15,23,42,0.06)] sm:p-6 lg:p-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="text-right">
                            <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">
                                <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                                صلاحية الدخول
                            </div>

                            <h1 class="mt-4 text-3xl font-black text-slate-950 sm:text-4xl">
                                طلبك قيد المراجعة
                            </h1>

                            <p class="mt-3 max-w-2xl text-base leading-8 text-slate-500">
                                الحساب ديالك تسجّل مزيان، ودابا الإدارة كاتراجع الطلب قبل تفعيل الدخول المناسب ليك.
                            </p>
                        </div>

                        <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-4 text-center shadow-sm">
                            <p class="text-xs font-black tracking-[0.16em] text-amber-700">الحالة الحالية</p>
                            <p class="mt-2 text-2xl font-black text-amber-600">
                                {{ $latestRequest->status === 'rejected' ? 'مرفوض' : 'في انتظار الموافقة' }}
                            </p>
                        </div>
                    </div>
                </section>

                {{-- Mobile-first actions first --}}
                <section class="grid gap-5 lg:grid-cols-[0.95fr_1.05fr]">
                    {{-- Actions --}}
                    <div class="order-1 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_18px_50px_rgba(15,23,42,0.05)] sm:p-6">
                        <div class="mb-4 text-right">
                            <p class="text-xs font-black tracking-[0.16em] text-slate-400">شنو تقدر تدير دابا؟</p>
                            <h2 class="mt-1 text-2xl font-black text-slate-950">خياراتك</h2>
                        </div>

                        <div class="space-y-3">
                            <a
                                href="{{ route('setup.show') }}"
                                class="inline-flex w-full items-center justify-center rounded-full bg-[linear-gradient(135deg,#2c0d0d_0%,#d91f26_55%,#f59e0b_100%)] px-6 py-3.5 text-sm font-black text-white shadow-[0_16px_35px_rgba(217,31,38,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_20px_40px_rgba(217,31,38,0.28)]"
                            >
                                تعديل الطلب
                            </a>

                            <a
                                href="{{ route('profile.edit') }}"
                                class="inline-flex w-full items-center justify-center rounded-full border border-slate-300 bg-white px-6 py-3.5 text-sm font-black text-slate-700 transition hover:border-slate-400"
                            >
                                تعديل الحساب
                            </a>
                        </div>
                    </div>

                    {{-- Request summary --}}
                    <div class="order-2 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_18px_50px_rgba(15,23,42,0.05)] sm:p-6">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div class="text-right">
                                <p class="text-xs font-black tracking-[0.16em] text-slate-400">الطلب الحالي</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-950">طلبك</h2>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 shadow-sm">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 7.5 12 4l8 3.5L12 11 4 7.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M6 9.5V15l6 3 6-3V9.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4 text-right">
                                <p class="text-xs font-black text-slate-400">الامتحان</p>
                                <p class="mt-2 text-xl font-black text-slate-950">{{ $latestRequest->examFamily?->name ?? '—' }}</p>
                            </div>

                            <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4 text-right">
                                <p class="text-xs font-black text-slate-400">المستوى</p>
                                <p class="mt-2 text-xl font-black text-slate-950">{{ $latestRequest->level ?? '—' }}</p>
                            </div>

                            <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4 text-right">
                                <p class="text-xs font-black text-slate-400">تاريخ الإرسال</p>
                                <p class="mt-2 text-base font-black text-slate-800">{{ optional($latestRequest->submitted_at)->format('Y-m-d H:i') ?? '—' }}</p>
                            </div>

                            <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4 text-right">
                                <p class="text-xs font-black text-slate-400">الحالة</p>
                                <p class="mt-2 text-base font-black {{ $latestRequest->status === 'rejected' ? 'text-rose-600' : 'text-amber-600' }}">
                                    {{ $latestRequest->status === 'rejected' ? 'مرفوض' : 'قيد المراجعة' }}
                                </p>
                            </div>
                        </div>

                        @if (!empty($latestRequest->review_note))
                            <div class="mt-4 rounded-[1.25rem] border border-amber-200 bg-amber-50 px-4 py-4 text-right">
                                <p class="text-xs font-black text-amber-700">ملاحظة من الإدارة</p>
                                <p class="mt-2 text-sm leading-7 text-amber-900">{{ $latestRequest->review_note }}</p>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Timeline + FAQ --}}
                <section class="grid gap-5 lg:grid-cols-[1.05fr_0.95fr]">
                    {{-- Timeline --}}
                    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_18px_50px_rgba(15,23,42,0.05)] sm:p-6">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div class="text-right">
                                <p class="text-xs font-black tracking-[0.16em] text-slate-400">التقدّم</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-950">فين وصلنا؟</h2>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 shadow-sm">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="font-black text-slate-900">تم إنشاء الحساب</p>
                                    <p class="text-sm leading-7 text-slate-500">الحساب ديالك تسجّل بنجاح.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="font-black text-slate-900">تم إرسال الطلب</p>
                                    <p class="text-sm leading-7 text-slate-500">الطلب ديالك توصل للإدارة للمراجعة.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                    <svg class="h-4 w-4 animate-pulse" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 8v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="font-black text-slate-900">في انتظار مراجعة الإدارة</p>
                                    <p class="text-sm leading-7 text-slate-500">دابا كاتراجع الاختيار ديالك قبل التفعيل.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 opacity-60">
                                <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 12h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="m13 7 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="font-black text-slate-900">تفعيل الدخول</p>
                                    <p class="text-sm leading-7 text-slate-500">منين تتم الموافقة، غادي يفتح ليك الدخول المناسب.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FAQ --}}
                    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_18px_50px_rgba(15,23,42,0.05)] sm:p-6">
                        <div class="mb-4 text-right">
                            <p class="text-xs font-black tracking-[0.16em] text-slate-400">أسئلة سريعة</p>
                            <h2 class="mt-1 text-2xl font-black text-slate-950">FAQ</h2>
                        </div>

                        <div class="space-y-3">
                            <details class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3">
                                <summary class="cursor-pointer text-sm font-black text-slate-900">علاش خاص المراجعة؟</summary>
                                <p class="mt-2 text-sm leading-7 text-slate-500">
                                    باش الإدارة تتأكد من نوع الامتحان والمستوى المناسب قبل تفعيل الدخول.
                                </p>
                            </details>

                            <details class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3">
                                <summary class="cursor-pointer text-sm font-black text-slate-900">واش نقدر نبدّل الاختيار؟</summary>
                                <p class="mt-2 text-sm leading-7 text-slate-500">
                                    نعم، تقدر تضغط على "تعديل الطلب" وتعاود تختار الامتحان أو المستوى.
                                </p>
                            </details>

                            <details class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3">
                                <summary class="cursor-pointer text-sm font-black text-slate-900">شنو غادي يوقع من بعد؟</summary>
                                <p class="mt-2 text-sm leading-7 text-slate-500">
                                    منين تتم الموافقة، غادي يفتح ليك الدخول المناسب للمسار اللي اخترتي.
                                </p>
                            </details>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>
</x-app-layout>