<x-app-layout>
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
            <div class="relative overflow-hidden rounded-[2rem] bg-white px-6 py-10 sm:px-10">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(239,68,68,0.16),transparent_30%),radial-gradient(circle_at_top_right,rgba(250,204,21,0.18),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.08),transparent_25%)]"></div>

                <div class="relative text-right" dir="rtl">
                    <p class="text-sm font-semibold text-amber-600">صلاحية الدخول</p>
                    <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">طلبك قيد المراجعة</h1>
                    <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">
                        أهلاً {{ $user->name }}، الحساب ديالك تسجّل بنجاح، ولكن خاص الإدارة توافق عليه أولاً قبل ما يبان ليك محتوى المنصة والامتحانات.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-sm font-semibold text-slate-500">الحالة الحالية</p>
                            <p class="mt-2 text-2xl font-black {{ $user->isRejected() ? 'text-rose-600' : 'text-amber-600' }}">
                                {{ $user->isRejected() ? 'تم رفض الطلب' : 'في انتظار الموافقة' }}
                            </p>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-sm font-semibold text-slate-500">شنو غادي يوقع من بعد؟</p>
                            <p class="mt-2 text-base leading-7 text-slate-700">
                                منين الإدارة توافق، غادي يفتح ليك الوصول كامل للمنصة، ومن بعد تقدر تكمل الإعدادات وتبدا التمرين عادي.
                            </p>
                        </div>
                    </div>

                    @if ($user->approval_note)
                        <div class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 p-5 text-right">
                            <p class="text-sm font-semibold text-amber-700">ملاحظة من الإدارة</p>
                            <p class="mt-2 text-base leading-7 text-amber-900">{{ $user->approval_note }}</p>
                        </div>
                    @endif

                    <div class="mt-8 flex flex-wrap justify-end gap-3">
                        <a
                            href="{{ route('profile.edit') }}"
                            class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-800 transition hover:-translate-y-0.5 hover:border-slate-400"
                        >
                            تعديل الحساب
                        </a>

                        <!-- <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-slate-900 via-red-600 to-amber-400 px-6 py-3 text-sm font-bold text-white shadow-[0_16px_30px_rgba(15,23,42,0.18)] transition hover:-translate-y-0.5"
                            >
                                تسجيل الخروج
                            </button>
                        </form> -->
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
