<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $pendingApprovalsCount = \App\Models\User::query()
        ->where('is_admin', false)
        ->where('access_status', \App\Models\User::ACCESS_PENDING)
        ->count();
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} - TELC Simulator</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
<div class="min-h-screen grid grid-cols-[240px_1fr]">
    <aside class="bg-slate-900 text-white p-5">
        <h1 class="text-xl font-bold">TELC Admin</h1>
        <p class="mt-1 text-xs text-slate-300">{{ auth()->user()->name }}</p>

        <nav class="mt-8 space-y-2 text-sm">
            <a href="{{ route('admin.exams.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.exams.*') ? 'bg-slate-700' : 'hover:bg-slate-800' }}">Exams</a>
            <a href="{{ route('admin.approvals.index') }}" class="flex items-center justify-between rounded-md px-3 py-2 {{ request()->routeIs('admin.approvals.*') ? 'bg-slate-700' : 'hover:bg-slate-800' }}">
                <span>Approvals</span>
                @if ($pendingApprovalsCount > 0)
                    <span class="rounded-full bg-amber-400 px-2 py-0.5 text-xs font-semibold text-slate-900">{{ $pendingApprovalsCount }}</span>
                @endif
            </a>
            <a href="{{ route('dashboard') }}" class="block rounded-md px-3 py-2 hover:bg-slate-800">Student View</a>
            <a href="{{ route('profile.edit') }}" class="block rounded-md px-3 py-2 hover:bg-slate-800">Profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left rounded-md px-3 py-2 hover:bg-slate-800" type="submit">Logout</button>
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
