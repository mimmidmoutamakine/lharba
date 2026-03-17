<x-admin.layouts.app>
    <div class="space-y-8" dir="rtl">
        <div class="flex items-center justify-between gap-4">
            <div class="text-right">
                <h1 class="text-3xl font-bold text-slate-900">طلبات الموافقة</h1>
                <p class="mt-2 text-sm text-slate-500">أي طالب جديد خاص الإدارة توافق عليه أولاً قبل ما ياخذ الوصول الكامل للمنصة.</p>
            </div>
            <div class="rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700">
                المعلقة الآن: {{ $pendingUsers->count() }}
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4 text-right">
                <h2 class="text-lg font-semibold text-slate-900">طلبات جديدة</h2>
            </div>

            @if ($pendingUsers->isEmpty())
                <div class="px-6 py-10 text-center text-sm text-slate-500">ما كاين حتى طلب جديد دابا.</div>
            @else
                <div class="divide-y divide-slate-200">
                    @foreach ($pendingUsers as $pendingUser)
                        <div class="grid gap-4 px-6 py-5 lg:grid-cols-[1fr_1.3fr]">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <form method="POST" action="{{ route('admin.approvals.approve', $pendingUser) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-right">
                                    @csrf
                                    <label class="mb-2 block text-sm font-medium text-emerald-900">ملاحظة (اختياري)</label>
                                    <textarea name="approval_note" rows="3" class="w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-400 focus:outline-none"></textarea>
                                    <button type="submit" class="mt-3 inline-flex rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                        موافقة
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.approvals.reject', $pendingUser) }}" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-right">
                                    @csrf
                                    <label class="mb-2 block text-sm font-medium text-rose-900">سبب الرفض (اختياري)</label>
                                    <textarea name="approval_note" rows="3" class="w-full rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none"></textarea>
                                    <button type="submit" class="mt-3 inline-flex rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                                        رفض
                                    </button>
                                </form>
                            </div>

                            <div class="text-right">
                                <h3 class="text-lg font-semibold text-slate-900">{{ $pendingUser->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $pendingUser->email }}</p>
                                <p class="mt-3 text-sm text-slate-600">
                                    تسجّل في {{ optional($pendingUser->created_at)->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4 text-right">
                <h2 class="text-lg font-semibold text-slate-900">آخر القرارات</h2>
            </div>

            @if ($recentUsers->isEmpty())
                <div class="px-6 py-8 text-sm text-slate-500 text-right">ما كاين حتى قرار مسجل حتى الآن.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm" dir="rtl">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-6 py-3 text-right font-medium">الاسم</th>
                                <th class="px-6 py-3 text-right font-medium">البريد</th>
                                <th class="px-6 py-3 text-right font-medium">الحالة</th>
                                <th class="px-6 py-3 text-right font-medium">وافق عليه</th>
                                <th class="px-6 py-3 text-right font-medium">ملاحظة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach ($recentUsers as $recentUser)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $recentUser->name }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $recentUser->email }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $recentUser->isApproved() ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ $recentUser->isApproved() ? 'مقبول' : 'مرفوض' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">{{ optional($recentUser->approver)->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $recentUser->approval_note ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-admin.layouts.app>
