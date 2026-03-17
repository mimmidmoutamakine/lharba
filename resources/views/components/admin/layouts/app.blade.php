<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'لوحة الإدارة' }} - الهربة</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
<div class="min-h-screen grid grid-cols-[240px_1fr]">
    <aside class="bg-slate-900 text-white p-5">
        <h1 class="text-xl font-bold">إدارة الهربة</h1>
        <p class="mt-1 text-xs text-slate-300">{{ auth()->user()->name }}</p>

        <nav class="mt-8 space-y-2 text-sm" dir="rtl">
            <a href="{{ route('admin.exams.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.exams.*') ? 'bg-slate-700' : 'hover:bg-slate-800' }}">الامتحانات</a>
            <a href="{{ route('admin.approvals.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.approvals.*') ? 'bg-slate-700' : 'hover:bg-slate-800' }}">طلبات الموافقة</a>
            <a href="{{ route('dashboard') }}" class="block rounded-md px-3 py-2 hover:bg-slate-800">واجهة الطالب</a>
            <a href="{{ route('profile.edit') }}" class="block rounded-md px-3 py-2 hover:bg-slate-800">الملف الشخصي</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-right rounded-md px-3 py-2 hover:bg-slate-800" type="submit">تسجيل الخروج</button>
            </form>
        </nav>
    </aside>

    <main class="p-8">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        {{ $slot }}
    </main>
</div>
</body>
</html>
